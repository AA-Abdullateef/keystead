<?php

namespace Database\Seeders;

use App\Enums\LoanType;
use App\Models\InvestmentProject;
use App\Models\LoanProject;
use App\Models\Property;
use App\Models\User;
use App\Services\InvestmentService;
use App\Services\LoanService;
use App\Services\ProfitDistributionService;
use App\Services\WalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

/**
 * Realistic demo book built entirely through the services, so the ledger, portfolios and
 * distributions are exactly what production code would produce. Refuses to run in production.
 *
 *   php artisan db:seed --class=DemoSeeder      (password: DEMO_PASSWORD, default "Password123!")
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (App::isProduction()) {
            $this->command?->error('DemoSeeder refuses to run in production.');

            return;
        }
        if (User::where('email', 'admin@keystead.test')->exists()) {
            $this->command?->warn('Demo data already present — skipping.');

            return;
        }

        $this->call([RoleSeeder::class, CountrySeeder::class, StateSeeder::class]);

        $password = env('DEMO_PASSWORD', 'Password123!');
        $wallets = app(WalletService::class);
        $invest = app(InvestmentService::class);
        $profits = app(ProfitDistributionService::class);
        $loans = app(LoanService::class);

        $make = function (string $name, string $email, string $role) use ($password) {
            $u = User::create(['name' => $name, 'email' => $email, 'password' => $password]);
            $u->email_verified_at = now();
            $u->save();
            $u->assignRole($role);

            return $u;
        };

        $admin = $make('Amara Okafor', 'admin@keystead.test', User::ROLE_ADMIN);
        $make('Tomas Reyes', 'manager@keystead.test', User::ROLE_MANAGER);
        $investors = [
            $make('Lena Fischer', 'lena@keystead.test', User::ROLE_INVESTOR),
            $make('Kofi Mensah', 'kofi@keystead.test', User::ROLE_INVESTOR),
            $make('Priya Nair', 'priya@keystead.test', User::ROLE_INVESTOR),
            $make('Daniel Ortiz', 'daniel@keystead.test', User::ROLE_INVESTOR),
        ];
        foreach ($investors as $i => $u) {
            $wallets->deposit($u->id, (string) (150000 + $i * 50000) . '.00', $admin->id, 'Demo funding');
        }

        // ---- investment projects ------------------------------------------------
        $start = now()->subMonths(8)->startOfMonth()->toDateString();
        $defs = [
            ['The Marlowe Residences', 'Lisbon, Portugal', '420000.00', '200000.00', '5000.00', '10.00', '7.50'],
            ['Harbour Court Lofts', 'Cape Town, South Africa', '310000.00', '150000.00', '2500.00', '12.50', '8.25'],
            ['Birchfield Commons', 'Leeds, United Kingdom', '280000.00', '120000.00', '2000.00', '8.00', '6.90'],
        ];
        $projects = [];
        foreach ($defs as [$name, $loc, $price, $target, $min, $fee, $roi]) {
            $property = Property::create(['name' => $name, 'type' => 'investment', 'location' => $loc, 'purchase_price' => $price, 'status' => 'active',
                'description' => 'Stabilised rental asset held for long-term income.', 'created_by' => $admin->id, 'updated_by' => $admin->id]);
            $projects[] = $invest->createProject([
                'property_id' => $property->id, 'target_amount' => $target, 'min_investment' => $min,
                'management_fee_percent' => $fee, 'expected_roi' => $roi, 'start_date' => $start,
            ], $admin->id);
        }

        [$p1, $p2, $p3] = $projects;
        $invest->invest($investors[0]->id, $p1->id, '60000.00');
        $invest->invest($investors[1]->id, $p1->id, '40000.00');
        $invest->invest($investors[2]->id, $p1->id, '100000.00'); // p1 fully funded
        $invest->invest($investors[0]->id, $p2->id, '30000.00');
        $invest->invest($investors[3]->id, $p2->id, '45000.00');
        $invest->invest($investors[1]->id, $p3->id, '12000.00');

        // ---- income, expenses, distributions for the last 3 closed months -------
        foreach ([3, 2, 1] as $back) {
            $d = now()->startOfMonth()->subMonths($back);
            [$m, $y] = [(int) $d->format('n'), (int) $d->format('Y')];
            foreach ([[$p1, '9800.00', '1450.00'], [$p2, '5200.00', '900.00']] as [$p, $income, $expense]) {
                $invest->recordIncome($p->id, $income, $m, $y, $admin->id);
                $invest->recordExpense($p->id, 'maintenance', $expense, $m, $y, 'Routine upkeep', $admin->id);
                $profits->distribute($p->id, $m, $y, $admin->id);
            }
        }
        // one month entered but not yet distributed, to show the preview screen
        $d = now()->startOfMonth()->subMonth();
        $invest->recordIncome($p3->id, '2100.00', (int) $d->format('n'), (int) $d->format('Y'), $admin->id);

        // ---- loan products and borrowers ---------------------------------------
        $loanProperty = fn (string $n, string $l, string $price) => Property::create(['name' => $n, 'type' => 'loan', 'location' => $l,
            'purchase_price' => $price, 'status' => 'available', 'created_by' => $admin->id, 'updated_by' => $admin->id]);

        $mort = LoanProject::create(['property_id' => $loanProperty('Oakline Townhouse', 'Porto, Portugal', '240000.00')->id, 'loan_type' => LoanType::Mortgage,
            'property_value' => '240000.00', 'min_initial_payment' => '40000.00', 'interest_rate' => '9.00', 'duration_months' => 24, 'status' => 'active', 'created_by' => $admin->id]);
        $mort2 = LoanProject::create(['property_id' => $loanProperty('Saltmarsh Cottage', 'Faro, Portugal', '150000.00')->id, 'loan_type' => LoanType::Mortgage,
            'property_value' => '150000.00', 'min_initial_payment' => '30000.00', 'interest_rate' => '8.00', 'duration_months' => 18, 'status' => 'active', 'created_by' => $admin->id]);
        $prop = LoanProject::create(['property_id' => $loanProperty('Elm Street Duplex', 'Leeds, United Kingdom', '180000.00')->id, 'loan_type' => LoanType::Property,
            'property_value' => '180000.00', 'min_initial_payment' => '25000.00', 'interest_rate' => '7.50', 'duration_months' => 12, 'status' => 'active', 'created_by' => $admin->id]);

        $active = $loans->apply(LoanType::Mortgage, $mort->id, '40000.00', $investors[2]->id);
        $loans->approve(LoanType::Mortgage, $active->id, $admin->id);
        $loans->pay(LoanType::Mortgage, $active->id, $investors[2]->id);
        $loans->apply(LoanType::Mortgage, $mort2->id, '30000.00', $investors[3]->id);        // pending decision
        $loans->apply(LoanType::Property, $prop->id, '25000.00', $investors[1]->id);        // pending decision

        // ---- a withdrawal waiting in the queue ---------------------------------
        $wallets->requestWithdrawal($investors[0]->id, '1500.00');

        $this->command?->info('Demo data ready. Sign in as admin@keystead.test / manager@keystead.test.');
    }
}
