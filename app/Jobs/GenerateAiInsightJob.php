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
        // Pega as últimas 50 mensagens da empresa para análise
        $conversations = Conversation::where('company_id', $this->company->id)
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

        AiInsight::create([
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
