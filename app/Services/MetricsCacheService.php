<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class MetricsCacheService
{
    public function __construct(private MetricsService $metrics) {}

    public function getDashboard(): array
    {
        $companyId = $this->metrics->getCompanyId();
        $period    = $this->metrics->getPeriod();

        return Cache::remember(
            "dashboard_metrics_{$companyId}_{$period}",
            now()->addMinutes(5),
            fn () => $this->metrics->dashboard()
        );
    }

    /**
     * Invalida o cache do dashboard para uma empresa.
     * Deve ser chamado apos operacoes que alterem dados (leads, followups, insights).
     * Uso: MetricsCacheService::invalidate($companyId);
     */
    public static function invalidate(string $companyId): void
    {
        foreach (['today', '7days', '30days'] as $period) {
            Cache::forget("dashboard_metrics_{$companyId}_{$period}");
        }
    }
}