<?php

namespace Tests\Unit\Services;

use App\Models\ChatSession;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Followup;
use App\Models\Lead;
use App\Models\Product;
use App\Services\MetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes unitários do MetricsService.
 *
 * O service usa withoutGlobalScopes() + company_id manual,
 * então não precisa de usuário autenticado — pode rodar puro.
 */
class MetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private MetricsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->service = new MetricsService($this->company->id, 'today');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    private function createLead(array $attrs = []): Lead
    {
        return Lead::factory()->create(array_merge(
            ['company_id' => $this->company->id],
            $attrs
        ));
    }

    private function createConversation(Lead $lead, array $attrs = []): void
    {
        Conversation::withoutGlobalScopes()->create(array_merge([
            'company_id'    => $this->company->id,
            'lead_id'       => $lead->id,
            'sender'        => 'bot',
            'message'       => 'mensagem',
            'response_time' => 1000,
        ], $attrs));
    }

    private function createSession(array $attrs = []): void
    {
        ChatSession::withoutGlobalScopes()->create(array_merge([
            'company_id'           => $this->company->id,
            'lead_id'              => $this->createLead()->id,
            'started_at'           => now(),
            'transferred_to_human' => false,
        ], $attrs));
    }

    // ─── leadsToday / leadsMonth ──────────────────────────────────

    public function test_leads_today_conta_apenas_leads_de_hoje(): void
    {
        $this->createLead(['created_at' => now()]);
        $this->createLead(['created_at' => now()]);
        $this->createLead(['created_at' => now()->subDays(2)]); // não conta

        $this->assertEquals(2, $this->service->leadsToday());
    }

    public function test_leads_today_zero_sem_leads(): void
    {
        $this->assertEquals(0, $this->service->leadsToday());
    }

    public function test_leads_month_conta_mes_corrente(): void
    {
        $this->createLead(['created_at' => now()]);
        $this->createLead(['created_at' => now()->startOfMonth()]);
        $this->createLead(['created_at' => now()->subMonths(2)]); // mês anterior, não conta

        $this->assertEquals(2, $this->service->leadsMonth());
    }

    // ─── Período 7days / 30days ───────────────────────────────────

    public function test_periodo_7days_inclui_leads_da_ultima_semana(): void
    {
        $service = new MetricsService($this->company->id, '7days');

        $this->createLead(['created_at' => now()->subDays(3)]);
        $this->createLead(['created_at' => now()->subDays(6)]);
        $this->createLead(['created_at' => now()->subDays(8)]); // fora do período

        $this->assertEquals(2, $service->leadsToday());
    }

    public function test_periodo_30days_inclui_leads_do_ultimo_mes(): void
    {
        $service = new MetricsService($this->company->id, '30days');

        $this->createLead(['created_at' => now()->subDays(15)]);
        $this->createLead(['created_at' => now()->subDays(29)]);
        $this->createLead(['created_at' => now()->subDays(31)]); // fora do período

        $this->assertEquals(2, $service->leadsToday());
    }

    // ─── ticketAverage ───────────────────────────────────────────

    public function test_ticket_average_calcula_media_corretamente(): void
    {
        Product::withoutGlobalScopes()->create(['company_id' => $this->company->id, 'name' => 'A', 'avg_price' => 1000]);
        Product::withoutGlobalScopes()->create(['company_id' => $this->company->id, 'name' => 'B', 'avg_price' => 3000]);

        $this->assertEquals(2000.00, $this->service->ticketAverage());
    }

    public function test_ticket_average_retorna_zero_sem_produtos(): void
    {
        $this->assertEquals(0.0, $this->service->ticketAverage());
    }

    public function test_ticket_average_ignora_produtos_de_outra_empresa(): void
    {
        $outra = Company::factory()->create();
        Product::withoutGlobalScopes()->create(['company_id' => $outra->id, 'name' => 'X', 'avg_price' => 9999]);

        $this->assertEquals(0.0, $this->service->ticketAverage());
    }

    // ─── transferRate ────────────────────────────────────────────

    public function test_transfer_rate_calcula_percentual_correto(): void
    {
        // 2 transferidas de 4 sessões = 50%
        $this->createSession(['transferred_to_human' => true]);
        $this->createSession(['transferred_to_human' => true]);
        $this->createSession(['transferred_to_human' => false]);
        $this->createSession(['transferred_to_human' => false]);

        $this->assertEquals(50.0, $this->service->transferRate());
    }

    public function test_transfer_rate_retorna_zero_sem_sessoes(): void
    {
        $this->assertEquals(0.0, $this->service->transferRate());
    }

    // ─── aiResponseRate ──────────────────────────────────────────

    public function test_ai_response_rate_calcula_percentual_bot(): void
    {
        $lead = $this->createLead();
        $this->createConversation($lead, ['sender' => 'bot']);
        $this->createConversation($lead, ['sender' => 'bot']);
        $this->createConversation($lead, ['sender' => 'lead']);
        $this->createConversation($lead, ['sender' => 'human']);

        // 2 bot de 4 mensagens = 50%
        $this->assertEquals(50.0, $this->service->aiResponseRate());
    }

    public function test_ai_response_rate_retorna_zero_sem_conversas(): void
    {
        $this->assertEquals(0.0, $this->service->aiResponseRate());
    }

    // ─── avgResponseTime ─────────────────────────────────────────

    public function test_avg_response_time_converte_ms_para_segundos(): void
    {
        $lead = $this->createLead();
        $this->createConversation($lead, ['sender' => 'bot', 'response_time' => 2000]); // 2s
        $this->createConversation($lead, ['sender' => 'bot', 'response_time' => 4000]); // 4s

        // Média = 3000ms = 3.0s
        $this->assertEquals(3.0, $this->service->avgResponseTime());
    }

    public function test_avg_response_time_ignora_conversas_sem_response_time(): void
    {
        $lead = $this->createLead();
        $this->createConversation($lead, ['sender' => 'bot', 'response_time' => 2000]);
        $this->createConversation($lead, ['sender' => 'bot', 'response_time' => null]); // ignorada

        $this->assertEquals(2.0, $this->service->avgResponseTime());
    }

    // ─── leadsNew / leadsUnique / leadsRecurring ──────────────────

    public function test_leads_new_conta_primeiros_contatos(): void
    {
        // Lead completamente novo
        $this->createLead(['phone' => '11111', 'created_at' => now()]);

        // Lead que já existia antes do período
        $this->createLead(['phone' => '22222', 'created_at' => now()->subDays(10)]);
        $this->createLead(['phone' => '22222', 'created_at' => now()]); // recorrente hoje

        $this->assertEquals(1, $this->service->leadsNew());
    }

    public function test_leads_unique_conta_phones_distintos(): void
    {
        $this->createLead(['phone' => 'AAAA', 'created_at' => now()]);
        $this->createLead(['phone' => 'AAAA', 'created_at' => now()]); // mesmo phone
        $this->createLead(['phone' => 'BBBB', 'created_at' => now()]);

        $this->assertEquals(2, $this->service->leadsUnique());
    }

    public function test_leads_recurring_conta_phones_com_mais_de_um_contato(): void
    {
        $this->createLead(['phone' => 'AAAA', 'created_at' => now()]);
        $this->createLead(['phone' => 'AAAA', 'created_at' => now()]); // recorrente
        $this->createLead(['phone' => 'BBBB', 'created_at' => now()]); // único, não conta

        $this->assertEquals(1, $this->service->leadsRecurring());
    }

    // ─── Recuperação ─────────────────────────────────────────────

    public function test_leads_lost_conta_status_perdido(): void
    {
        $this->createLead(['status' => 'perdido']);
        $this->createLead(['status' => 'perdido']);
        $this->createLead(['status' => 'novo']); // não conta

        $this->assertEquals(2, $this->service->leadsLost());
    }

    public function test_leads_recovered_conta_followups_com_recovered_true(): void
    {
        $lead = $this->createLead();

        Followup::withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'lead_id'    => $lead->id,
            'status'     => 'recovered',
            'recovered'  => true,
        ]);
        Followup::withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'lead_id'    => $lead->id,
            'status'     => 'pending',
            'recovered'  => false,
        ]);

        $this->assertEquals(1, $this->service->leadsRecovered());
    }

    public function test_recovery_rate_calcula_percentual_correto(): void
    {
        $lead = $this->createLead(['status' => 'perdido']);
        $this->createLead(['status' => 'perdido']);

        Followup::withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'lead_id'    => $lead->id,
            'recovered'  => true,
            'status'     => 'recovered',
        ]);

        // 1 recuperado de 2 perdidos = 50%
        $this->assertEquals(50.0, $this->service->recoveryRate());
    }

    public function test_recovery_rate_retorna_zero_sem_perdidos(): void
    {
        $this->assertEquals(0.0, $this->service->recoveryRate());
    }

    // ─── Receita estimada ─────────────────────────────────────────

    public function test_revenue_with_ai_maior_que_revenue_estimated(): void
    {
        // Com produto e leads, a receita com IA (17.8%) > sem IA (15%)
        Product::withoutGlobalScopes()->create(['company_id' => $this->company->id, 'name' => 'X', 'avg_price' => 1000]);
        $this->createLead(['created_at' => now()]);

        $this->assertGreaterThan(
            $this->service->revenueEstimated(),
            $this->service->revenueWithAi()
        );
    }

    public function test_revenue_ai_impact_e_a_diferenca_entre_os_dois(): void
    {
        Product::withoutGlobalScopes()->create(['company_id' => $this->company->id, 'name' => 'X', 'avg_price' => 1000]);
        $this->createLead(['created_at' => now()]);

        $expected = round(
            $this->service->revenueWithAi() - $this->service->revenueEstimated(),
            2
        );

        $this->assertEquals($expected, $this->service->revenueAiImpact());
    }

    // ─── Isolamento de empresa ───────────────────────────────────

    public function test_service_nao_conta_leads_de_outra_empresa(): void
    {
        $outra = Company::factory()->create();
        Lead::factory()->count(10)->create(['company_id' => $outra->id]);

        $this->assertEquals(0, $this->service->leadsToday());
        $this->assertEquals(0, $this->service->leadsMonth());
    }

    public function test_service_nao_conta_sessoes_de_outra_empresa(): void
    {
        $outra = Company::factory()->create();
        $lead  = Lead::factory()->create(['company_id' => $outra->id]);

        ChatSession::withoutGlobalScopes()->create([
            'company_id'           => $outra->id,
            'lead_id'              => $lead->id,
            'started_at'           => now(),
            'transferred_to_human' => true,
        ]);

        $this->assertEquals(0.0, $this->service->transferRate());
    }

    // ─── dashboard() retorna todas as chaves esperadas ────────────

    public function test_dashboard_retorna_todas_as_chaves(): void
    {
        $data = $this->service->dashboard();

        $expectedKeys = [
            'leads_today', 'leads_month', 'ticket_average', 'transfer_rate',
            'ai_response_rate', 'avg_response_time', 'leads_unique', 'leads_recurring',
            'leads_new', 'funnel', 'top_products', 'peak_hours', 'leads_lost',
            'leads_recovered', 'recovery_rate', 'revenue_recovered', 'revenue_estimated',
            'revenue_with_ai', 'revenue_ai_impact', 'revenue_is_estimate',
            'leads_per_day', 'leads_per_month',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $data, "Chave ausente no dashboard(): '{$key}'");
        }
    }

    public function test_dashboard_revenue_is_estimate_e_sempre_true(): void
    {
        $data = $this->service->dashboard();
        $this->assertTrue($data['revenue_is_estimate']);
    }

    public function test_funnel_tem_5_estagios(): void
    {
        $funnel = $this->service->conversionFunnel();
        $this->assertCount(5, $funnel);
    }

    public function test_funnel_estagios_tem_label_e_value(): void
    {
        $funnel = $this->service->conversionFunnel();

        foreach ($funnel as $stage) {
            $this->assertArrayHasKey('label', $stage);
            $this->assertArrayHasKey('value', $stage);
            $this->assertIsInt($stage['value']);
        }
    }
}
