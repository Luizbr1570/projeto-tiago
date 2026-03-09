<?php

namespace App\Http\Controllers;

use App\Jobs\NotifyTransferJob;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ChatSessionController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', ChatSession::class);

        $sessions = ChatSession::with('lead')
            ->latest('started_at')
            ->paginate(20);

        return view('chat_sessions.index', compact('sessions'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', ChatSession::class);

        $validado = $request->validate([
            'lead_id' => [
                'required',
                'uuid',
                Rule::exists('leads', 'id')->where('company_id', Auth::user()->company_id),
            ],
        ]);

        $aberta = ChatSession::where('lead_id', $validado['lead_id'])
            ->whereNull('ended_at')
            ->exists();

        if ($aberta) {
            return back()->with('error', 'Já existe uma sessão aberta para este lead');
        }

        ChatSession::create([
            'lead_id'    => $validado['lead_id'],
            'started_at' => now(),
        ]);

        return back()->with('success', 'Sessão iniciada');
    }

    public function transfer(string $id)
    {
        $session = ChatSession::where('company_id', Auth::user()->company_id)->findOrFail($id);

        $this->authorize('transfer', $session);

        $session->update(['transferred_to_human' => true]);

        NotifyTransferJob::dispatch($session);

        return back()->with('success', 'Sessão transferida para humano');
    }

    public function close(string $id)
    {
        $session = ChatSession::where('company_id', Auth::user()->company_id)->findOrFail($id);

        $this->authorize('close', $session);

        $session->update(['ended_at' => now()]);

        return back()->with('success', 'Sessão encerrada');
    }
}