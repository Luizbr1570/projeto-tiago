<?php

use App\Http\Controllers\AIInsightController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatSessionController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DailyMetricController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FollowupController;
use App\Http\Controllers\FunnelController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\MetaEmbeddedSignupController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductInterestController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

// Rotas públicas
Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => view('auth.login'))->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('auth.login');

    Route::get('/register', fn () => view('auth.register'))->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,60')->name('auth.register');
});

// Logout
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Rotas protegidas — todos os usuários autenticados
Route::middleware(['auth', 'company.active'])->group(function () {

    // FIX B02 (parcial): redirect da raiz para o dashboard
    Route::get('/', fn () => redirect()->route('dashboard'));

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Funil
    Route::get('/funnel', [FunnelController::class, 'index'])->name('funnel.index');

    // Configurações — perfil e senha acessíveis a qualquer role autenticado
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::patch('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');

    // Leads — admin e agent
    Route::middleware('role:admin,agent')->group(function () {
        Route::resource('leads', LeadController::class);
        Route::resource('conversations', ConversationController::class)
            ->only(['index', 'store', 'show', 'destroy']);
        Route::get('/chat-sessions', [ChatSessionController::class, 'index'])->name('chat-sessions.index');
        Route::post('/chat-sessions', [ChatSessionController::class, 'store'])->name('chat-sessions.store');
        Route::patch('/chat-sessions/{id}/transfer', [ChatSessionController::class, 'transfer'])->name('chat-sessions.transfer');
        Route::patch('/chat-sessions/{id}/close', [ChatSessionController::class, 'close'])->name('chat-sessions.close');
    });

    // Rotas só para admin
    Route::middleware('role:admin')->group(function () {

        // FIX B02: export movido para dentro do grupo admin
        // Antes estava no grupo geral — qualquer autenticado podia exportar leads
        Route::get('/export/leads', [ExportController::class, 'leads'])->middleware('throttle:10,60')->name('export.leads');

        // FIX B02 (parcial): settings.company restrito a admin
        // Antes qualquer role podia alterar o nome da empresa
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
            Route::get('/latest', [MetaEmbeddedSignupController::class, 'latest'])->name('latest');
            Route::get('/sessions', [MetaEmbeddedSignupController::class, 'sessions'])->name('sessions');
        });
    });
});
