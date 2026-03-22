<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Followup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class FunnelController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Lead::class);

        $companyId = Auth::user()->company_id;

        $statuses = [
            'novo'        => ['label' => 'Novos',          'color' => '#a855f7'],
            'em_conversa' => ['label' => 'Em conversa',    'color' => '#43e97b'],
            'pediu_preco' => ['label' => 'Pediram preço',  'color' => '#ffc107'],
            'encaminhado' => ['label' => 'Encaminhados',   'color' => '#0dcaf0'],
            'perdido'     => ['label' => 'Perdidos',       'color' => '#ff6584'],
            'recuperacao' => ['label' => 'Em recuperação', 'color' => '#ff9800'],
        ];

        // Cache de 5 minutos por empresa.
        // Invalidado pelo MetricsCacheService::invalidate() via funnel_data_{companyId}.
        $cached = Cache::remember("funnel_data_{$companyId}", now()->addMinutes(5), function () use ($companyId, $statuses) {
            $total   = Lead::where('company_id', $companyId)->count();
            $divisor = $total ?: 1;

            $counts = Lead::where('company_id', $companyId)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $funnel = [];
            foreach ($statuses as $key => $meta) {
                $count    = $counts->get($key, 0);
                $funnel[] = [
                    'status' => $key,
                    'label'  => $meta['label'],
                    'color'  => $meta['color'],
                    'count'  => $count,
                    'pct'    => round(($count / $divisor) * 100, 1),
                ];
            }

            $timeline = Lead::where('company_id', $companyId)
                ->selectRaw('DATE(created_at) as date, status, count(*) as total')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date', 'status')
                ->orderBy('date')
                ->get()
                ->groupBy('status');

            $recovered    = Followup::where('company_id', $companyId)->where('recovered', true)->count();
            $lost         = Lead::where('company_id', $companyId)->where('status', 'perdido')->count();
            $recoveryRate = $lost > 0 ? round(($recovered / $lost) * 100, 1) : 0;

            $leads_by_city = Lead::where('company_id', $companyId)
                ->whereNotNull('city')
                ->where('city', '!=', '')
                ->selectRaw('city, count(*) as count')
                ->groupBy('city')
                ->orderByDesc('count')
                ->limit(20)
                ->get();

            return compact('funnel', 'timeline', 'recovered', 'lost', 'recoveryRate', 'total', 'leads_by_city');
        });

        extract($cached);

        return view('funnel.index', compact('funnel', 'timeline', 'recovered', 'lost', 'recoveryRate', 'total', 'leads_by_city'));
    }
}