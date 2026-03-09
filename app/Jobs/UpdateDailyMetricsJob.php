<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Conversation;
use App\Models\DailyMetric;
use App\Models\Followup;
use App\Models\Lead;
use App\Models\ProductInterest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateDailyMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private Company $company,
        private string $date // formato: Y-m-d
    ) {}

    public function handle(): void
    {
        $companyId = $this->company->id;
        $date = $this->date;

        $leads = Lead::where('company_id', $companyId)
            ->whereDate('created_at', $date)
            ->count();

        $conversations = Conversation::where('company_id', $companyId)
            ->whereDate('created_at', $date)
            ->count();

        $recoveredLeads = Followup::where('company_id', $companyId)
            ->where('status', 'recovered')
            ->whereDate('sent_at', $date)
            ->count();

        // Receita estimada: leads recuperados * ticket médio dos produtos
        $ticketMedio = \App\Models\Product::where('company_id', $companyId)
            ->avg('avg_price') ?? 0;

        $estimatedRevenue = round($recoveredLeads * $ticketMedio, 2);

        DailyMetric::updateOrCreate(
            [
                'company_id' => $companyId,
                'date'       => $date,
            ],
            [
                'leads'             => $leads,
                'conversations'     => $conversations,
                'recovered_leads'   => $recoveredLeads,
                'estimated_revenue' => $estimatedRevenue,
            ]
        );

        Log::info("UpdateDailyMetricsJob: métricas atualizadas para empresa {$companyId} em {$date}");
    }

    public function failed(\Throwable $e): void
    {
        Log::error("UpdateDailyMetricsJob falhou para empresa {$this->company->id}: {$e->getMessage()}");
    }
}
