<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Conversation::class);

        // FIX #1: adicionado where company_id para garantir isolamento multi-tenant.
        // Sem esse filtro, o CompanyScope é a única barreira — se o model não usar
        // BelongsToCompany, todas as conversas de todos os tenants seriam expostas.
        $query = Conversation::where('company_id', Auth::user()->company_id)
            ->with('lead')
            ->latest('created_at');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('lead', function ($q2) use ($search) {
                    $q2->where('phone', 'like', "%{$search}%");
                })->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($request->filled('sender') && in_array($request->input('sender'), ['lead', 'bot', 'human'])) {
            $query->where('sender', $request->input('sender'));
        }

        $conversations = $query->paginate(20)->withQueryString();

        return view('conversations.index', compact('conversations'));
    }

    public function show(string $id)
    {
        $conversation = Conversation::where('company_id', Auth::user()->company_id)
            ->with('lead')
            ->findOrFail($id);

        $this->authorize('view', $conversation);

        return view('conversations.show', compact('conversation'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Conversation::class);

        $validated = $request->validate([
            'lead_id' => [
                'required',
                'uuid',
                Rule::exists('leads', 'id')->where('company_id', Auth::user()->company_id),
            ],
            'sender'        => 'required|in:lead,bot,human',
            'message'       => 'required|string|min:1|max:5000',
            'response_time' => 'nullable|integer|min:0',
        ]);

        $validated['company_id'] = Auth::user()->company_id;

        Conversation::create($validated);

        return back()->with('success', 'Mensagem registrada com sucesso.');
    }

    public function destroy(string $id)
    {
        $conversation = Conversation::where('company_id', Auth::user()->company_id)
            ->findOrFail($id);

        $this->authorize('delete', $conversation);

        try {
            $conversation->delete();

            return redirect(route('conversations.index'))
                ->with('success', '✅ Conversa removida com sucesso.');
        } catch (\Exception $e) {
            return redirect(route('conversations.index'))
                ->with('error', '❌ Erro ao remover conversa. Tente novamente.');
        }
    }
    public function restore(string $id)
    {
        $conversation = Conversation::withoutGlobalScopes()
            ->onlyTrashed()
            ->where('company_id', Auth::user()->company_id)
            ->findOrFail($id);
        
            $this->authorize('restore', $conversation);
        
            $conversation->restore();
        
            if (request()->expectsJson()) {
                return response()->json(['message' => 'Conversa restaurada']);
            }
        
            return back()->with('success', '✅ Conversa restaurada com sucesso.');
    }
}