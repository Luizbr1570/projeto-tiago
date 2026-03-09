<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        // CORRIGIDO: try/finally garante que as FKs são SEMPRE reativadas,
        // mesmo que uma exceção ocorra no meio do seeder.
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            // ── Empresa ───────────────────────────────────────
            $companyId = (string) Str::uuid();

            DB::table('companies')->insert([
                'id'         => $companyId,
                'name'       => 'Japa iPhone',
                'slug'       => 'japa-iphone',
                'plan'       => 'pro',
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ── Usuário admin ─────────────────────────────────
            DB::table('users')->insert([
                'id'         => (string) Str::uuid(),
                'company_id' => $companyId,
                'name'       => 'João Admin',
                'email'      => 'admin@japaiphone.com',
                'password'   => Hash::make('password'),
                'role'       => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ── Produtos ──────────────────────────────────────
            $produtos = [
                ['nome' => 'iPhone 15 Pro Max', 'cat' => 'Smartphone', 'preco' => 9499],
                ['nome' => 'iPhone 15',          'cat' => 'Smartphone', 'preco' => 6499],
                ['nome' => 'iPhone 14',          'cat' => 'Smartphone', 'preco' => 4999],
                ['nome' => 'iPhone 13',          'cat' => 'Smartphone', 'preco' => 3799],
                ['nome' => 'iPhone 12',          'cat' => 'Smartphone', 'preco' => 2999],
                ['nome' => 'Apple Watch',        'cat' => 'Wearable',   'preco' => 2499],
                ['nome' => 'AirPods Pro',        'cat' => 'Audio',      'preco' => 1899],
                ['nome' => 'AirPods',            'cat' => 'Audio',      'preco' => 999],
            ];

            $productIds = [];
            foreach ($produtos as $p) {
                $id = (string) Str::uuid();
                DB::table('products')->insert([
                    'id'         => $id,
                    'company_id' => $companyId,
                    'name'       => $p['nome'],
                    'category'   => $p['cat'],
                    'avg_price'  => $p['preco'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $productIds[] = $id;
            }

            // ── Leads ─────────────────────────────────────────
            $statusList = ['novo','em_conversa','pediu_preco','encaminhado','perdido','recuperacao'];
            $cidades    = ['São Paulo','Rio de Janeiro','Curitiba','Belo Horizonte','Campinas','Salvador','Fortaleza'];
            $origens    = ['WhatsApp','Instagram','Indicação','Google','Facebook'];

            $leadIds      = [];
            $leadStatuses = [];
            $leadDates    = [];

            for ($i = 0; $i < 90; $i++) {
                $id        = (string) Str::uuid();
                $status    = $statusList[array_rand($statusList)];
                $createdAt = now()->subDays(rand(0, 30))->subHours(rand(0, 23));

                DB::table('leads')->insert([
                    'id'            => $id,
                    'company_id'    => $companyId,
                    'phone'         => '(11) 9' . rand(1000, 9999) . '-' . rand(1000, 9999),
                    'city'          => $cidades[array_rand($cidades)],
                    'status'        => $status,
                    'source'        => $origens[array_rand($origens)],
                    'first_contact' => $createdAt,
                    'created_at'    => $createdAt,
                    'updated_at'    => now(),
                ]);

                $leadIds[]      = $id;
                $leadStatuses[] = $status;
                $leadDates[]    = $createdAt;
            }

            // ── Conversas ─────────────────────────────────────
            $senders   = ['lead', 'bot', 'human'];
            $mensagens = [
                'Oi, tudo bem? Queria saber o preço do iPhone 15.',
                'Olá! O iPhone 15 Pro Max está disponível?',
                'Qual a condição do aparelho?',
                'Vocês aceitam troca?',
                'Tem parcelamento no cartão?',
                'Qual a garantia?',
                'Pode me mandar mais fotos?',
                'Olá! Temos esse modelo disponível sim!',
                'O preço é R$ 6.499 à vista ou 12x no cartão.',
                'Temos garantia de 6 meses na loja.',
                'Sim, aceitamos trocas mediante avaliação.',
                'Vou verificar o estoque e já te retorno!',
            ];

            foreach ($leadIds as $idx => $leadId) {
                $qtd = rand(2, 8);
                for ($j = 0; $j < $qtd; $j++) {
                    DB::table('conversations')->insert([
                        'id'         => (string) Str::uuid(),
                        'company_id' => $companyId,
                        'lead_id'    => $leadId,
                        'sender'     => $senders[array_rand($senders)],
                        'message'    => $mensagens[array_rand($mensagens)],
                        'created_at' => $leadDates[$idx]->copy()->addMinutes(rand(1, 120)),
                    ]);
                }
            }

            // ── Chat Sessions ─────────────────────────────────
            $sessionsLeads = array_rand($leadIds, 40);
            foreach ($sessionsLeads as $idx) {
                $started = $leadDates[$idx]->copy()->addMinutes(rand(1, 30));
                DB::table('chat_sessions')->insert([
                    'id'                   => (string) Str::uuid(),
                    'company_id'           => $companyId,
                    'lead_id'              => $leadIds[$idx],
                    'started_at'           => $started,
                    'ended_at'             => rand(0, 1) ? $started->copy()->addMinutes(rand(5, 60)) : null,
                    'transferred_to_human' => rand(0, 4) === 0,
                ]);
            }

            // ── Interesses em produtos ────────────────────────
            $interestLeads = array_rand($leadIds, 70);
            $usedPairs     = [];
            foreach ($interestLeads as $idx) {
                $qtd           = rand(1, 3);
                $prodsSorteados = array_rand($productIds, min($qtd, count($productIds)));
                if (!is_array($prodsSorteados)) $prodsSorteados = [$prodsSorteados];
                foreach ($prodsSorteados as $pidx) {
                    $pair = $leadIds[$idx] . '_' . $productIds[$pidx];
                    if (isset($usedPairs[$pair])) continue;
                    $usedPairs[$pair] = true;
                    DB::table('product_interest')->insert([
                        'id'         => (string) Str::uuid(),
                        'company_id' => $companyId,
                        'lead_id'    => $leadIds[$idx],
                        'product_id' => $productIds[$pidx],
                        'created_at' => now(),
                    ]);
                }
            }

            // ── Followups ─────────────────────────────────────
            foreach ($leadIds as $idx => $leadId) {
                if ($leadStatuses[$idx] === 'perdido') {
                    DB::table('followups')->insert([
                        'id'         => (string) Str::uuid(),
                        'company_id' => $companyId,
                        'lead_id'    => $leadId,
                        'status'     => collect(['pending', 'sent', 'recovered'])->random(),
                        'sent_at'    => rand(0, 1) ? now()->subDays(rand(1, 10)) : null,
                        'recovered'  => rand(0, 3) === 0,
                    ]);
                }
            }

            // ── Métricas diárias ──────────────────────────────
            for ($i = 13; $i >= 0; $i--) {
                DB::table('daily_metrics')->insert([
                    'id'                => (string) Str::uuid(),
                    'company_id'        => $companyId,
                    'date'              => now()->subDays($i)->toDateString(),
                    'leads'             => rand(3, 15),
                    'conversations'     => rand(10, 50),
                    'recovered_leads'   => rand(0, 5),
                    'estimated_revenue' => rand(5000, 80000),
                ]);
            }

            // ── Insights de IA ────────────────────────────────
            $insightsList = [
                '42% das buscas são por iPhone 15 Pro Max',
                'Pico de atendimento entre 11h e 13h',
                'Ticket médio subiu 12% este mês',
                '27% dos clientes perguntam sobre parcelamento',
                'Taxa de recuperação de leads está em 21%',
                'AirPods Pro teve aumento de 18% nas buscas esta semana',
                'Clientes de São Paulo têm ticket médio 15% maior',
                'Leads via Instagram convertem 2x mais que Facebook',
            ];

            foreach ($insightsList as $insight) {
                DB::table('ai_insights')->insert([
                    'id'         => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'insight'    => $insight,
                    'created_at' => now()->subHours(rand(1, 48)),
                ]);
            }

            $this->command->info('✅ Empresa "Japa iPhone" criada!');
            $this->command->info('📧 Login: admin@japaiphone.com');
            $this->command->info('🔑 Senha: password');
            $this->command->info('📊 90 leads, produtos, conversas e métricas gerados!');

        } finally {
            // CORRIGIDO: finally garante que as FKs são reativadas mesmo em caso de erro
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}