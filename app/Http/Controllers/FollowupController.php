<?php

namespace App\Http\Controllers;

use App\Models\Followup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FollowupController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Followup::class);

        $followups = Followup::with('lead')
            ->latest('created_at')
            ->paginate(20);

        return view('followups.index', compact('followups'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Followup::class);

        $validado = $request->validate([
            'lead_id' => [
                'required',
                'uuid',
                Rule::exists('leads', 'id')->where('company_id', Auth::user()->company_id),
            ],
        ]);

        Followup::create([
            'lead_id'    => $validado['lead_id'],
            'company_id' => Auth::user()->company_id,
            'status'     => 'pending',
        ]);

        \App\Services\MetricsCacheService::invalidate(Auth::user()->company_id);

        return back()->with('success', '✅ Follow-up agendado com sucesso.');
    }

    public function update(Request $request, string $id)
    {
        $followup = Followup::where('company_id', Auth::user()->company_id)->findOrFail($id);

        $this->authorize('update', $followup);

        $validado = $request->validate([
            'status'    => 'required|in:pending,sent,recovered',
            'recovered' => 'nullable|boolean',
        ]);

        if ($validado['status'] === 'sent') {
            $validado['sent_at'] = now();
        }

        $followup->update($validado);

        \App\Services\MetricsCacheService::invalidate(Auth::user()->company_id);

        return back()->with('success', '✅ Follow-up atualizado com sucesso.');
    }

    public function destroy(string $id)
    {
        $followup = Followup::where('company_id', Auth::user()->company_id)->findOrFail($id);

        $this->authorize('delete', $followup);

        $followup->delete();

        return back()->with('success', '✅ Follow-up removido com sucesso.');
    }
}