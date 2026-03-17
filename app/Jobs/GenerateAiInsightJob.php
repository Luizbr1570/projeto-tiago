<?php

namespace App\Jobs;

use App\Models\AiInsight;
use App\Models\Company;
use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAiInsightJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 120;

    public function __construct(private Company $company) {}

    public function handle(): void
    {
        // FIX #3: withoutGlobalScopes() necessário porque jobs rodam no queue worker,
        // onde Auth::check() retorna false. Sem isso, o CompanyScope aplica
        // whereRaw('1 = 0') e a query retorna vazio silenciosamente.
        $conversations = Conversation::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->latest()
            ->limit(50)
            ->pluck('message')
            ->implode("\n");

        if (empty($conversations)) {
            Log::info("GenerateAiInsightJob: sem conversas para empresa {$this->company->id}");
            return;
        }

        // TODO: substituir pelo client de IA real (OpenAI, Claude API, etc)
        // Exemplo de estrutura para quando integrar:
        // $insight = app(AiService::class)->generateInsight($conversations);

        $insight = "Insight gerado automaticamente em " . now()->toDateTimeString();

        // AiInsight também usa BelongsToCompany — withoutGlobalScopes() no create
        // não é necessário pois o evento creating() verifica Auth::check() antes
        // de injetar, e o company_id é passado explicitamente aqui.
        AiInsight::withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'insight'    => $insight,
        ]);

        Log::info("GenerateAiInsightJob: insight gerado para empresa {$this->company->id}");
    }

    public function failed(\Throwable $e): void
    {
        Log::error("GenerateAiInsightJob falhou para empresa {$this->company->id}: {$e->getMessage()}");
    }
}