<?php

namespace App\Http\Controllers;

use App\Models\DailyMetric;
use App\Services\MetricsCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DailyMetricController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', DailyMetric::class);

        $metrics = DailyMetric::where('company_id', Auth::user()->company_id)
            ->orderByDesc('date')
            ->paginate(30);

        return view('metrics.index', compact('metrics'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', DailyMetric::class);

        $validado = $request->validate([
            'date'              => 'required|date|before_or_equal:today',
            'leads'             => 'integer|min:0',
            'conversations'     => 'integer|min:0',
            'recovered_leads'   => 'integer|min:0',
            'estimated_revenue' => 'numeric|min:0',
        ]);

        $validado['company_id'] = Auth::user()->company_id;

        DailyMetric::updateOrCreate(
            ['date' => $validado['date'], 'company_id' => $validado['company_id']],
            $validado
        );

        // Invalida cache do dashboard para refletir as novas metricas imediatamente
        MetricsCacheService::invalidate(Auth::user()->company_id);

        return back()->with('success', 'Métrica salva');
    }
}