<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Dashboard\BudgetController;
use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\MonthlyBudgetController;
use App\Http\Controllers\Dashboard\SettingController;
use App\Http\Controllers\Dashboard\TelegramUserController;
use App\Http\Controllers\Dashboard\TransactionController;
use App\Http\Controllers\Dashboard\TransactionDraftController;
use App\Http\Controllers\Dashboard\TransactionLogController;
use App\Http\Controllers\Telegram\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard.index');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'throttle:web'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard.index');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('dashboard.transactions');
    Route::resource('/transactions', TransactionController::class)->except(['index'])->names('dashboard.transactions');
    Route::get('/categories', [CategoryController::class, 'index'])->name('dashboard.categories');
    Route::resource('/categories', CategoryController::class)->except(['index'])->names('dashboard.categories');
    Route::get('/budget', [MonthlyBudgetController::class, 'index'])->name('dashboard.budget');
    Route::resource('/budgets', MonthlyBudgetController::class)->parameters(['budgets' => 'budget'])->names('dashboard.budgets');
    Route::get('/reports', [DashboardController::class, 'reports'])->name('dashboard.reports');
    Route::get('/settings', [SettingController::class, 'index'])->name('dashboard.settings');
    Route::resource('/settings', SettingController::class)->except(['index'])->names('dashboard.settings');
    Route::resource('/telegram-users', TelegramUserController::class)->parameters(['telegram-users' => 'telegramUser'])->names('dashboard.telegram-users');
    Route::resource('/transaction-drafts', TransactionDraftController::class)->parameters(['transaction-drafts' => 'transactionDraft'])->names('dashboard.transaction-drafts');
    Route::resource('/transaction-logs', TransactionLogController::class)->parameters(['transaction-logs' => 'transactionLog'])->names('dashboard.transaction-logs');
});

Route::post('/budget', [BudgetController::class, 'store'])
    ->middleware(['auth', 'throttle:budget'])
    ->name('dashboard.budget.store');

Route::post('/telegram/webhook/{secret}', TelegramWebhookController::class)
    ->middleware('throttle:telegram-webhook')
    ->name('telegram.webhook');
