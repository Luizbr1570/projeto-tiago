<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Followup;
use Illuminate\Support\Facades\Auth;

class FunnelController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;

        $statuses = [
            'novo'        => ['label' => 'Novos',              'color' => '#a855f7'],
            'em_conversa' => ['label' => 'Em conversa',        'color' => '#43e97b'],
            'pediu_preco' => ['label' => 'Pediram preço',      'color' => '#ffc107'],
            'encaminhado' => ['label' => 'Encaminhados',       'color' => '#0dcaf0'],
            'perdido'     => ['label' => 'Perdidos',           'color' => '#ff6584'],
            'recuperacao' => ['label' => 'Em recuperação',     'color' => '#ff9800'],
        ];

        $total = Lead::where('company_id', $companyId)->count();
        $divisor = $total ?: 1; // evita divisao por zero mas preserva $total real para exibicao

        // Uma única query agrupa todos os status de uma vez — evita N queries no loop
        $counts = Lead::where('company_id', $companyId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $funnel = [];
        foreach ($statuses as $key => $meta) {
            $count = $counts->get($key, 0);
            $funnel[] = [
                'status' => $key,
                'label'  => $meta['label'],
                'color'  => $meta['color'],
                'count'  => $count,
                'pct'    => round(($count / $divisor) * 100, 1),
            ];
        }

        // Leads por status ao longo dos últimos 30 dias
        $timeline = Lead::where('company_id', $companyId)
            ->selectRaw('DATE(created_at) as date, status, count(*) as total')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get()
            ->groupBy('status');

        $recovered = Followup::where('company_id', $companyId)->where('recovered', true)->count();
        $lost      = Lead::where('company_id', $companyId)->where('status', 'perdido')->count();
        $recoveryRate = $lost > 0 ? round(($recovered / $lost) * 100, 1) : 0;

        return view('funnel.index', compact('funnel', 'timeline', 'recovered', 'lost', 'recoveryRate', 'total'));
    }
}