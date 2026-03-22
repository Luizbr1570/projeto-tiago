<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Lead::class);

        $query = Lead::where('company_id', Auth::user()->company_id)->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('source', 'like', "%{$search}%");
            });
        }

        $leads = $query->paginate(20)->withQueryString();

        return view('leads.index', compact('leads'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Lead::class);

        $validado = $request->validate([
            'phone'  => [
                'required',
                'string',
                \Illuminate\Validation\Rule::unique('leads')->where('company_id', Auth::user()->company_id),
            ],
            'city'   => 'nullable|string',
            'source' => 'nullable|string'
        ]);

        Lead::create($validado);

        \App\Services\MetricsCacheService::invalidate(Auth::user()->company_id);

        return redirect()->back()->with('success', 'Lead criado');
    }

    public function show(string $id)
    {
        $lead = Lead::withTrashed()
            ->where('company_id', Auth::user()->company_id)
            ->with(['productInterests.product', 'sales'])
            ->findOrFail($id);

        $this->authorize('view', $lead);

        $conversations = $lead->conversations()
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        // Produtos da empresa para o modal de registrar venda
        $products = Product::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get();

        return view('leads.show', compact('lead', 'conversations', 'products'));
    }

    public function update(Request $request, string $id)
    {
        $lead = Lead::where('company_id', Auth::user()->company_id)->findOrFail($id);

        $this->authorize('update', $lead);

        $lead->update($request->validate([
            'status' => 'required|in:novo,em_conversa,pediu_preco,encaminhado,perdido,recuperacao'
        ]));

        \App\Services\MetricsCacheService::invalidate(Auth::user()->company_id);

        return back()->with('success', 'Lead atualizado');
    }

    public function destroy(string $id)
    {
        $lead = Lead::where('company_id', Auth::user()->company_id)->findOrFail($id);

        $this->authorize('delete', $lead);

        $lead->delete();

        \App\Services\MetricsCacheService::invalidate(Auth::user()->company_id);

        return back()->with('success', 'Lead removido');
    }

    public function restore(string $id)
    {
        $lead = Lead::withoutGlobalScopes()
            ->onlyTrashed()
            ->where('company_id', Auth::user()->company_id)
            ->findOrFail($id);

        $this->authorize('restore', $lead);

        $lead->restore();

        \App\Services\MetricsCacheService::invalidate(Auth::user()->company_id);

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Lead restaurado']);
        }

        return back()->with('success', '✅ Lead restaurado com sucesso.');
    }
}