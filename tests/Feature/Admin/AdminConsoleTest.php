<?php

namespace Tests\Feature\Admin;

use App\Enums\LoanType;
use App\Enums\TransactionStatus;
use App\Models\Country;
use App\Models\Transaction;
use App\Models\User;
use App\Services\InvestmentService;
use App\Services\LoanService;
use App\Services\ProfitDistributionService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFinance;
use Tests\TestCase;

class AdminConsoleTest extends TestCase
{
    use RefreshDatabase, BuildsFinance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function manager(): User
    {
        return User::factory()->manager()->create();
    }

    /* ------------------------------------------------------------ access control */

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->post('/admin/users', [])->assertRedirect('/login');
    }

    public function test_investors_cannot_use_the_console(): void
    {
        $this->actingAs($this->investor())->get('/admin')->assertForbidden();
    }

    public function test_staff_login_flow_and_investor_login_is_refused(): void
    {
        $admin = $this->admin();
        $this->post('/login', ['email' => $admin->email, 'password' => 'Password123!'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);

        auth()->logout();
        $inv = $this->investor();
        $this->post('/login', ['email' => $inv->email, 'password' => 'Password123!'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/login', ['email' => $admin->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
    }

    public function test_suspended_staff_cannot_sign_in(): void
    {
        $u = User::factory()->admin()->suspended()->create();
        $this->post('/login', ['email' => $u->email, 'password' => 'Password123!'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_managers_can_read_but_cannot_move_money(): void
    {
        $m = $this->manager();
        $inv = $this->investor('1000.00');
        $project = $this->project();

        $this->actingAs($m)->get('/admin')->assertOk();
        $this->actingAs($m)->post("/admin/users/{$inv->id}/deposit", ['amount' => '50'])->assertForbidden();
        $this->actingAs($m)->post('/admin/investments/investors', ['user_id' => $inv->id, 'investment_project_id' => $project->id, 'amount' => '1000'])->assertForbidden();
        $this->actingAs($m)->post('/admin/profit-distribution', [])->assertForbidden();
        $this->actingAs($m)->post('/admin/users', ['name' => 'x'])->assertForbidden();
        $this->assertSame('1000.00', $this->balance($inv));
    }

    /* -------------------------------------------------------------- page smoke */

    public function test_every_console_page_renders_with_data(): void
    {
        $admin = $this->admin();
        $inv = $this->investor('60000.00');
        $project = $this->project();
        app(InvestmentService::class)->invest($inv->id, $project->id, '5000.00');
        [$m, $y] = $this->lastMonth();
        app(InvestmentService::class)->recordIncome($project->id, '1000.00', $m, $y, $admin->id);
        app(InvestmentService::class)->recordExpense($project->id, 'repairs', '100.00', $m, $y, 'roof', $admin->id);
        app(ProfitDistributionService::class)->distribute($project->id, $m, $y, $admin->id);
        $loan = app(LoanService::class)->apply(LoanType::Mortgage, $this->loanProject()->id, '20000.00', $inv->id);
        app(LoanService::class)->approve(LoanType::Mortgage, $loan->id, $admin->id);
        app(LoanService::class)->pay(LoanType::Mortgage, $loan->id, $inv->id);
        app(WalletService::class)->requestWithdrawal($inv->id, '100.00');
        $this->loanProject(LoanType::Property);
        Country::create(['name' => 'Testland', 'code' => 'TL']);

        $txn = Transaction::first();
        $investment = $inv->investments()->first();
        $this->actingAs($admin);

        $urls = [
            '/admin', '/admin/users', '/admin/users?q=a&status=active', "/admin/users/{$inv->id}/edit", "/admin/users/{$inv->id}/profile",
            "/admin/users/{$inv->id}/financial-profile", '/admin/properties', '/admin/properties?type=loan',
            '/admin/properties/' . $project->property_id, '/admin/properties/' . $project->property_id . '/edit',
            '/admin/investments', "/admin/investments/projects/{$project->id}/edit", "/admin/investments/investors/{$investment->id}/edit",
            '/admin/rental-income', '/admin/expenses', '/admin/profit-distribution',
            "/admin/profit-distribution?investment_project_id={$project->id}&month=$m&year=$y",
            '/admin/earnings', '/admin/withdrawals', '/admin/withdrawals?status=pending', '/admin/transactions', '/admin/transactions?type=deposit',
            "/admin/transactions/{$txn->id}", '/admin/loan-projects', '/admin/loans/mortgage', '/admin/loans/property',
            "/admin/loans/mortgage/{$loan->id}",
        ];
        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
        $this->getJson('/admin/countries/' . Country::first()->id . '/states')->assertOk();
        $this->get('/admin/loans/bogus')->assertNotFound();
    }

    /* ------------------------------------------------------------ money actions */

    public function test_admin_can_fund_a_wallet_and_it_lands_in_the_ledger(): void
    {
        $admin = $this->admin();
        $inv = $this->investor('0.00');

        $this->actingAs($admin)->post("/admin/users/{$inv->id}/deposit", ['amount' => '250.75', 'note' => 'demo'])->assertSessionHasNoErrors();
        $this->assertSame('250.75', $this->balance($inv));
        $this->post("/admin/users/{$inv->id}/deposit", ['amount' => '1.234'])->assertSessionHasErrors('amount');
    }

    public function test_user_creation_assigns_role_status_and_wallet(): void
    {
        $this->actingAs($this->admin())->post('/admin/users', [
            'name' => 'Nia', 'email' => 'nia@example.test', 'password' => 'Password123!', 'role' => 'manager', 'status' => 'active',
        ])->assertSessionHasNoErrors();

        $u = User::where('email', 'nia@example.test')->first();
        $this->assertTrue($u->hasRole('manager'));
        $this->assertNotNull($u->wallet()->first());
    }

    public function test_admin_cannot_suspend_themselves_and_suspension_revokes_tokens(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put("/admin/users/{$admin->id}", ['name' => 'A', 'status' => 'suspended'])->assertStatus(422);

        $inv = $this->investor();
        $inv->createToken('t');
        $this->put("/admin/users/{$inv->id}", ['name' => 'B', 'status' => 'suspended'])->assertRedirect();
        $this->assertSame(0, $inv->tokens()->count());
        $this->assertFalse($inv->fresh()->isActive());
    }

    public function test_withdrawal_approval_and_rejection_from_the_console(): void
    {
        $admin = $this->admin();
        $a = $this->investor('500.00');
        $b = $this->investor('500.00');
        $wa = app(WalletService::class)->requestWithdrawal($a->id, '200.00');
        $wb = app(WalletService::class)->requestWithdrawal($b->id, '200.00');

        $this->actingAs($admin)->post("/admin/withdrawals/{$wa->id}/approve")->assertSessionHasNoErrors();
        $this->post("/admin/withdrawals/{$wb->id}/reject")->assertSessionHasNoErrors();

        $this->assertSame(TransactionStatus::Completed, $wa->fresh()->status);
        $this->assertSame('300.00', $this->balance($a));
        $this->assertSame('500.00', $this->balance($b));
        $this->post("/admin/withdrawals/{$wb->id}/reject")->assertSessionHasErrors(); // already decided
        $this->assertSame('500.00', $this->balance($b));
    }

    public function test_project_and_profit_workflow_through_the_console(): void
    {
        $admin = $this->admin();
        $inv = $this->investor('10000.00');
        $property = \App\Models\Property::factory()->create();
        $this->actingAs($admin);

        $this->post('/admin/investments/projects', [
            'property_id' => $property->id, 'target_amount' => '10000.00', 'min_investment' => '1000.00',
            'management_fee_percent' => '10', 'start_date' => now()->subMonths(3)->toDateString(),
        ])->assertSessionHasNoErrors();
        $project = \App\Models\InvestmentProject::first();

        $this->post('/admin/investments/investors', ['user_id' => $inv->id, 'investment_project_id' => $project->id, 'amount' => '10000.00'])->assertSessionHasNoErrors();
        $this->assertSame('0.00', $this->balance($inv));
        $this->assertSame('funded', $project->fresh()->status->value);

        [$m, $y] = $this->lastMonth();
        $this->post('/admin/rental-income', ['investment_project_id' => $project->id, 'month' => $m, 'year' => $y, 'amount' => '1000.00'])->assertSessionHasNoErrors();
        $this->post('/admin/rental-income', ['investment_project_id' => $project->id, 'month' => $m, 'year' => $y, 'amount' => '5.00'])->assertSessionHasErrors('month');
        $this->post('/admin/profit-distribution', ['investment_project_id' => $project->id, 'month' => $m, 'year' => $y])->assertSessionHasNoErrors();
        $this->assertSame('900.00', $this->balance($inv));
        $this->post('/admin/profit-distribution', ['investment_project_id' => $project->id, 'month' => $m, 'year' => $y])->assertSessionHasErrors('profit');
        $this->assertSame('900.00', $this->balance($inv));
        $this->artisan('finance:reconcile')->assertSuccessful();

        // closed periods cannot be edited or deleted
        $income = \App\Models\RentalIncome::first();
        $this->put("/admin/rental-income/{$income->id}", ['amount' => '9999.00'])->assertSessionHasErrors();
        $this->delete("/admin/rental-income/{$income->id}")->assertSessionHasErrors();
        // reversal refused after distribution
        $this->delete('/admin/investments/investors/' . $inv->investments()->first()->id)->assertSessionHasErrors();
    }

    public function test_loan_approval_and_rejection_from_the_console(): void
    {
        $admin = $this->admin();
        $u1 = $this->investor('50000.00');
        $u2 = $this->investor('50000.00');
        $l1 = app(LoanService::class)->apply(LoanType::Mortgage, $this->loanProject()->id, '20000.00', $u1->id);
        $l2 = app(LoanService::class)->apply(LoanType::Mortgage, $this->loanProject()->id, '20000.00', $u2->id);

        $this->actingAs($admin)->post("/admin/loans/mortgage/{$l1->id}/approve")->assertSessionHasNoErrors();
        $this->post("/admin/loans/mortgage/{$l2->id}/reject")->assertSessionHasNoErrors();
        $this->assertSame('30000.00', $this->balance($u1));
        $this->assertSame('50000.00', $this->balance($u2));

        $this->post("/admin/loans/mortgage/{$l1->id}/approve")->assertSessionHasErrors();
        $this->post("/admin/loans/mortgage/{$l2->id}/reject")->assertSessionHasErrors();
        $this->assertSame('50000.00', $this->balance($u2));
        // wrong type in URL cannot reach another table's loan
        $this->post("/admin/loans/property/{$l1->id}/reject")->assertNotFound();
    }

    public function test_loan_product_with_applications_only_allows_status_change(): void
    {
        $admin = $this->admin();
        $u = $this->investor('50000.00');
        $product = $this->loanProject();
        app(LoanService::class)->apply(LoanType::Mortgage, $product->id, '20000.00', $u->id);

        $this->actingAs($admin)->put("/admin/loan-projects/{$product->id}", [
            'property_value' => '1.00', 'min_initial_payment' => '0.50', 'interest_rate' => '1', 'duration_months' => 1, 'status' => 'inactive',
        ])->assertSessionHasNoErrors();
        $product->refresh();
        $this->assertSame('120000.00', $product->property_value);
        $this->assertSame('inactive', $product->status);
    }

    public function test_pagination_view_renders(): void
    {
        $admin = $this->admin();
        \App\Models\Property::factory()->count(20)->create();
        $this->actingAs($admin)->get('/admin/properties')->assertOk()->assertSee('Showing 1–15 of 20', false);
    }
}
