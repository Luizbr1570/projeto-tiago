<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use App\Services\MetricsCacheService;
use App\Services\MetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;


class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $period    = $request->input('period', 'today');

        $cache = new MetricsCacheService(new MetricsService($companyId, $period));

        $data = $cache->getDashboard();

        // Insights em cache separado (5 min) para ser invalidado junto com
        // as métricas pelo MetricsCacheService::invalidate(). Sem isso,
        // insights novos gerados pelo job demoram até 5 min para aparecer.
        $data['recent_insights'] = Cache::remember(
            "recent_insights_{$companyId}",
            now()->addMinutes(5),
            fn () => AiInsight::where('company_id', $companyId)
                ->latest('created_at')
                ->limit(4)
                ->get()
        );

        return view('dashboard.index', compact('data', 'period'));
    }
}