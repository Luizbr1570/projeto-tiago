<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Conversation;
use App\Models\Followup;
use App\Models\Lead;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Garante que usuários de uma empresa NUNCA acessam dados de outra empresa.
 * Estes são os testes mais críticos do sistema — qualquer falha aqui é
 * uma violação de privacidade em produção.
 */
class MultiTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    // ─── Setup ───────────────────────────────────────────────────

    private function makeCompanyWithAdmin(): array
    {
        $company = Company::factory()->create();
        $admin   = User::factory()->create([
            'company_id' => $company->id,
            'role'       => 'admin',
        ]);
        return [$company, $admin];
    }

    // ─── Leads ───────────────────────────────────────────────────

    public function test_admin_nao_ve_leads_de_outra_empresa(): void
    {
        [$companyA, $adminA] = $this->makeCompanyWithAdmin();
        [$companyB, $adminB] = $this->makeCompanyWithAdmin();

        $leadB = Lead::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($adminA)
            ->get('/leads')
            ->assertOk()
            ->assertDontSee($leadB->phone);
    }

    public function test_admin_nao_acessa_lead_de_outra_empresa(): void
    {
        [$companyA, $adminA] = $this->makeCompanyWithAdmin();
        [$companyB, $adminB] = $this->makeCompanyWithAdmin();

        $leadB = Lead::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($adminA)
            ->get("/leads/{$leadB->id}")
            ->assertNotFound();
    }

    public function test_admin_nao_atualiza_lead_de_outra_empresa(): void
    {
        [$companyA, $adminA] = $this->makeCompanyWithAdmin();
        [$companyB, $adminB] = $this->makeCompanyWithAdmin();

        $leadB = Lead::factory()->create([
            'company_id' => $companyB->id,
            'status'     => 'novo',
        ]);

        $this->actingAs($adminA)
            ->patch("/leads/{$leadB->id}", ['status' => 'encaminhado'])
            ->assertNotFound();

        $this->assertDatabaseHas('leads', ['id' => $leadB->id, 'status' => 'novo']);
    }

    public function test_admin_nao_deleta_lead_de_outra_empresa(): void
    {
        [$companyA, $adminA] = $this->makeCompanyWithAdmin();
        [$companyB, $adminB] = $this->makeCompanyWithAdmin();

        $leadB = Lead::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($adminA)
            ->delete("/leads/{$leadB->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('leads', ['id' => $leadB->id]);
    }

    // ─── Conversas ───────────────────────────────────────────────

    public function test_admin_nao_ve_conversas_de_outra_empresa(): void
    {
        [$companyA, $adminA] = $this->makeCompanyWithAdmin();
        [$companyB, $adminB] = $this->makeCompanyWithAdmin();

        $leadB = Lead::factory()->create(['company_id' => $companyB->id]);
        $convB = Conversation::factory()->create([
            'company_id' => $companyB->id,
            'lead_id'    => $leadB->id,
            'message'    => 'Mensagem secreta empresa B',
        ]);

        $this->actingAs($adminA)
            ->get('/conversations')
            ->assertOk()
            ->assertDontSee('Mensagem secreta empresa B');
    }

    public function test_admin_nao_acessa_conversa_de_outra_empresa(): void
    {
        [$companyA, $adminA] = $this->makeCompanyWithAdmin();
        [$companyB, $adminB] = $this->makeCompanyWithAdmin();

        $leadB = Lead::factory()->create(['company_id' => $companyB->id]);
        $convB = Conversation::factory()->create([
            'company_id' => $companyB->id,
            'lead_id'    => $leadB->id,
        ]);

        $this->actingAs($adminA)
            ->get("/conversations/{$convB->id}")
            ->assertNotFound();
    }

    public function test_admin_nao_deleta_conversa_de_outra_empresa(): void
    {
        [$companyA, $adminA] = $this->makeCompanyWithAdmin();
        [$companyB, $adminB] = $this->makeCompanyWithAdmin();

        $leadB = Lead::factory()->create(['company_id' => $companyB->id]);
        $convB = Conversation::factory()->create([
            'company_id' => $companyB->id,
            'lead_id'    => $leadB->id,
        ]);

        $this->actingAs($adminA)
            ->delete("/conversations/{$convB->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('conversations', ['id' => $convB->id]);
    }

    // ─── Produtos ────────────────────────────────────────────────

    public function test_admin_nao_ve_produtos_de_outra_empresa(): void
    {
        [$companyA, $adminA] = $this->makeCompanyWithAdmin();
        [$companyB, $adminB] = $this->makeCompanyWithAdmin();

        $prodB = Product::factory()->create([
            'company_id' => $companyB->id,
            'name'       => 'Produto Secreto B',
        ]);

        $this->actingAs($adminA)
            ->get('/products')
            ->assertOk()
            ->assertDontSee('Produto Secreto B');
    }

    public function test_admin_nao_atualiza_produto_de_outra_empresa(): void
    {
        [$companyA, $adminA] = $this->makeCompanyWithAdmin();
        [$companyB, $adminB] = $this->makeCompanyWithAdmin();

        $prodB = Product::factory()->create([
            'company_id' => $companyB->id,
            'name'       => 'Original',
        ]);

        $this->actingAs($adminA)
            ->patch("/products/{$prodB->id}", ['name' => 'Hackeado'])
            ->assertNotFound();

        $this->assertDatabaseHas('products', ['id' => $prodB->id, 'name' => 'Original']);
    }

    public function test_admin_nao_deleta_produto_de_outra_empresa(): void
    {
        [$companyA, $adminA] = $this->makeCompanyWithAdmin();
        [$companyB, $adminB] = $this->makeCompanyWithAdmin();

        $prodB = Product::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($adminA)
            ->delete("/products/{$prodB->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('products', ['id' => $prodB->id]);
    }

    // ─── Followups ────────────────────────────────────────────────

    public function test_admin_nao_ve_followups_de_outra_empresa(): void
    {
        [$companyA, $adminA] = $this->makeCompanyWithAdmin();
        [$companyB, $adminB] = $this->makeCompanyWithAdmin();

        $leadB     = Lead::factory()->create(['company_id' => $companyB->id]);
        $followupB = Followup::factory()->create([
            'company_id' => $companyB->id,
            'lead_id'    => $leadB->id,
        ]);

        // Followup da empresa A — deve aparecer
        $leadA     = Lead::factory()->create(['company_id' => $companyA->id]);
        $followupA = Followup::factory()->create([
            'company_id' => $companyA->id,
            'lead_id'    => $leadA->id,
        ]);

        $response = $this->actingAs($adminA)->get('/followups')->assertOk();

        // Garante que só aparece o da empresa A
        $this->assertStringNotContainsString($followupB->id, $response->getContent());
    }

    public function test_admin_nao_atualiza_followup_de_outra_empresa(): void
    {
        [$companyA, $adminA] = $this->makeCompanyWithAdmin();
        [$companyB, $adminB] = $this->makeCompanyWithAdmin();

        $leadB     = Lead::factory()->create(['company_id' => $companyB->id]);
        $followupB = Followup::factory()->create([
            'company_id' => $companyB->id,
            'lead_id'    => $leadB->id,
            'status'     => 'pending',
        ]);

        $this->actingAs($adminA)
            ->patch("/followups/{$followupB->id}", ['status' => 'recovered'])
            ->assertNotFound();

        $this->assertDatabaseHas('followups', ['id' => $followupB->id, 'status' => 'pending']);
    }

    public function test_admin_nao_deleta_followup_de_outra_empresa(): void
    {
        [$companyA, $adminA] = $this->makeCompanyWithAdmin();
        [$companyB, $adminB] = $this->makeCompanyWithAdmin();

        $leadB     = Lead::factory()->create(['company_id' => $companyB->id]);
        $followupB = Followup::factory()->create([
            'company_id' => $companyB->id,
            'lead_id'    => $leadB->id,
        ]);

        $this->actingAs($adminA)
            ->delete("/followups/{$followupB->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('followups', ['id' => $followupB->id]);
    }

    // ─── Não pode criar lead em empresa alheia ───────────────────

    public function test_admin_nao_cria_followup_para_lead_de_outra_empresa(): void
    {
        [$companyA, $adminA] = $this->makeCompanyWithAdmin();
        [$companyB, $adminB] = $this->makeCompanyWithAdmin();

        $leadB = Lead::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($adminA)
            ->post('/followups', ['lead_id' => $leadB->id])
            ->assertSessionHasErrors('lead_id');

        $this->assertDatabaseMissing('followups', ['lead_id' => $leadB->id]);
    }

    public function test_admin_nao_cria_conversa_para_lead_de_outra_empresa(): void
    {
        [$companyA, $adminA] = $this->makeCompanyWithAdmin();
        [$companyB, $adminB] = $this->makeCompanyWithAdmin();

        $leadB = Lead::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($adminA)
            ->post('/conversations', [
                'lead_id' => $leadB->id,
                'sender'  => 'human',
                'message' => 'Tentando invadir',
            ])->assertSessionHasErrors('lead_id');
    }
}
