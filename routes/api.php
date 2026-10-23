<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\InvestmentController;
use App\Http\Controllers\Api\V1\LoanController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ReferenceController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

/*
| Keystead REST API, version 1 (mounted at /api/v1).
| Every response uses the envelope { success, message, data?, meta?, links?, errors? }.
*/
Route::prefix('v1')->name('api.v1.')->group(function () {

    // --- public --------------------------------------------------------------
    Route::prefix('auth')->name('auth.')->middleware('throttle:auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])->name('login');
        Route::post('verify-email', [AuthController::class, 'verifyEmail'])->name('verify');
        Route::post('resend-verification', [AuthController::class, 'resendVerification'])->name('resend');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('reset');
    });

    Route::get('countries', [ReferenceController::class, 'countries'])->name('countries');
    Route::get('countries/{country}/states', [ReferenceController::class, 'states'])->name('states');

    // --- authenticated ---------------------------------------------------------
    Route::middleware(['auth:sanctum', 'active', 'throttle:api'])->group(function () {

        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::put('auth/password', [AuthController::class, 'changePassword'])->name('auth.password');
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');

        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('wallet', [WalletController::class, 'show'])->name('wallet');
        Route::get('portfolio', [DashboardController::class, 'portfolio'])->name('portfolio');
        Route::get('financial-profile', [DashboardController::class, 'financialProfile'])->name('financial-profile');
        Route::get('earnings', [WalletController::class, 'earnings'])->name('earnings');

        Route::get('transactions', [WalletController::class, 'transactions'])->name('transactions.index');
        Route::get('transactions/{transaction}', [WalletController::class, 'transaction'])->name('transactions.show');

        Route::get('withdrawals', [WalletController::class, 'withdrawals'])->name('withdrawals.index');
        Route::post('withdrawals', [WalletController::class, 'requestWithdrawal'])
            ->middleware(['verified.email', 'throttle:money', 'idempotent'])->name('withdrawals.store');

        // Investments
        Route::get('investment-projects', [InvestmentController::class, 'projects'])->name('projects.index');
        Route::get('investment-projects/{project}', [InvestmentController::class, 'showProject'])->name('projects.show');
        Route::get('investment-projects/{project}/profit-history', [InvestmentController::class, 'projectProfitHistory'])->name('projects.profits');
        Route::get('investments', [InvestmentController::class, 'mine'])->name('investments.index');
        Route::post('investments', [InvestmentController::class, 'invest'])->middleware(['verified.email', 'throttle:money', 'idempotent'])->name('investments.store');
        Route::get('investments/summary', [InvestmentController::class, 'summary'])->name('investments.summary');
        Route::get('profit-history', [InvestmentController::class, 'profitHistory'])->name('profits.index');

        // Loans: {type} is mortgage|property
        Route::prefix('loans/{type}')->name('loans.')->group(function () {
            Route::get('products', [LoanController::class, 'products'])->name('products');
            Route::get('/', [LoanController::class, 'index'])->name('index');
            Route::post('/', [LoanController::class, 'apply'])->middleware(['verified.email', 'throttle:money', 'idempotent'])->name('apply');
            Route::get('{id}', [LoanController::class, 'show'])->whereUuid('id')->name('show');
            Route::post('{id}/payments', [LoanController::class, 'pay'])->whereUuid('id')->middleware(['verified.email', 'throttle:money', 'idempotent'])->name('pay');
            Route::post('{id}/cancel', [LoanController::class, 'cancel'])->whereUuid('id')->middleware(['verified.email', 'throttle:money', 'idempotent'])->name('cancel');
        });
    });
});
