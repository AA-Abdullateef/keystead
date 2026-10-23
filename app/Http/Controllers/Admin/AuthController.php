<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ForgotPasswordRequest;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Models\User;
use App\Services\AccountTokenService;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Session login for staff (admin / manager). Investors use the API, not this console. */
class AuthController extends Controller
{
    public function __construct(private readonly AccountTokenService $tokens, private readonly AuditLogger $audit)
    {
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $fail = fn () => back()->withInput($request->only('email'))->withErrors(['email' => 'These credentials do not match our records.']);

        if (! Auth::attempt($credentials)) {
            $this->audit->log('auth.login_failed', User::where('email', $credentials['email'])->first(), null, ['email' => $credentials['email']], 'Failed console login');

            return $fail();
        }

        $user = Auth::user();
        if (! $user->isActive() || ! $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_MANAGER])) {
            $this->audit->log('auth.login_blocked', $user, null, null, 'Console login refused (suspended or not staff)', $user);
            Auth::logout();
            $request->session()->invalidate();

            return back()->withInput($request->only('email'))->withErrors(['email' => 'This area is for Keystead staff only.']);
        }

        $request->session()->regenerate();
        $this->audit->log('auth.login', $user, null, null, 'Console login', $user);

        return redirect()->intended(route('admin.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        if ($user = $request->user()) {
            $this->audit->log('auth.logout', $user, null, null, 'Console logout', $user);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        $this->tokens->sendPasswordReset($request->validated('email'));

        // Same answer for known and unknown addresses.
        return back()->with('status', 'If that address belongs to an account, a reset link has been sent.');
    }

    public function showResetForm(string $token, Request $request)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $this->tokens->resetPassword($request->validated('email'), $request->validated('token'), $request->validated('password'), $request->ip());

        return redirect()->route('login')->with('status', 'Password updated. Please sign in.');
    }

    /** Confirmation page: the link only shows a button, so mail scanners that prefetch URLs cannot burn the token. */
    public function showVerify(string $token, Request $request)
    {
        return view('auth.verify-email', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'token' => ['required', 'string', 'size:64']]);
        $this->tokens->verifyEmail($data['email'], $data['token']);

        return redirect()->route('login')->with('status', 'Email address confirmed. You can now use your account.');
    }
}
