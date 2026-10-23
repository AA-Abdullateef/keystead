<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DepositRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserProfileRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use App\Services\AccountTokenService;
use App\Services\FinancialProfileService;
use App\Services\NotificationService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly FinancialProfileService $profiles,
        private readonly AccountTokenService $tokens,
        private readonly NotificationService $notify,
    )
    {
    }

    public function index(Request $request)
    {
        $users = User::with('roles', 'wallet')
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.users', ['users' => $users, 'editUser' => null]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $user = User::create($request->safe()->only(['name', 'email', 'password']));
            $user->forceFill(['status' => $request->validated('status')])->save(); // status is not mass-assignable
            $user->assignRole($request->validated('role'));
            $this->wallets->getWallet($user->id);
            $this->tokens->sendVerification($user, force: true);
        });

        return back()->with('success', 'User created. A confirmation email was sent to them.');
    }

    public function edit(User $user, Request $request)
    {
        return view('admin.users', [
            'users' => User::with('roles', 'wallet')->orderBy('name')->paginate(20),
            'editUser' => $user,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()) && $request->validated('status') === 'suspended', 422, 'You cannot suspend your own account.');

        $user->forceFill($request->validated())->save();
        if ($user->wasChanged('status')) {
            if ($user->status === 'suspended') {
                $user->tokens()->delete(); // cut off API sessions immediately
            }
            app(\App\Services\AuditLogger::class)->log('user.status_changed', $user, null, ['status' => $user->status], 'Account ' . $user->status . ' by administrator');
            $this->notify->accountStatusChanged($user, $user->status);
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function deposit(DepositRequest $request, User $user): RedirectResponse
    {
        $this->wallets->deposit($user->id, $request->amount(), $request->user()->id, $request->validated('note'));

        return back()->with('success', 'Demo funds recorded in the ledger. (No payment processor is involved.)');
    }

    public function profile(User $user)
    {
        $user->load('profile');
        $countries = Country::orderBy('name')->get();
        $states = State::when($user->profile?->country_id, fn ($q, $c) => $q->where('country_id', $c))->orderBy('name')->get();

        return view('admin.user_profile', compact('user', 'countries', 'states'));
    }

    public function updateProfile(UpdateUserProfileRequest $request, User $user): RedirectResponse
    {
        $user->profile()->updateOrCreate(['user_id' => $user->id], $request->validated() + ['updated_by' => $request->user()->id]);

        return back()->with('success', 'Profile updated.');
    }

    public function financialProfile(User $user)
    {
        return view('admin.financial_profile', $this->profiles->forUser($user->id));
    }

    public function states(Country $country): JsonResponse
    {
        return response()->json($country->states()->orderBy('name')->get(['id', 'name']));
    }
}
