<?php

namespace App\Services;

use App\Models\ChatSession;
use App\Models\Conversation;
use App\Models\Followup;
use App\Models\Lead;
use App\Models\Product;
use App\Models\ProductInterest;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MetricsService
{
    // Fallbacks usados quando a empresa não tem taxas configuradas.
    // Os valores reais são lidos de companies.conversion_base / conversion_with_ai.
    private const CONVERSION_BASE_DEFAULT    = 0.15;
    private const CONVERSION_WITH_AI_DEFAULT = 0.178;

    protected string $companyId;
    protected string $period;
    protected ?float $conversionBase   = null;
    protected ?float $conversionWithAi = null;

    public function __construct(string $companyId, string $period = 'today')
    {
        $this->companyId = $companyId;
        $this->period    = $period;

        // Carrega as taxas configuradas pela empresa (sem GlobalScope pois pode
        // ser chamado de contexto sem Auth — ex: jobs ou testes).
        $company = \App\Models\Company::withoutGlobalScopes()->find($companyId);
        $this->conversionBase   = $company?->conversion_base   ?? self::CONVERSION_BASE_DEFAULT;
        $this->conversionWithAi = $company?->conversion_with_ai ?? self::CONVERSION_WITH_AI_DEFAULT;
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

            // true = exibe aviso de estimativa, false = dados reais de vendas
            'revenue_is_estimate' => !$this->hasRealRevenue(),

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

    // Alias de leadsToday() — ambos aplicam applyPeriod ao count de leads.
    // Usado internamente nos cálculos de receita para deixar a intenção clara.
    private function leadsInPeriod(): int
    {
        return $this->leadsToday();
    }

    public function ticketAverage(): float
    {
        // Usa média de TODAS as vendas históricas — igual ao cálculo da página de vendas.
        // Não filtra por período para manter consistência entre dashboard e página de vendas.
        $avg = $this->query(Sale::class)->avg('value');
        if ($avg !== null) {
            return round($avg, 2);
        }
        // Fallback: média dos preços dos produtos enquanto não há vendas
        return round($this->query(Product::class)->avg('avg_price') ?? 0, 2);
    }

    public function transferRate(): float
    {
        $total = $this->applyPeriod($this->query(ChatSession::class), 'started_at')->count();
        if ($total === 0) return 0;
        $transferred = $this->applyPeriod($this->query(ChatSession::class), 'started_at')
            ->where('transferred_to_human', true)
            ->count();
        return round(($transferred / $total) * 100, 1);
    }

    public function aiResponseRate(): float
    {
        $total = $this->applyPeriod($this->query(Conversation::class))->count();
        if ($total === 0) return 0;
        $bot = $this->applyPeriod($this->query(Conversation::class))->where('sender', 'bot')->count();
        return round(($bot / $total) * 100, 1);
    }

    public function avgResponseTime(): float
    {
        $avgMs = $this->applyPeriod($this->query(Conversation::class))
            ->where('sender', 'bot')
            ->whereNotNull('response_time')
            ->avg('response_time');

        return round(($avgMs ?? 0) / 1000, 2);
    }

    // ─── Leads únicos vs recorrentes ────────────────────────────

    public function leadsUnique(): int
    {
        return $this->applyPeriod($this->query(Lead::class))
            ->distinct('phone')
            ->count('phone');
    }

    public function leadsNew(): int
    {
        $periodStart = match($this->period) {
            '7days'  => now()->subDays(7)->startOfDay(),
            '30days' => now()->subDays(30)->startOfDay(),
            default  => now()->startOfDay(),
        };

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

    public function leadsRecurring(): int
    {
        // FIX: era ->get()->count() — carregava todos os registros em memória.
        // Agora conta direto no banco via subquery.
        $sub = $this->applyPeriod($this->query(Lead::class))
            ->select('leads.phone')
            ->groupBy('leads.phone')
            ->havingRaw('count(leads.id) > 1')
            ->toBase();

        return (int) DB::table($sub, 'sub')->count();
    }

    // ─── Funil de conversão ─────────────────────────────────────

    public function conversionFunnel(): array
    {
        // FIX: era 5 queries separadas — agora é 1 query com groupBy status.
        $counts = $this->applyPeriod($this->query(Lead::class))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total       = $counts->sum();
        $responderam = ($counts->get('em_conversa', 0) + $counts->get('pediu_preco', 0) + $counts->get('encaminhado', 0) + $counts->get('recuperacao', 0));
        $interessados = ($counts->get('pediu_preco', 0) + $counts->get('encaminhado', 0) + $counts->get('recuperacao', 0));

        return [
            ['label' => 'Leads recebidos',     'value' => $total],
            ['label' => 'Responderam',          'value' => $responderam],
            ['label' => 'Interessados',         'value' => $interessados],
            ['label' => 'Pediram preço',        'value' => $counts->get('pediu_preco', 0)],
            ['label' => 'Encaminhados (venda)', 'value' => $counts->get('encaminhado', 0)],
        ];
    }

    // ─── Produtos ───────────────────────────────────────────────

    public function topProducts(): \Illuminate\Support\Collection
    {
        $query = ProductInterest::withoutGlobalScopes()
            ->select('products.name', 'products.avg_price', DB::raw('count(*) as total'))
            ->join('products', 'products.id', '=', 'product_interest.product_id')
            ->where('product_interest.company_id', $this->companyId)
            ->where('products.company_id', $this->companyId)
            ->groupBy('products.name', 'products.avg_price')
            ->orderByDesc('total')
            ->limit(5);

        // FIX: applyPeriod retorna a query — o retorno precisa ser capturado.
        // Antes o resultado era descartado e o filtro de período nunca era aplicado.
        $query = $this->applyPeriod($query, 'product_interest.created_at');

        return $query->get();
    }

    // ─── Horários de pico ───────────────────────────────────────

    public function peakHours(): \Illuminate\Support\Collection
    {
        $driver   = DB::getDriverName();
        $hourExpr = $driver === 'sqlite'
            ? "CAST(strftime('%H', conversations.created_at) AS INTEGER) as hour"
            : 'HOUR(conversations.created_at) as hour';

        return $this->applyPeriod($this->query(Conversation::class), 'conversations.created_at')
            ->selectRaw($hourExpr . ', count(*) as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(fn($row) => [
                'hour'  => str_pad((int) $row->hour, 2, '0', STR_PAD_LEFT) . 'h',
                'total' => $row->total,
            ]);
    }

    // ─── Recuperação de leads ────────────────────────────────────

    public function leadsLost(): int
    {
        // Usa updated_at para capturar leads que mudaram para "perdido" no período,
        // não created_at (que seria quando o lead foi criado, não quando foi perdido).
        return $this->applyPeriod($this->query(Lead::class), 'updated_at')
            ->where('status', 'perdido')
            ->count();
    }

    public function leadsRecovered(): int
    {
        // sent_at é quando o followup foi enviado/marcado como recuperado
        return $this->applyPeriod($this->query(Followup::class), 'sent_at')
            ->where('recovered', true)
            ->whereNotNull('sent_at')
            ->count();
    }

    public function recoveryRate(): float
    {
        $lost = $this->leadsLost();
        if ($lost === 0) return 0;
        return round(($this->leadsRecovered() / $lost) * 100, 1);
    }

    public function revenueRecovered(): float
    {
        // Receita estimada dos leads recuperados no período × ticket médio real
        return round($this->leadsRecovered() * $this->ticketAverage(), 2);
    }

    // ─── Receita ─────────────────────────────────────────────────

    public function revenueReal(): float
    {
        return round(
            (float) $this->applyPeriod($this->query(Sale::class), 'sold_at')->sum('value'),
            2
        );
    }

    public function hasRealRevenue(): bool
    {
        // Verifica se há vendas reais no período selecionado, não no histórico completo.
        // Sem isso, períodos sem venda exibiriam "dados reais" incorretamente.
        return $this->applyPeriod($this->query(Sale::class), 'sold_at')->exists();
    }

    public function revenueEstimated(): float
    {
        return round($this->leadsInPeriod() * $this->conversionBase * $this->ticketAverage(), 2);
    }

    public function revenueWithAi(): float
    {
        $real = $this->revenueReal();
        return $real > 0
            ? $real
            : round($this->leadsInPeriod() * $this->conversionWithAi * $this->ticketAverage(), 2);
    }

    public function revenueAiImpact(): float
    {
        // Quando há vendas reais, o impacto da IA é a diferença entre o real e a estimativa sem IA.
        // Quando não há vendas reais, é a diferença entre as duas estimativas.
        $real = $this->revenueReal();
        if ($real > 0) {
            return round($real - $this->revenueEstimated(), 2);
        }
        return round($this->revenueWithAi() - $this->revenueEstimated(), 2);
    }

    // ─── Histórico ───────────────────────────────────────────────

    public function leadsPerDay(): \Illuminate\Support\Collection
    {
        return $this->applyPeriod($this->query(Lead::class))
            ->selectRaw("DATE(created_at) as date, count(*) as total")
            ->groupBy('date')
            ->orderBy('date')
            ->limit(30)
            ->get();
    }

    public function leadsPerMonth(): \Illuminate\Support\Collection
    {
        $driver     = DB::getDriverName();
        $monthExpr  = $driver === 'sqlite'
            ? "strftime('%Y-%m', created_at) as month"
            : "DATE_FORMAT(created_at, '%Y-%m') as month";

        return $this->query(Lead::class)
            ->selectRaw("{$monthExpr}, count(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->limit(12)
            ->get();
    }
}