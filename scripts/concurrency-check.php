<?php
/**
 * Concurrency harness for MySQL/MariaDB (sqlite cannot prove row locking). DESTRUCTIVE: `setup` runs
 * migrate:fresh on RACE_DB_DATABASE (default keystead_race). Never point it at real data.
 * Driven by scripts/concurrency-check.sh. Usage: php race.php <setup|invest|withdraw|apply|approve|reject|pay|distribute> [loan-id]
 */
chdir(dirname(__DIR__));
foreach (['DB_CONNECTION'=>'mysql','DB_HOST'=>'127.0.0.1','DB_PORT'=>'3306','DB_DATABASE'=>'keystead_race','DB_USERNAME'=>'root','DB_PASSWORD'=>''] as $k => $v) {
    $v = getenv('RACE_'.$k) !== false ? getenv('RACE_'.$k) : $v; putenv("$k=$v"); $_ENV[$k] = $v;
}
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\{User,InvestmentProject,Property,LoanProject,MortgageLoan};
use App\Services\{WalletService,InvestmentService,ProfitDistributionService,LoanService};
use App\Enums\LoanType;
$s = $argv[1]; $ctx = sys_get_temp_dir().'/keystead_race_ctx.json';
$w = app(WalletService::class); $inv = app(InvestmentService::class); $pr = app(ProfitDistributionService::class); $ln = app(LoanService::class);
if ($s === 'setup') {
    Artisan::call('migrate:fresh', ['--force'=>true]); Artisan::call('db:seed', ['--class'=>'RoleSeeder','--force'=>true]);
    $admin = User::create(['name'=>'A','email'=>'a@x.test','password'=>'Password123!']); $admin->assignRole('admin');
    $u = User::create(['name'=>'U','email'=>'u@x.test','password'=>'Password123!']); $u->assignRole('investor');
    $w->deposit($u->id,'1000.00',$admin->id);
    $prop = Property::create(['name'=>'P','type'=>'investment','location'=>'L','status'=>'active','created_by'=>$admin->id]);
    $p = $inv->createProject(['property_id'=>$prop->id,'target_amount'=>'100000.00','min_investment'=>'100.00','management_fee_percent'=>'10','start_date'=>now()->subMonths(6)->toDateString()], $admin->id);
    $lp = Property::create(['name'=>'LP','type'=>'loan','location'=>'L','status'=>'available','created_by'=>$admin->id]);
    $prod = LoanProject::create(['property_id'=>$lp->id,'loan_type'=>LoanType::Mortgage,'property_value'=>'1200.00','min_initial_payment'=>'200.00','interest_rate'=>'10','duration_months'=>10,'status'=>'active','created_by'=>$admin->id]);
    file_put_contents($ctx, json_encode(['admin'=>$admin->id,'user'=>$u->id,'project'=>$p->id,'product'=>$prod->id]));
    echo "setup ok\n"; exit;
}
$c = json_decode(file_get_contents($ctx), true);
try {
    switch ($s) {
        case 'invest': $inv->invest($c['user'],$c['project'],'400.00'); echo "OK\n"; break;
        case 'withdraw': $w->requestWithdrawal($c['user'],'100.00'); echo "OK\n"; break;
        case 'distribute': $d=now()->subMonth(); $pr->distribute($c['project'],(int)$d->format('n'),(int)$d->format('Y'),$c['admin']); echo "OK\n"; break;
        case 'apply': $ln->apply(LoanType::Mortgage,$c['product'],'200.00',$c['user']); echo "OK\n"; break;
        case 'pay': $ln->pay(LoanType::Mortgage,$argv[2],$c['user']); echo "OK\n"; break;
        case 'approve': $ln->approve(LoanType::Mortgage,$argv[2],$c['admin']); echo "OK\n"; break;
        case 'reject': $ln->reject(LoanType::Mortgage,$argv[2]); echo "OK\n"; break;
    }

} catch (Throwable $e) { echo "FAIL ".class_basename($e).": ".substr(str_replace("\n"," ",$e->getMessage()),0,90)."\n"; }
