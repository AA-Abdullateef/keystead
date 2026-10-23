<?php

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\InvestmentController;
use App\Http\Controllers\Admin\LedgerController;
use App\Http\Controllers\Admin\LoanController;
use App\Http\Controllers\Admin\ProfitController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
| Staff console (session auth). Investors use the REST API under /api/v1.
|   role:admin,manager  → read access and day-to-day records
|   role:admin          → anything that moves money or changes who can sign in
*/

/* ----------------------------------------------------------------- guest */
Route::redirect('/', '/login');
Route::middleware(['guest', 'throttle:auth'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});
Route::get('/verify-email/{token}', [AuthController::class, 'showVerify'])->name('verification.notice');
Route::post('/verify-email', [AuthController::class, 'verify'])->middleware('throttle:auth')->name('verification.verify');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/* ------------------------------------------------------------------ staff */
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin,manager'])->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('index');

    // People
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/{user}/profile', [UserController::class, 'profile'])->name('user.profile');
    Route::put('users/{user}/profile', [UserController::class, 'updateProfile'])->name('user.profile.update');
    Route::get('users/{user}/financial-profile', [UserController::class, 'financialProfile'])->name('users.financial.profile');
    Route::get('countries/{country}/states', [UserController::class, 'states'])->name('country.states');

    // Properties
    Route::get('properties', [PropertyController::class, 'index'])->name('properties.index');
    Route::post('properties', [PropertyController::class, 'store'])->name('properties.store');
    Route::get('properties/{property}', [PropertyController::class, 'show'])->name('properties.show');
    Route::get('properties/{property}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
    Route::put('properties/{property}', [PropertyController::class, 'update'])->name('properties.update');

    // Investment projects
    Route::get('investments', [InvestmentController::class, 'index'])->name('investments.index');
    Route::post('investments/projects', [InvestmentController::class, 'storeProject'])->name('investments.projects.store');
    Route::get('investments/projects/{project}/edit', [InvestmentController::class, 'editProject'])->name('investments.projects.edit');
    Route::put('investments/projects/{project}', [InvestmentController::class, 'updateProject'])->name('investments.projects.update');

    // Rental income and expenses
    Route::get('rental-income', [LedgerController::class, 'income'])->name('rental.index');
    Route::post('rental-income', [LedgerController::class, 'storeIncome'])->name('rental.store');
    Route::get('rental-income/{income}/edit', [LedgerController::class, 'editIncome'])->name('rental.edit');
    Route::put('rental-income/{income}', [LedgerController::class, 'updateIncome'])->name('rental.update');
    Route::delete('rental-income/{income}', [LedgerController::class, 'destroyIncome'])->name('rental.destroy');

    Route::get('expenses', [LedgerController::class, 'expenses'])->name('expenses.index');
    Route::post('expenses', [LedgerController::class, 'storeExpense'])->name('expenses.store');
    Route::get('expenses/{expense}/edit', [LedgerController::class, 'editExpense'])->name('expenses.edit');
    Route::put('expenses/{expense}', [LedgerController::class, 'updateExpense'])->name('expenses.update');
    Route::delete('expenses/{expense}', [LedgerController::class, 'destroyExpense'])->name('expenses.destroy');

    // Finance (read)
    Route::get('profit-distribution', [ProfitController::class, 'index'])->name('profit.distribution');
    Route::get('earnings', [ProfitController::class, 'earnings'])->name('earnings.index');
    Route::get('withdrawals', [FinanceController::class, 'withdrawals'])->name('withdrawals.index');
    Route::get('transactions', [FinanceController::class, 'transactions'])->name('transactions.index');
    Route::get('transactions/{id}', [FinanceController::class, 'showTransaction'])->whereUuid('id')->name('transactions.show');

    // Lending
    Route::get('loan-projects', [LoanController::class, 'products'])->name('loan.projects');
    Route::post('loan-projects', [LoanController::class, 'storeProduct'])->name('loan.projects.store');
    Route::get('loan-projects/{product}/edit', [LoanController::class, 'editProduct'])->name('loan.projects.edit');
    Route::put('loan-projects/{product}', [LoanController::class, 'updateProduct'])->name('loan.projects.update');

    Route::get('loans/{type}', [LoanController::class, 'index'])->name('loans.index');
    Route::get('loans/{type}/{id}', [LoanController::class, 'show'])->whereUuid('id')->name('loans.show');

    /* ---- admin only: money movement and account control ---- */
    Route::middleware('role:admin')->group(function () {
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('users/{user}/deposit', [UserController::class, 'deposit'])->name('users.deposit');

        Route::delete('investments/projects/{project}', [InvestmentController::class, 'destroyProject'])->name('investments.projects.destroy');
        Route::post('investments/investors', [InvestmentController::class, 'storeInvestor'])->name('investments.investors.store');
        Route::get('investments/investors/{investment}/edit', [InvestmentController::class, 'editInvestor'])->name('investments.investors.edit');
        Route::put('investments/investors/{investment}', [InvestmentController::class, 'updateInvestor'])->name('investments.investors.update');
        Route::delete('investments/investors/{investment}', [InvestmentController::class, 'destroyInvestor'])->name('investments.investors.destroy');

        Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
        Route::post('profit-distribution', [ProfitController::class, 'distribute'])->name('profit.distribute');

        Route::post('withdrawals/{id}/approve', [FinanceController::class, 'approveWithdrawal'])->whereUuid('id')->name('withdrawals.approve');
        Route::post('withdrawals/{id}/reject', [FinanceController::class, 'rejectWithdrawal'])->whereUuid('id')->name('withdrawals.reject');

        Route::post('loans/{type}/{id}/approve', [LoanController::class, 'approve'])->whereUuid('id')->name('loans.approve');
        Route::post('loans/{type}/{id}/reject', [LoanController::class, 'reject'])->whereUuid('id')->name('loans.reject');
    });
});
