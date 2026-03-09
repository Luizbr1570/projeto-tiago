<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DailyMetric;
use App\Models\Followup;
use App\Models\Lead;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): array
    {
        $company = Company::factory()->create();
        $admin   = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        return [$company, $admin];
    }

    public function test_lista_produtos_da_propria_empresa(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name'       => 'iPhone 15 Pro',
        ]);

        $this->actingAs($admin)
            ->get('/products')
            ->assertOk()
            ->assertSee('iPhone 15 Pro');
    }

    public function test_cria_produto_com_dados_validos(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $this->actingAs($admin)
            ->post('/products', [
                'name'      => 'iPhone 16',
                'category'  => 'Smartphone',
                'avg_price' => 9999,
            ])->assertRedirect();

        $this->assertDatabaseHas('products', [
            'name'       => 'iPhone 16',
            'company_id' => $company->id,
        ]);
    }

    public function test_nao_cria_produto_sem_nome(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $this->actingAs($admin)
            ->post('/products', ['avg_price' => 100])
            ->assertSessionHasErrors('name');
    }

    public function test_nao_cria_produto_com_preco_negativo(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $this->actingAs($admin)
            ->post('/products', ['name' => 'Produto', 'avg_price' => -50])
            ->assertSessionHasErrors('avg_price');
    }

    public function test_deleta_produto_com_soft_delete(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $product = Product::factory()->create(['company_id' => $company->id]);

        $this->actingAs($admin)
            ->delete("/products/{$product->id}")
            ->assertRedirect();

        $this->assertNull(Product::find($product->id));
        $this->assertNotNull(Product::withTrashed()->find($product->id));
    }
}

// ─────────────────────────────────────────────────────────────────

class FollowupTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): array
    {
        $company = Company::factory()->create();
        $admin   = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        return [$company, $admin];
    }

    public function test_lista_followups_da_propria_empresa(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $lead     = Lead::factory()->create(['company_id' => $company->id]);
        $followup = Followup::factory()->create([
            'company_id' => $company->id,
            'lead_id'    => $lead->id,
        ]);

        $this->actingAs($admin)
            ->get('/followups')
            ->assertOk();
    }

    public function test_cria_followup_para_lead_da_propria_empresa(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $lead = Lead::factory()->create(['company_id' => $company->id]);

        $this->actingAs($admin)
            ->post('/followups', ['lead_id' => $lead->id])
            ->assertRedirect();

        $this->assertDatabaseHas('followups', [
            'lead_id'    => $lead->id,
            'company_id' => $company->id,
            'status'     => 'pending',
        ]);
    }

    public function test_atualiza_status_followup(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $lead     = Lead::factory()->create(['company_id' => $company->id]);
        $followup = Followup::factory()->create([
            'company_id' => $company->id,
            'lead_id'    => $lead->id,
            'status'     => 'pending',
        ]);

        $this->actingAs($admin)
            ->patch("/followups/{$followup->id}", ['status' => 'recovered'])
            ->assertRedirect();

        $this->assertDatabaseHas('followups', [
            'id'     => $followup->id,
            'status' => 'recovered',
        ]);
    }

    public function test_nao_atualiza_followup_com_status_invalido(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $lead     = Lead::factory()->create(['company_id' => $company->id]);
        $followup = Followup::factory()->create([
            'company_id' => $company->id,
            'lead_id'    => $lead->id,
        ]);

        $this->actingAs($admin)
            ->patch("/followups/{$followup->id}", ['status' => 'invalido'])
            ->assertSessionHasErrors('status');
    }

    public function test_deleta_followup(): void
    {
        [$company, $admin] = $this->makeAdmin();

        $lead     = Lead::factory()->create(['company_id' => $company->id]);
        $followup = Followup::factory()->create([
            'company_id' => $company->id,
            'lead_id'    => $lead->id,
        ]);

        $this->actingAs($admin)
            ->delete("/followups/{$followup->id}")
            ->assertRedirect();

        $this->assertNull(Followup::find($followup->id));
        $this->assertNotNull(Followup::withTrashed()->find($followup->id));
    }
}

// ─────────────────────────────────────────────────────────────────

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_atualiza_nome_da_empresa(): void
    {
        $company = Company::factory()->create(['name' => 'Empresa Velha']);
        $admin   = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);

        $this->actingAs($admin)
            ->patch('/settings/company', ['name' => 'Empresa Nova'])
            ->assertRedirect();

        $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => 'Empresa Nova']);
    }

    public function test_atualiza_perfil_do_usuario(): void
    {
        $admin = User::factory()->create(['name' => 'Nome Antigo']);

        $this->actingAs($admin)
            ->patch('/settings/profile', [
                'name'  => 'Nome Novo',
                'email' => $admin->email,
            ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'name' => 'Nome Novo']);
    }

    public function test_nao_atualiza_email_duplicado(): void
    {
        $admin1 = User::factory()->create(['email' => 'admin1@test.com']);
        $admin2 = User::factory()->create(['email' => 'admin2@test.com']);

        $this->actingAs($admin2)
            ->patch('/settings/profile', [
                'name'  => $admin2->name,
                'email' => 'admin1@test.com', // email do admin1
            ])->assertSessionHasErrors('email');
    }

    public function test_altera_senha_com_senha_atual_correta(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->patch('/settings/password', [
                'current_password'      => 'password',
                'password'              => 'NovaSenha123',
                'password_confirmation' => 'NovaSenha123',
            ])->assertRedirect();
    }

    public function test_nao_altera_senha_com_senha_atual_errada(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->patch('/settings/password', [
                'current_password'      => 'senha-errada',
                'password'              => 'NovaSenha123',
                'password_confirmation' => 'NovaSenha123',
            ])->assertSessionHasErrors('current_password');
    }

    public function test_nao_altera_senha_acima_de_72_chars(): void
    {
        $admin = User::factory()->create();
        $longa = str_repeat('Aa1', 25); // 75 chars

        $this->actingAs($admin)
            ->patch('/settings/password', [
                'current_password'      => 'password',
                'password'              => $longa,
                'password_confirmation' => $longa,
            ])->assertSessionHasErrors('password');
    }
}

// ─────────────────────────────────────────────────────────────────

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_carrega_para_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_dashboard_carrega_para_agent(): void
    {
        $company = Company::factory()->create();
        $agent   = User::factory()->create(['company_id' => $company->id, 'role' => 'agent']);

        $this->actingAs($agent)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_dashboard_aceita_filtro_de_periodo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['today', '7days', '30days'] as $period) {
            $this->actingAs($admin)
                ->get("/dashboard?period={$period}")
                ->assertOk();
        }
    }

    public function test_nao_autenticado_e_redirecionado(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_metricas_diarias_listam_apenas_da_propria_empresa(): void
    {
        $companyA = Company::factory()->create();
        $adminA   = User::factory()->create(['company_id' => $companyA->id, 'role' => 'admin']);

        $companyB = Company::factory()->create();

        DailyMetric::create([
            'company_id'        => $companyA->id,
            'date'              => now()->toDateString(),
            'leads'             => 10,
            'conversations'     => 50,
            'recovered_leads'   => 2,
            'estimated_revenue' => 5000,
        ]);

        DailyMetric::create([
            'company_id'        => $companyB->id,
            'date'              => now()->toDateString(),
            'leads'             => 99,
            'conversations'     => 999,
            'recovered_leads'   => 99,
            'estimated_revenue' => 999999,
        ]);

        $this->actingAs($adminA)
            ->get('/metrics')
            ->assertOk()
            ->assertSee('10')       // leads da empresa A
            ->assertDontSee('999999'); // receita da empresa B
    }
}
