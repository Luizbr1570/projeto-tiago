<?php

namespace Tests\Unit\Jobs;

use App\Jobs\GenerateAiInsightJob;
use App\Jobs\SendFollowupJob;
use App\Jobs\UpdateDailyMetricsJob;
use App\Models\AiInsight;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\DailyMetric;
use App\Models\Followup;
use App\Models\Lead;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

// ─── GenerateAiInsightJob ────────────────────────────────────────

class GenerateAiInsightJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_insight_quando_ha_conversas(): void
    {
        $company = Company::factory()->create();

        // Cria algumas conversas sem global scope (contexto de job)
        $lead = Lead::factory()->create(['company_id' => $company->id]);
        Conversation::withoutGlobalScopes()->create([
            'company_id'    => $company->id,
            'lead_id'       => $lead->id,
            'sender'        => 'lead',
            'message'       => 'Quero comprar um iPhone',
            'response_time' => null,
        ]);

        GenerateAiInsightJob::dispatchSync($company);

        $this->assertDatabaseHas('ai_insights', ['company_id' => $company->id]);
    }

    public function test_nao_cria_insight_sem_conversas(): void
    {
        $company = Company::factory()->create();

        Log::shouldReceive('info')->once();

        GenerateAiInsightJob::dispatchSync($company);

        $this->assertDatabaseMissing('ai_insights', ['company_id' => $company->id]);
    }

    public function test_insight_pertence_a_empresa_correta(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $lead = Lead::factory()->create(['company_id' => $companyA->id]);
        Conversation::withoutGlobalScopes()->create([
            'company_id'    => $companyA->id,
            'lead_id'       => $lead->id,
            'sender'        => 'lead',
            'message'       => 'Mensagem da empresa A',
            'response_time' => null,
        ]);

        GenerateAiInsightJob::dispatchSync($companyA);

        // Insight gerado só para empresa A
        $this->assertEquals(1, AiInsight::withoutGlobalScopes()->where('company_id', $companyA->id)->count());
        $this->assertEquals(0, AiInsight::withoutGlobalScopes()->where('company_id', $companyB->id)->count());
    }

    public function test_configuracao_de_tentativas(): void
    {
        $job = new GenerateAiInsightJob(Company::factory()->make());
        $this->assertEquals(2, $job->tries);
        $this->assertEquals(120, $job->backoff);
    }
}

// ─── SendFollowupJob ─────────────────────────────────────────────

class SendFollowupJobTest extends TestCase
{
    use RefreshDatabase;

    private function makeLead(string $status): Lead
    {
        $company = Company::factory()->create();
        return Lead::factory()->create([
            'company_id' => $company->id,
            'status'     => $status,
        ]);
    }

    public function test_cria_followup_para_lead_elegivel(): void
    {
        foreach (['novo', 'em_conversa', 'pediu_preco'] as $status) {
            $lead = $this->makeLead($status);

            SendFollowupJob::dispatchSync($lead);

            $this->assertDatabaseHas('followups', [
                'lead_id'    => $lead->id,
                'company_id' => $lead->company_id,
                'status'     => 'pending',
            ]);
        }
    }

    public function test_nao_cria_followup_para_lead_nao_elegivel(): void
    {
        foreach (['encaminhado', 'perdido', 'recuperacao'] as $status) {
            $lead = $this->makeLead($status);

            SendFollowupJob::dispatchSync($lead);

            $this->assertDatabaseMissing('followups', ['lead_id' => $lead->id]);
        }
    }

    public function test_nao_cria_followup_duplicado_se_ja_existe_pendente(): void
    {
        $lead = $this->makeLead('novo');

        // Cria followup pendente manualmente
        Followup::withoutGlobalScopes()->create([
            'company_id' => $lead->company_id,
            'lead_id'    => $lead->id,
            'status'     => 'pending',
        ]);

        SendFollowupJob::dispatchSync($lead);

        // Deve continuar com apenas 1 followup
        $this->assertEquals(
            1,
            Followup::withoutGlobalScopes()->where('lead_id', $lead->id)->count()
        );
    }

