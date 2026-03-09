<?php

namespace App\Services;

use App\Models\ChatSession;
use App\Models\Conversation;
use App\Models\Followup;
use App\Models\Lead;
use App\Models\Product;
use App\Models\ProductInterest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MetricsService
{
    // Taxa de conversão base (sem IA) e com IA.
    // ATENÇÃO: valores baseados em benchmarks do setor — NÃO são dados reais da empresa.
    // Implementar settings por empresa no futuro para personalizar essas taxas.
    private const CONVERSION_BASE    = 0.15;   // 15%
    private const CONVERSION_WITH_AI = 0.178;  // 17.8%

    protected string $companyId;
    protected string $period;

    public function __construct(string $companyId, string $period = 'today')
    {
        $this->companyId = $companyId;
        $this->period    = $period;
    }

    public function getCompanyId(): string { return $this->companyId; }
    public function getPeriod(): string    { return $this->period; }

    /**
     * Base query isolada por empresa.
     * Usa withoutGlobalScopes() pois CompanyScope depende de Auth::check(),
     * que pode ser false em contextos de service/job.
     */
    private function query(string $model): Builder
    {
        return $model::withoutGlobalScopes()
            ->where('company_id', $this->companyId);
    }

    protected function applyPeriod($query, string $column = 'created_at')
    {
        return match($this->period) {
            '7days'  => $query->where($column, '>=', now()->subDays(7)->startOfDay()),
            '30days' => $query->where($column, '>=', now()->subDays(30)->startOfDay()),
            default  => $query->whereDate($column, now()), // today
        };
    }

    public function dashboard(): array
    {
        return [
            'leads_today'         => $this->leadsToday(),
            'leads_month'         => $this->leadsMonth(),
            'ticket_average'      => $this->ticketAverage(),
            'transfer_rate'       => $this->transferRate(),
            'ai_response_rate'    => $this->aiResponseRate(),
            'avg_response_time'   => $this->avgResponseTime(),

            'leads_unique'        => $this->leadsUnique(),
            'leads_recurring'     => $this->leadsRecurring(),
            'leads_new'           => $this->leadsNew(),

            'funnel'              => $this->conversionFunnel(),
            'top_products'        => $this->topProducts(),
            'peak_hours'          => $this->peakHours(),

            'leads_lost'          => $this->leadsLost(),
            'leads_recovered'     => $this->leadsRecovered(),
            'recovery_rate'       => $this->recoveryRate(),
            'revenue_recovered'   => $this->revenueRecovered(),

            'revenue_estimated'   => $this->revenueEstimated(),
            'revenue_with_ai'     => $this->revenueWithAi(),
            'revenue_ai_impact'   => $this->revenueAiImpact(),

            // Flag para o template exibir aviso de que receita é estimativa de benchmark
            'revenue_is_estimate' => true,

            'leads_per_day'       => $this->leadsPerDay(),
            'leads_per_month'     => $this->leadsPerMonth(),
        ];
    }

    // ─── Cards principais ───────────────────────────────────────

    public function leadsToday(): int
    {
        return $this->applyPeriod($this->query(Lead::class))->count();
    }

    public function leadsMonth(): int
    {
        return $this->query(Lead::class)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    public function ticketAverage(): float
    {
        return round($this->query(Product::class)->avg('avg_price') ?? 0, 2);
    }

    public function transferRate(): float
    {
        $total = $this->query(ChatSession::class)->count();
        if ($total === 0) return 0;
        $transferred = $this->query(ChatSession::class)->where('transferred_to_human', true)->count();
        return round(($transferred / $total) * 100, 1);
    }

    public function aiResponseRate(): float
    {
        $total = $this->query(Conversation::class)->count();
        if ($total === 0) return 0;
        $bot = $this->query(Conversation::class)->where('sender', 'bot')->count();
        return round(($bot / $total) * 100, 1);
    }

    public function avgResponseTime(): float
    {
        $avgMs = $this->query(Conversation::class)
            ->where('sender', 'bot')
            ->whereNotNull('response_time')
            ->avg('response_time');

        return round(($avgMs ?? 0) / 1000, 2);
    }

    // ─── Leads únicos vs recorrentes ────────────────────────────

    /**
     * CORRIGIDO: leadsUnique respeita o período selecionado.
     * Antes ignorava o período e contava todos os leads históricos.
     */
    public function leadsUnique(): int
    {
        return $this->applyPeriod($this->query(Lead::class))
            ->distinct('phone')
            ->count('phone');
    }

    /**
     * CORRIGIDO: leadsNew conta leads cujo telefone aparece
     * pela PRIMEIRA VEZ no período — ou seja, nunca existiu antes dele.
     * Antes contava todos os leads do mês sem essa distinção.
     */
    public function leadsNew(): int
    {
        $periodStart = match($this->period) {
            '7days'  => now()->subDays(7)->startOfDay(),
            '30days' => now()->subDays(30)->startOfDay(),
            default  => now()->startOfDay(),
        };

        // Phones que aparecem no período mas NÃO existiam antes dele
        return $this->query(Lead::class)
            ->where('created_at', '>=', $periodStart)
            ->whereNotIn('phone', function ($sub) use ($periodStart) {
                $sub->select('phone')
                    ->from('leads')
                    ->where('company_id', $this->companyId)
                    ->where('created_at', '<', $periodStart)
                    ->whereNull('deleted_at');
            })
            ->distinct('phone')
            ->count('phone');
    }

    /**
     * CORRIGIDO: leadsRecurring respeita o período.
     * Conta phones que aparecem mais de uma vez dentro do período.
     */
    public function leadsRecurring(): int
    {
        return $this->applyPeriod($this->query(Lead::class))
            ->select('phone')
            ->groupBy('phone')
            ->havingRaw('count(*) > 1')
            ->get()
            ->count();
    }

    // ─── Funil de conversão ─────────────────────────────────────

    public function conversionFunnel(): array
    {
        $total = $this->query(Lead::class)->count();

        return [
            ['label' => 'Leads recebidos',     'value' => $total],
            ['label' => 'Responderam',          'value' => $this->query(Lead::class)->whereIn('status', ['em_conversa','pediu_preco','encaminhado','recuperacao'])->count()],
            ['label' => 'Interessados',         'value' => $this->query(Lead::class)->whereIn('status', ['pediu_preco','encaminhado','recuperacao'])->count()],
            ['label' => 'Pediram preço',        'value' => $this->query(Lead::class)->where('status', 'pediu_preco')->count()],
            ['label' => 'Encaminhados (venda)', 'value' => $this->query(Lead::class)->where('status', 'encaminhado')->count()],
        ];
    }

    // ─── Produtos ───────────────────────────────────────────────

    public function topProducts(): \Illuminate\Support\Collection
    {
        return ProductInterest::withoutGlobalScopes()
            ->select(
                'products.name',
                'products.avg_price',
                DB::raw('count(*) as total')
            )
            ->join('products', 'products.id', '=', 'product_interest.product_id')
            ->where('product_interest.company_id', $this->companyId)
            ->where('products.company_id', $this->companyId)
            ->groupBy('products.name', 'products.avg_price')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
    }

    // ─── Horários de pico ───────────────────────────────────────

    public function peakHours(): \Illuminate\Support\Collection
    {
        return $this->query(Conversation::class)
            ->selectRaw('HOUR(created_at) as hour, count(*) as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(fn($row) => [
                'hour'  => str_pad($row->hour, 2, '0', STR_PAD_LEFT) . 'h',
                'total' => $row->total,
            ]);
    }

    // ─── Recuperação de leads ────────────────────────────────────

    public function leadsLost(): int
    {
        return $this->query(Lead::class)->where('status', 'perdido')->count();
    }

    public function leadsRecovered(): int
    {
        return $this->query(Followup::class)->where('recovered', true)->count();
    }

    public function recoveryRate(): float
    {
        $lost = $this->leadsLost();
        if ($lost === 0) return 0;
        return round(($this->leadsRecovered() / $lost) * 100, 1);
    }

    public function revenueRecovered(): float
    {
        return round($this->leadsRecovered() * $this->ticketAverage(), 2);
    }

    // ─── Receita estimada (benchmark, não dados reais) ───────────

    public function revenueEstimated(): float
    {
        return round($this->leadsMonth() * self::CONVERSION_BASE * $this->ticketAverage(), 2);
    }

    public function revenueWithAi(): float
    {
        return round($this->leadsMonth() * self::CONVERSION_WITH_AI * $this->ticketAverage(), 2);
    }

    public function revenueAiImpact(): float
    {
        return round($this->revenueWithAi() - $this->revenueEstimated(), 2);
    }

    // ─── Histórico ───────────────────────────────────────────────

    public function leadsPerDay(): \Illuminate\Support\Collection
    {
        // FIX: aplica o periodo selecionado para o grafico ser consistente com os demais cards
        return $this->applyPeriod($this->query(Lead::class))
            ->selectRaw('DATE(created_at) as date, count(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->limit(30)
            ->get();
    }

    public function leadsPerMonth(): \Illuminate\Support\Collection
    {
        return $this->query(Lead::class)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, count(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->limit(12)
            ->get();
    }
}