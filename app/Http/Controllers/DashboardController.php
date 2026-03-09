<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use App\Services\MetricsCacheService;
use App\Services\MetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $period    = $request->input('period', 'today');

        $cache = new MetricsCacheService(new MetricsService($companyId, $period));

        $data = $cache->getDashboard();

        // FIX B03: query de insights movida do template para o controller,
        // com filtro explícito de company_id e ordenação por created_at (não por UUID)
        $data['recent_insights'] = AiInsight::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->latest('created_at')
            ->limit(4)
            ->get();

        return view('dashboard.index', compact('data'));
    }
}