<?php

use App\Jobs\GenerateAiInsightJob;
use App\Jobs\UpdateDailyMetricsJob;
use App\Models\Company;
use Illuminate\Support\Facades\Schedule;

// Roda todo dia à meia-noite — atualiza métricas do dia anterior para todas as empresas
Schedule::call(function () {
    $yesterday = now()->subDay()->toDateString();

    Company::where('active', true)->each(function ($company) use ($yesterday) {
        UpdateDailyMetricsJob::dispatch($company, $yesterday);
    });
})->dailyAt('00:05')->name('update-daily-metrics')->withoutOverlapping();

// Roda todo dia às 8h — gera insights de IA para todas as empresas
Schedule::call(function () {
    Company::where('active', true)->each(function ($company) {
        GenerateAiInsightJob::dispatch($company);
    });
})->dailyAt('08:00')->name('generate-ai-insights')->withoutOverlapping();