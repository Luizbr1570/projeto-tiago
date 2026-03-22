<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Services\MetricsCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    private function applyPeriod(\Illuminate\Database\Eloquent\Builder $query, string $period): \Illuminate\Database\Eloquent\Builder
    {
        return match($period) {
            'today'  => $query->whereDate('sold_at', now()),
            '7days'  => $query->where('sold_at', '>=', now()->subDays(7)->startOfDay()),
            '30days' => $query->where('sold_at', '>=', now()->subDays(30)->startOfDay()),
            default  => $query,
        };
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Sale::class);

        $companyId = Auth::user()->company_id;
        $period    = $request->input('period', 'all');

        $query = $this->applyPeriod(
            Sale::where('company_id', $companyId)->with(['lead' => fn($q) => $q->withTrashed(), 'product'])->latest('sold_at'),
            $period
        );

        $total_value = (clone $query)->sum('value');
        $total_count = (clone $query)->count();

        $best_day = Sale::where('company_id', $companyId)
            ->selectRaw('DATE(sold_at) as day, SUM(value) as total')
            ->groupBy('day')
            ->orderByDesc('total')
            ->first();

        $sales_per_day = $this->applyPeriod(Sale::where('company_id', $companyId), $period === 'all' ? '30days' : $period)
            ->selectRaw('DATE(sold_at) as day, COUNT(*) as count, SUM(value) as total')
            ->groupBy('day')->orderBy('day')->get();

        $cacheKey = "sales_analytics_{$companyId}";
        $analytics = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(10), function () use ($companyId) {
            $sales_by_product = DB::table('sales')
                ->join('products', 'products.id', '=', 'sales.product_id')
                ->selectRaw('products.name, COUNT(*) as count, SUM(sales.value) as total')
                ->where('sales.company_id', $companyId)
                ->whereNotNull('sales.product_id')
                ->whereNull('sales.deleted_at')
                ->groupBy('products.name')
                ->orderByDesc('total')
                ->limit(8)
                ->get();

            $sales_by_category = DB::table('sales')
                ->join('products', 'products.id', '=', 'sales.product_id')
                ->selectRaw('products.category, COUNT(*) as count, SUM(sales.value) as total')
                ->where('sales.company_id', $companyId)
                ->whereNotNull('sales.product_id')
                ->whereNotNull('products.category')
                ->whereNull('sales.deleted_at')
                ->groupBy('products.category')
                ->orderByDesc('total')
                ->get();

            $sales_by_source = DB::table('sales')
                ->join('leads', 'leads.id', '=', 'sales.lead_id')
                ->selectRaw('leads.source, COUNT(*) as count, SUM(sales.value) as total')
                ->where('sales.company_id', $companyId)
                ->whereNotNull('leads.source')
                ->whereNull('sales.deleted_at')
                ->groupBy('leads.source')
                ->orderByDesc('total')
                ->get();

            $top_leads = DB::table('sales')
                ->join('leads', 'leads.id', '=', 'sales.lead_id')
                ->selectRaw('leads.id, leads.phone, leads.city, COUNT(*) as count, SUM(sales.value) as total')
                ->where('sales.company_id', $companyId)
                ->whereNull('sales.deleted_at')
                ->groupBy('leads.id', 'leads.phone', 'leads.city')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            $driver = DB::getDriverName();
            $hourExpr = $driver === 'pgsql'
                ? 'EXTRACT(HOUR FROM sold_at)::integer as hour'
                : 'HOUR(sold_at) as hour';

            $sales_by_hour = DB::table('sales')
                ->selectRaw("{$hourExpr}, COUNT(*) as count, SUM(value) as total")
                ->where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->map(fn($r) => [
                    'hour'  => str_pad($r->hour, 2, '0', STR_PAD_LEFT) . 'h',
                    'count' => $r->count,
                    'total' => $r->total,
                ]);

            $sales_by_city = DB::table('sales')
                ->join('leads', 'leads.id', '=', 'sales.lead_id')
                ->selectRaw('leads.city, COUNT(*) as count, SUM(sales.value) as total')
                ->where('sales.company_id', $companyId)
                ->whereNotNull('leads.city')
                ->whereNull('sales.deleted_at')
                ->groupBy('leads.city')
                ->orderByDesc('total')
                ->limit(50)
                ->get();

            return compact(
                'sales_by_product', 'sales_by_category',
                'sales_by_source', 'top_leads', 'sales_by_hour', 'sales_by_city'
            );
        });

        extract($analytics);

        $vendido_mes = Sale::where('company_id', $companyId)
            ->whereMonth('sold_at', now()->month)
            ->whereYear('sold_at', now()->year)
            ->sum('value');
        $avg_daily = Sale::where('company_id', $companyId)
            ->where('sold_at', '>=', now()->subDays(30))
            ->selectRaw('SUM(value) / 30 as avg')
            ->value('avg') ?? 0;
        $meta_mes  = round($avg_daily * now()->daysInMonth, 2);
        $meta_pct  = $meta_mes > 0 ? min(100, round(($vendido_mes / $meta_mes) * 100, 1)) : 0;

        $sales    = $query->paginate(20)->withQueryString();
        $products = Product::where('company_id', $companyId)->orderBy('name')->get();

        return view('sales.index', compact(
            'sales', 'period', 'total_value', 'total_count', 'best_day',
            'sales_per_day', 'products', 'sales_by_product', 'sales_by_category',
            'sales_by_source', 'top_leads', 'sales_by_hour',
            'meta_mes', 'vendido_mes', 'meta_pct', 'sales_by_city'
        ));
    }

    public function store(Request $request, string $leadId)
    {
        $this->authorize('create', Sale::class);

        $companyId = Auth::user()->company_id;

        $lead = \App\Models\Lead::where('company_id', $companyId)->findOrFail($leadId);

        $request->validate([
            'product_id' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('products', 'id')
                    ->where('company_id', $companyId),
            ],
            'quantity'   => 'nullable|integer|min:1|max:9999',
            'value'      => 'required|numeric|min:0.01',
            'notes'      => 'nullable|string|max:255',
        ]);

        Sale::create([
            'company_id' => $companyId,
            'lead_id'    => $lead->id,
            'product_id' => $request->product_id,
            'quantity'   => $request->quantity ?? 1,
            'value'      => $request->value,
            'notes'      => $request->notes,
            'sold_at'    => now(),
        ]);

        MetricsCacheService::invalidate($companyId);

        return back()->with('success', 'Venda registrada com sucesso!');
    }

    public function update(Request $request, string $id)
    {
        $sale = Sale::where('company_id', Auth::user()->company_id)->findOrFail($id);

        $this->authorize('update', $sale);

        $request->validate([
            'product_id' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('products', 'id')
                    ->where('company_id', Auth::user()->company_id),
            ],
            'quantity'   => 'nullable|integer|min:1|max:9999',
            'value'      => 'required|numeric|min:0.01',
            'notes'      => 'nullable|string|max:255',
        ]);

        $sale->update([
            'product_id' => $request->product_id,
            'quantity'   => $request->quantity ?? 1,
            'value'      => $request->value,
            'notes'      => $request->notes,
        ]);

        MetricsCacheService::invalidate(Auth::user()->company_id);

        return back()->with('success', 'Venda atualizada!');
    }

    public function destroy(string $id)
    {
        $sale = Sale::where('company_id', Auth::user()->company_id)->findOrFail($id);

        $this->authorize('delete', $sale);
        $sale->delete();

        MetricsCacheService::invalidate(Auth::user()->company_id);

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Venda removida']);
        }

        return back()->with('success', 'Venda removida.');
    }

    public function restore(string $id)
    {
        $sale = Sale::withoutGlobalScopes()
            ->onlyTrashed()
            ->where('company_id', Auth::user()->company_id)
            ->findOrFail($id);

        $this->authorize('restore', $sale);

        $sale->restore();

        MetricsCacheService::invalidate(Auth::user()->company_id);

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Venda restaurada']);
        }

        return back()->with('success', '✅ Venda restaurada com sucesso.');
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Sale::class);

        $companyId = Auth::user()->company_id;
        $period    = $request->input('period', 'all');

        $query = $this->applyPeriod(
            Sale::where('company_id', $companyId)->with(['lead' => fn($q) => $q->withTrashed(), 'product'])->latest('sold_at'),
            $period
        );

        $filename = 'vendas_' . now()->format('Y-m-d') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['Lead (telefone)', 'Cidade', 'Produto', 'Categoria', 'Quantidade', 'Valor (R$)', 'Observação', 'Data'], ';');
            $query->cursor()->each(function ($sale) use ($file) {
                fputcsv($file, [
                    $sale->lead->phone       ?? 'Lead removido',
                    $sale->lead->city        ?? '—',
                    $sale->product->name     ?? '—',
                    $sale->product->category ?? '—',
                    $sale->quantity          ?? 1,
                    number_format($sale->value, 2, ',', '.'),
                    $sale->notes             ?? '',
                    $sale->sold_at->format('d/m/Y H:i'),
                ], ';');
            });
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}