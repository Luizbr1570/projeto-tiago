<?php

namespace App\Jobs;

use App\Models\Followup;
use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendFollowupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // segundos entre tentativas

    public function __construct(private Lead $lead) {}

    public function handle(): void
    {
        // Verifica se o lead ainda está elegível para followup
        if (!in_array($this->lead->status, ['novo', 'em_conversa', 'pediu_preco'])) {
            Log::info("SendFollowupJob: lead {$this->lead->id} não elegível, pulando.");
            return;
        }

        // Evita followup duplicado pendente
        $jaExiste = Followup::where('lead_id', $this->lead->id)
            ->where('status', 'pending')
            ->exists();

        if ($jaExiste) {
            Log::info("SendFollowupJob: followup já pendente para lead {$this->lead->id}");
            return;
        }

        // Cria o followup
        Followup::create([
            'company_id' => $this->lead->company_id,
            'lead_id'    => $this->lead->id,
            'status'     => 'pending',
        ]);

        Log::info("SendFollowupJob: followup criado para lead {$this->lead->id}");

        // TODO: integrar com WhatsApp/canal de envio aqui
    }

    public function failed(\Throwable $e): void
    {
        Log::error("SendFollowupJob falhou para lead {$this->lead->id}: {$e->getMessage()}");
    }
}
