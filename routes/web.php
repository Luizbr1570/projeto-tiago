<?php

use App\Http\Controllers\AIInsightController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatSessionController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DailyMetricController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FollowupController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\FunnelController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\MetaEmbeddedSignupController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductInterestController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => view('auth.login'))->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('auth.login');

    Route::get('/register', fn () => view('auth.register'))->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,60')->name('auth.register');

    // Esqueci a senha
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.email');

    // Redefinir senha
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'company.active'])->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/funnel', [FunnelController::class, 'index'])->name('funnel.index');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::patch('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');

    Route::middleware('role:admin,agent')->group(function () {
        Route::resource('leads', LeadController::class);
        Route::resource('conversations', ConversationController::class)
            ->only(['index', 'store', 'show', 'destroy']);
        Route::get('/chat-sessions', [ChatSessionController::class, 'index'])->name('chat-sessions.index');
        Route::post('/chat-sessions', [ChatSessionController::class, 'store'])->name('chat-sessions.store');
        Route::patch('/chat-sessions/{id}/transfer', [ChatSessionController::class, 'transfer'])->name('chat-sessions.transfer');
        Route::patch('/chat-sessions/{id}/close', [ChatSessionController::class, 'close'])->name('chat-sessions.close');
        Route::post('/leads/{id}/restore', [LeadController::class, 'restore'])->name('leads.restore');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/export/leads', [ExportController::class, 'leads'])->middleware('throttle:10,60')->name('export.leads');
        Route::patch('/settings/company', [SettingsController::class, 'updateCompany'])->name('settings.company');

        Route::resource('products', ProductController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('product-interests', ProductInterestController::class)
            ->only(['index', 'store', 'destroy']);

        Route::get('/followups', [FollowupController::class, 'index'])->name('followups.index');
        Route::post('/followups', [FollowupController::class, 'store'])->name('followups.store');
        Route::patch('/followups/{id}', [FollowupController::class, 'update'])->name('followups.update');
        Route::delete('/followups/{id}', [FollowupController::class, 'destroy'])->name('followups.destroy');

        Route::get('/insights', [AIInsightController::class, 'index'])->name('insights.index');
        Route::post('/insights', [AIInsightController::class, 'store'])->name('insights.store');
        Route::delete('/insights/{id}', [AIInsightController::class, 'destroy'])->name('insights.destroy');

        Route::get('/metrics', [DailyMetricController::class, 'index'])->name('metrics.index');
        Route::post('/metrics', [DailyMetricController::class, 'store'])->name('metrics.store');

        Route::get('/admin/meta/embedded-signup', [MetaEmbeddedSignupController::class, 'index'])
            ->name('admin.meta.embedded-signup.index');
        Route::get('/admin/meta/embedded-signup/callback', [MetaEmbeddedSignupController::class, 'callback'])
            ->name('admin.meta.embedded-signup.callback');
        Route::post('/admin/meta/embedded-signup/config', [MetaEmbeddedSignupController::class, 'saveConfig'])
            ->name('admin.meta.embedded-signup.config.save');

        Route::prefix('/api/meta/embedded-signup')->name('api.meta.embedded-signup.')->group(function () {
            Route::post('/session', [MetaEmbeddedSignupController::class, 'storeSession'])->name('session.store');
            Route::post('/exchange-code', [MetaEmbeddedSignupController::class, 'exchangeCode'])->name('exchange-code');
            Route::get('/latest', [MetaEmbeddedSignupController::class, 'latest'])->name('latest');
            Route::get('/sessions', [MetaEmbeddedSignupController::class, 'sessions'])->name('sessions');
        });

        Route::post('/products/{id}/restore',      [ProductController::class,      'restore'])->name('products.restore');
        Route::post('/conversations/{id}/restore', [ConversationController::class,  'restore'])->name('conversations.restore');
        Route::post('/followups/{id}/restore',     [FollowupController::class,      'restore'])->name('followups.restore');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{id}/password', [UserController::class, 'resetPassword'])->name('users.password');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');

        Route::post('/insights/{id}/restore', [AIInsightController::class, 'restore'])->name('insights.restore');

        // Vendas
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/export', [SaleController::class, 'export'])->middleware('throttle:10,60')->name('sales.export');
        Route::post('/leads/{lead}/sales', [SaleController::class, 'store'])->middleware('throttle:30,1')->name('sales.store');
        Route::patch('/sales/{sale}', [SaleController::class, 'update'])->middleware('throttle:30,1')->name('sales.update');
        Route::delete('/sales/{sale}', [SaleController::class, 'destroy'])->middleware('throttle:30,1')->name('sales.destroy');
        Route::post('/sales/{sale}/restore', [SaleController::class, 'restore'])->middleware('throttle:30,1')->name('sales.restore');
    });
});