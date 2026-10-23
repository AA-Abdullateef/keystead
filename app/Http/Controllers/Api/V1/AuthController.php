<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ChangePasswordRequest;
use App\Http\Requests\Api\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Requests\Api\Auth\ResendVerificationRequest;
use App\Http\Requests\Api\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\Auth\VerifyEmailRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AccountTokenService;
use App\Services\AuditLogger;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly AccountTokenService $tokens,
        private readonly AuditLogger $audit,
    ) {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create($request->safe()->only(['name', 'email', 'password']));
            $user->assignRole(User::ROLE_INVESTOR); // never client-controlled
            $this->wallets->getWallet($user->id);
            $this->audit->log('auth.registered', $user, null, ['email' => $user->email], 'Self-registration', $user);
            $this->tokens->sendVerification($user, force: true);

            return $user;
        });

        return $this->created('Registration successful. Check your inbox to confirm your email address.', [
            'user' => (new UserResource($user->load('roles')))->resolve(),
            'access_token' => $user->createToken($request->input('device_name', 'api'))->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $user = $this->tokens->verifyEmail($request->validated('email'), $request->validated('token'));

        return $this->success('Email address confirmed.', new UserResource($user->load('roles')));
    }

    public function resendVerification(ResendVerificationRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();
        if ($user) {
            $this->tokens->sendVerification($user);
        }

        // Same answer whether or not the address exists, is verified, or is cooling down.
        return $this->success('If that address is registered and unverified, a new confirmation link has been sent.');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        // Same message for unknown e-mail and wrong password (no account enumeration).
        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            $this->audit->log('auth.login_failed', $user, null, ['email' => $request->validated('email')], 'Failed API login', $user);
            throw ValidationException::withMessages(['email' => ['These credentials do not match our records.']]);
        }

        if (! $user->isActive()) {
            $this->audit->log('auth.login_blocked', $user, null, null, 'Login refused: account suspended', $user);

            return $this->error('This account is suspended.', null, 403);
        }

        $device = $request->validated('device_name') ?? 'api';
        $user->tokens()->where('name', $device)->delete(); // one live token per device
        $this->audit->log('auth.login', $user, null, ['device' => $device], 'API login', $user);

        return $this->success('Login successful.', [
            'user' => (new UserResource($user->load('roles')))->resolve(),
            'access_token' => $user->createToken($device)->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success('Authenticated user.', new UserResource($request->user()->load('roles')));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        $this->audit->log('auth.logout', $request->user(), null, null, 'API logout');

        return $this->success('Logged out.');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->tokens->changePassword(
            $request->user(),
            $request->validated('current_password'),
            $request->validated('password'),
            keepTokenId: (string) $request->user()->currentAccessToken()->id,
            ip: $request->ip(),
        );

        return $this->success('Password changed. Your other sessions were signed out.');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->tokens->sendPasswordReset($request->validated('email'));

        // Identical answer whether or not the address exists.
        return $this->success('If that address is registered, a reset link has been sent.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->tokens->resetPassword($request->validated('email'), $request->validated('token'), $request->validated('password'), $request->ip());

        return $this->success('Password reset successful. Please sign in.');
    }
}
