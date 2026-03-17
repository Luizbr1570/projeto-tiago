<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateAiInsightJob;
use App\Models\AiInsight;
use Illuminate\Support\Facades\Auth;

class AIInsightController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', AiInsight::class);

        $insights = AiInsight::where('company_id', Auth::user()->company_id)
            ->latest('created_at')
            ->paginate(20);

        return view('insights.index', compact('insights'));
    }

    public function store()
    {
        $this->authorize('create', AiInsight::class);

        GenerateAiInsightJob::dispatch(Auth::user()->company);

        return back()->with('success', 'Geração de insight agendada');
    }

    public function destroy(string $id)
    {
        $insight = AiInsight::where('company_id', Auth::user()->company_id)->findOrFail($id);

        $this->authorize('delete', $insight);

        $insight->delete();

        return back()->with('success', 'Insight removido');
    }
    public function restore(string $id)
    {
        $insight = AiInsight::withoutGlobalScopes()
            ->onlyTrashed()
            ->where('company_id', Auth::user()->company_id)
            ->findOrFail($id);

        $this->authorize('restore', $insight);

        $insight->restore();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Insight restaurado']);
        }

        return back()->with('success', '✅ Insight restaurado.');
    }
}