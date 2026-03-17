<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Conversation;
use App\Models\DailyMetric;
use App\Models\Followup;
use App\Models\Lead;
use App\Models\Product;
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

        // FIX: adicionado withoutGlobalScopes() em todas as queries.
        // Este job roda no queue worker onde Auth::check() retorna false.
        // O CompanyScope aplica whereRaw('1 = 0') quando não há usuário autenticado,
        // fazendo todas as contagens retornarem 0 silenciosamente sem erros.
        // withoutGlobalScopes() desativa o scope e o filtro manual de company_id
        // garante o isolamento multi-tenant.
        $leads = Lead::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereDate('created_at', $date)
            ->count();

        $conversations = Conversation::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereDate('created_at', $date)
            ->count();

        $recoveredLeads = Followup::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'recovered')
            ->whereDate('sent_at', $date)
            ->count();

        // Receita estimada: leads recuperados * ticket médio dos produtos
        $ticketMedio = Product::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->avg('avg_price') ?? 0;

        $estimatedRevenue = round($recoveredLeads * $ticketMedio, 2);

        DailyMetric::withoutGlobalScopes()->updateOrCreate(
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