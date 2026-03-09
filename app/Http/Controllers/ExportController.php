<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExportController extends Controller
{
    public function leads(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $filename  = 'leads_' . now()->format('Y-m-d_H-i') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($companyId) {
            $handle = fopen('php://output', 'w');

            // BOM para Excel reconhecer UTF-8
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Cabeçalho
            fputcsv($handle, ['Telefone', 'Cidade', 'Status', 'Origem', 'Primeiro Contato', 'Criado em'], ';');

            // CORRIGIDO: cursor() em vez de get() — processa um lead por vez,
            // sem carregar toda a coleção em memória. Essencial para exportações grandes.
            Lead::where('company_id', $companyId)
                ->latest()
                ->cursor()
                ->each(function ($lead) use ($handle) {
                    fputcsv($handle, [
                        $lead->phone,
                        $lead->city ?? '',
                        $lead->status,
                        $lead->source ?? '',
                        $lead->first_contact ? $lead->first_contact->format('d/m/Y H:i') : '',
                        $lead->created_at->format('d/m/Y H:i'),
                    ], ';');
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}