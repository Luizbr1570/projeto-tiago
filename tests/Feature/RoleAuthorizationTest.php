<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Garante que o middleware CheckRole funciona corretamente.
 * Agent não pode acessar rotas exclusivas de admin.
 */
class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ─────────────────────────────────────────────────

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeAgent(): User
    {
        $company = Company::factory()->create();
        return User::factory()->create([
            'company_id' => $company->id,
            'role'       => 'agent',
        ]);
    }

    // ─── Rotas só para admin ──────────────────────────────────────

    public function test_agent_nao_acessa_produtos(): void
    {
        $agent = $this->makeAgent();

        $this->actingAs($agent)
            ->get('/products')
            ->assertForbidden();
    }

    public function test_agent_nao_cria_produto(): void
    {
        $agent = $this->makeAgent();

        $this->actingAs($agent)
            ->post('/products', ['name' => 'Produto', 'avg_price' => 100])
            ->assertForbidden();
    }

    public function test_agent_nao_acessa_followups(): void
    {
        $agent = $this->makeAgent();

        $this->actingAs($agent)
            ->get('/followups')
            ->assertForbidden();
    }

    public function test_agent_nao_acessa_insights(): void
    {
        $agent = $this->makeAgent();

        $this->actingAs($agent)
            ->get('/insights')
            ->assertForbidden();
    }

    public function test_agent_nao_acessa_metricas(): void
    {
        $agent = $this->makeAgent();

        $this->actingAs($agent)
            ->get('/metrics')
            ->assertForbidden();
    }

    public function test_agent_nao_exporta_leads(): void
    {
        $agent = $this->makeAgent();

        $this->actingAs($agent)
            ->get('/export/leads')
            ->assertForbidden();
    }

    public function test_agent_nao_altera_dados_da_empresa(): void
    {
        $agent = $this->makeAgent();

        $this->actingAs($agent)
            ->patch('/settings/company', ['name' => 'Hackeado'])
            ->assertForbidden();
    }

    // ─── Rotas acessíveis para agent ─────────────────────────────

    public function test_agent_acessa_dashboard(): void
    {
        $agent = $this->makeAgent();

        $this->actingAs($agent)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_agent_acessa_leads(): void
    {
        $agent = $this->makeAgent();

        $this->actingAs($agent)
            ->get('/leads')
            ->assertOk();
    }

    public function test_agent_acessa_conversas(): void
    {
        $agent = $this->makeAgent();

        $this->actingAs($agent)
            ->get('/conversations')
            ->assertOk();
    }

    public function test_agent_acessa_chat_sessions(): void
    {
        $agent = $this->makeAgent();

        $this->actingAs($agent)
            ->get('/chat-sessions')
            ->assertOk();
    }

    public function test_agent_acessa_e_atualiza_proprio_perfil(): void
    {
        $agent = $this->makeAgent();

        $this->actingAs($agent)
            ->patch('/settings/profile', [
                'name'  => 'Novo Nome',
                'email' => $agent->email,
            ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $agent->id, 'name' => 'Novo Nome']);
    }

    // ─── Rotas admin acessíveis para admin ───────────────────────

    public function test_admin_acessa_produtos(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get('/products')
            ->assertOk();
    }

    public function test_admin_acessa_followups(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get('/followups')
            ->assertOk();
    }

    public function test_admin_acessa_metricas(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get('/metrics')
            ->assertOk();
    }

    public function test_admin_exporta_leads(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get('/export/leads')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    // ─── Usuário não autenticado ──────────────────────────────────

    public function test_nao_autenticado_e_redirecionado_para_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/leads')->assertRedirect('/login');
        $this->get('/products')->assertRedirect('/login');
        $this->get('/followups')->assertRedirect('/login');
    }
    // ─── Rate limiting na exportacao ───────────────────────────────────────────────

    public function test_exportacao_retorna_csv_valido(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get('/export/leads')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_agent_nao_acessa_exportacao(): void
    {
        $agent = $this->makeAgent();

        $this->actingAs($agent)
            ->get('/export/leads')
            ->assertForbidden();
    }
}