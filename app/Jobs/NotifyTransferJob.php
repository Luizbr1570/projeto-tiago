<?php

namespace App\Jobs;

use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;


class NotifyTransferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(private ChatSession $session) {}

    public function handle(): void
    {
        // Busca os admins da empresa para notificar
        $admins = User::where('company_id', $this->session->company_id)
            ->where('role', 'admin')
            ->get();

        if ($admins->isEmpty()) {
            Log::warning("NotifyTransferJob: nenhum admin encontrado para empresa {$this->session->company_id}");
            return;
        }

        $lead = $this->session->lead;

        foreach ($admins as $admin) {
            // TODO: substituir pelo canal de notificação real (Slack, WhatsApp, email, etc)
            // Exemplo com email nativo do Laravel:
            // $admin->notify(new TransferNotification($this->session));

            Log::info("NotifyTransferJob: notificando {$admin->email} sobre transferência do lead {$lead->id}");
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("NotifyTransferJob falhou para sessão {$this->session->id}: {$e->getMessage()}");
    }
}