<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): array
    {
        $company = Company::factory()->create();
        $admin   = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        return [$company, $admin];
    }

    // ─── Listagem ─────────────────────────────────────────────────

    public function test_lista_leads_da_propria_empresa(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $lead = Lead::factory()->create([
            'company_id' => $company->id,
            'phone'      => '(11) 99999-1111',
        ]);

        $this->actingAs($admin)
            ->get('/leads')
            ->assertOk()
            ->assertSee($lead->phone);
    }

    public function test_busca_por_telefone(): void
    {
        [$company, $admin] = $this->makeAdmin();

        Lead::factory()->create(['company_id' => $company->id, 'phone' => '(11) 91111-1111']);
        Lead::factory()->create(['company_id' => $company->id, 'phone' => '(21) 92222-2222']);

        $this->actingAs($admin)
            ->get('/leads?search=91111')
            ->assertOk()
            ->assertSee('91111-1111')
            ->assertDontSee('92222-2222');
    }

    // ─── Criação ──────────────────────────────────────────────────

    public function test_cria_lead_com_dados_validos(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $this->actingAs($admin)
            ->post('/leads', [
                'phone'  => '(11) 98888-7777',
                'city'   => 'São Paulo',
                'source' => 'WhatsApp',
            ])->assertRedirect();

        $this->assertDatabaseHas('leads', [
            'phone'      => '(11) 98888-7777',
            'company_id' => $company->id,
        ]);
    }

    public function test_lead_criado_pertence_a_empresa_do_admin(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $this->actingAs($admin)->post('/leads', ['phone' => '(11) 91234-5678']);

        $lead = Lead::where('phone', '(11) 91234-5678')->first();
        $this->assertEquals($company->id, $lead->company_id);
    }

    public function test_nao_cria_lead_sem_telefone(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $this->actingAs($admin)
            ->post('/leads', ['city' => 'SP'])
            ->assertSessionHasErrors('phone');
    }

    // ─── Visualização ─────────────────────────────────────────────

    public function test_exibe_detalhes_do_lead(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $lead = Lead::factory()->create([
            'company_id' => $company->id,
            'phone'      => '(11) 97777-6666',
        ]);

        $this->actingAs($admin)
            ->get("/leads/{$lead->id}")
            ->assertOk()
            ->assertSee($lead->phone);
    }

    // ─── Atualização ──────────────────────────────────────────────

    public function test_atualiza_status_do_lead(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $lead = Lead::factory()->create([
            'company_id' => $company->id,
            'status'     => 'novo',
        ]);

        $this->actingAs($admin)
            ->patch("/leads/{$lead->id}", ['status' => 'encaminhado'])
            ->assertRedirect();

        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => 'encaminhado']);
    }

    public function test_nao_atualiza_status_invalido(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $lead = Lead::factory()->create(['company_id' => $company->id, 'status' => 'novo']);

        $this->actingAs($admin)
            ->patch("/leads/{$lead->id}", ['status' => 'status_invalido'])
            ->assertSessionHasErrors('status');
    }

    // ─── Exclusão ─────────────────────────────────────────────────

    public function test_deleta_lead_com_soft_delete(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $lead = Lead::factory()->create(['company_id' => $company->id]);

        $this->actingAs($admin)
            ->delete("/leads/{$lead->id}")
            ->assertRedirect();

        // Soft delete: não aparece em queries normais
        $this->assertNull(Lead::find($lead->id));

        // Mas ainda existe no banco
        $this->assertDatabaseHas('leads', ['id' => $lead->id]);

        // E pode ser encontrado com withTrashed
        $this->assertNotNull(Lead::withTrashed()->find($lead->id));
    }
}