    public function test_followup_criado_com_company_id_correto(): void
    {
        $lead = $this->makeLead('novo');

        SendFollowupJob::dispatchSync($lead);

        $followup = Followup::withoutGlobalScopes()->where('lead_id', $lead->id)->first();
        $this->assertEquals($lead->company_id, $followup->company_id);
    }

    public function test_configuracao_de_tentativas(): void
    {
        $lead = Lead::factory()->make();
        $job  = new SendFollowupJob($lead);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->backoff);
    }
}

// ─── UpdateDailyMetricsJob ───────────────────────────────────────

class UpdateDailyMetricsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_metrica_diaria_corretamente(): void
    {
        $company = Company::factory()->create();
        $date    = now()->toDateString();

        // 3 leads no dia
        Lead::factory()->count(3)->create(['company_id' => $company->id]);

        // 5 conversas no dia
        $lead = Lead::factory()->create(['company_id' => $company->id]);
        Conversation::withoutGlobalScopes()->create(['company_id' => $company->id, 'lead_id' => $lead->id, 'sender' => 'bot', 'message' => 'a', 'response_time' => null]);
        Conversation::withoutGlobalScopes()->create(['company_id' => $company->id, 'lead_id' => $lead->id, 'sender' => 'lead', 'message' => 'b', 'response_time' => null]);

        UpdateDailyMetricsJob::dispatchSync($company, $date);

        $metric = DailyMetric::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('date', $date)
            ->first();

        $this->assertNotNull($metric);
        $this->assertEquals(4, $metric->leads); // 3 factory + 1 para conversas
        $this->assertEquals(2, $metric->conversations);
    }

    public function test_atualiza_metrica_existente_em_vez_de_duplicar(): void
    {
        $company = Company::factory()->create();
        $date    = now()->toDateString();

        // Rodar o job duas vezes
        UpdateDailyMetricsJob::dispatchSync($company, $date);
        Lead::factory()->create(['company_id' => $company->id]);
        UpdateDailyMetricsJob::dispatchSync($company, $date);

        // Deve existir apenas 1 registro para esse dia
        $this->assertEquals(
            1,
            DailyMetric::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('date', $date)
                ->count()
        );
    }

    public function test_receita_estimada_usa_ticket_medio_dos_produtos(): void
    {
        $company = Company::factory()->create();
        $date    = now()->toDateString();

        // Produto com preço médio conhecido
        Product::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name'       => 'iPhone',
            'avg_price'  => 5000,
        ]);

        // 2 leads recuperados no dia
        $lead1 = Lead::factory()->create(['company_id' => $company->id]);
        $lead2 = Lead::factory()->create(['company_id' => $company->id]);

        Followup::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'lead_id'    => $lead1->id,
            'status'     => 'recovered',
            'recovered'  => true,
            'sent_at'    => now(),
        ]);
        Followup::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'lead_id'    => $lead2->id,
            'status'     => 'recovered',
            'recovered'  => true,
            'sent_at'    => now(),
        ]);

        UpdateDailyMetricsJob::dispatchSync($company, $date);

        $metric = DailyMetric::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('date', $date)
            ->first();

        // 2 leads recuperados * R$ 5000 = R$ 10.000
        $this->assertEquals(10000.00, $metric->estimated_revenue);
    }

    public function test_metrica_de_empresa_a_nao_inclui_dados_da_empresa_b(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $date     = now()->toDateString();

        Lead::factory()->count(5)->create(['company_id' => $companyA->id]);
        Lead::factory()->count(99)->create(['company_id' => $companyB->id]);

        UpdateDailyMetricsJob::dispatchSync($companyA, $date);

        $metric = DailyMetric::withoutGlobalScopes()
            ->where('company_id', $companyA->id)
            ->where('date', $date)
            ->first();

        $this->assertEquals(5, $metric->leads);
    }

    public function test_configuracao_de_tentativas(): void
    {
        $job = new UpdateDailyMetricsJob(Company::factory()->make(), now()->toDateString());
        $this->assertEquals(3, $job->tries);
    }
}
