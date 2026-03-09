<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatSessionTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): array
    {
        $company = Company::factory()->create();
        $admin   = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        return [$company, $admin];
    }

    // ─── Listagem ────────────────────────────────────────────────

    public function test_lista_sessoes_da_propria_empresa(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $lead    = Lead::factory()->create(['company_id' => $company->id]);
        $session = ChatSession::factory()->create([
            'company_id' => $company->id,
            'lead_id'    => $lead->id,
        ]);

        $this->actingAs($admin)
            ->get('/chat-sessions')
            ->assertOk();
    }

    // ─── Criação ─────────────────────────────────────────────────

    public function test_cria_sessao_para_lead_valido(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $lead = Lead::factory()->create(['company_id' => $company->id]);

        $this->actingAs($admin)
            ->post('/chat-sessions', ['lead_id' => $lead->id])
            ->assertRedirect();

        $this->assertDatabaseHas('chat_sessions', [
            'lead_id'    => $lead->id,
            'company_id' => $company->id,
        ]);
    }

    public function test_nao_cria_sessao_duplicada_para_lead_com_sessao_aberta(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $lead = Lead::factory()->create(['company_id' => $company->id]);

        // Cria a primeira sessão
        ChatSession::factory()->create([
            'company_id' => $company->id,
            'lead_id'    => $lead->id,
            'ended_at'   => null, // sessão aberta
        ]);

        // Tenta criar segunda sessão para o mesmo lead
        $this->actingAs($admin)
            ->post('/chat-sessions', ['lead_id' => $lead->id])
            ->assertRedirect()
            ->assertSessionHas('error');

        // Deve existir apenas 1 sessão
        $this->assertEquals(
            1,
            ChatSession::where('lead_id', $lead->id)->count()
        );
    }

    public function test_nao_cria_sessao_para_lead_de_outra_empresa(): void
    {
        [$companyA, $adminA] = $this->makeAdmin();
        [$companyB, $adminB] = $this->makeAdmin();

        $leadB = Lead::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($adminA)
            ->post('/chat-sessions', ['lead_id' => $leadB->id])
            ->assertSessionHasErrors('lead_id');
    }

    // ─── Transferência ───────────────────────────────────────────

    public function test_transfere_sessao_para_humano(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $lead    = Lead::factory()->create(['company_id' => $company->id]);
        $session = ChatSession::factory()->create([
            'company_id'           => $company->id,
            'lead_id'              => $lead->id,
            'transferred_to_human' => false,
        ]);

        $this->actingAs($admin)
            ->patch("/chat-sessions/{$session->id}/transfer")
            ->assertRedirect();

        $this->assertDatabaseHas('chat_sessions', [
            'id'                   => $session->id,
            'transferred_to_human' => true,
        ]);
    }

    public function test_nao_transfere_sessao_de_outra_empresa(): void
    {
        [$companyA, $adminA] = $this->makeAdmin();
        [$companyB, $adminB] = $this->makeAdmin();

        $leadB    = Lead::factory()->create(['company_id' => $companyB->id]);
        $sessionB = ChatSession::factory()->create([
            'company_id'           => $companyB->id,
            'lead_id'              => $leadB->id,
            'transferred_to_human' => false,
        ]);

        $this->actingAs($adminA)
            ->patch("/chat-sessions/{$sessionB->id}/transfer")
            ->assertNotFound();

        $this->assertDatabaseHas('chat_sessions', [
            'id'                   => $sessionB->id,
            'transferred_to_human' => false,
        ]);
    }

    // ─── Encerramento ────────────────────────────────────────────

    public function test_encerra_sessao_aberta(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $lead    = Lead::factory()->create(['company_id' => $company->id]);
        $session = ChatSession::factory()->create([
            'company_id' => $company->id,
            'lead_id'    => $lead->id,
            'ended_at'   => null,
        ]);

        $this->actingAs($admin)
            ->patch("/chat-sessions/{$session->id}/close")
            ->assertRedirect();

        $this->assertNotNull(
            ChatSession::find($session->id)->ended_at
        );
    }

    public function test_nao_encerra_sessao_de_outra_empresa(): void
    {
        [$companyA, $adminA] = $this->makeAdmin();
        [$companyB, $adminB] = $this->makeAdmin();

        $leadB    = Lead::factory()->create(['company_id' => $companyB->id]);
        $sessionB = ChatSession::factory()->create([
            'company_id' => $companyB->id,
            'lead_id'    => $leadB->id,
            'ended_at'   => null,
        ]);

        $this->actingAs($adminA)
            ->patch("/chat-sessions/{$sessionB->id}/close")
            ->assertNotFound();

        $this->assertNull(
            ChatSession::find($sessionB->id)->ended_at
        );
    }

    // ─── Agent ───────────────────────────────────────────────────

    public function test_agent_acessa_e_gerencia_sessoes(): void
    {
        $company = Company::factory()->create();
        $agent   = User::factory()->create(['company_id' => $company->id, 'role' => 'agent']);
        $lead    = Lead::factory()->create(['company_id' => $company->id]);

        $this->actingAs($agent)
            ->get('/chat-sessions')
            ->assertOk();

        $this->actingAs($agent)
            ->post('/chat-sessions', ['lead_id' => $lead->id])
            ->assertRedirect();
    }
}
