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
        $this->seedEmpresa([
            'name'     => 'Japa iPhone',
            'slug'     => 'japa-iphone',
            'plan'     => 'pro',
            'email'    => 'admin@japaiphone.com',
            'username' => 'João Admin',
            'produtos' => [
                ['nome' => 'iPhone 15 Pro Max', 'cat' => 'Smartphone', 'preco' => 9499],
                ['nome' => 'iPhone 15 Pro',     'cat' => 'Smartphone', 'preco' => 8299],
                ['nome' => 'iPhone 15',         'cat' => 'Smartphone', 'preco' => 6499],
                ['nome' => 'iPhone 14 Pro',     'cat' => 'Smartphone', 'preco' => 6199],
                ['nome' => 'iPhone 14',         'cat' => 'Smartphone', 'preco' => 4999],
                ['nome' => 'iPhone 13',         'cat' => 'Smartphone', 'preco' => 3799],
                ['nome' => 'iPhone 12',         'cat' => 'Smartphone', 'preco' => 2999],
                ['nome' => 'iPhone SE',         'cat' => 'Smartphone', 'preco' => 2499],
                ['nome' => 'Apple Watch Ultra', 'cat' => 'Wearable',   'preco' => 5999],
                ['nome' => 'Apple Watch S9',    'cat' => 'Wearable',   'preco' => 2999],
                ['nome' => 'Apple Watch SE',    'cat' => 'Wearable',   'preco' => 1799],
                ['nome' => 'AirPods Pro 2',     'cat' => 'Audio',      'preco' => 2199],
                ['nome' => 'AirPods 3',         'cat' => 'Audio',      'preco' => 1299],
                ['nome' => 'AirPods 2',         'cat' => 'Audio',      'preco' => 799],
                ['nome' => 'iPad Pro 12.9',     'cat' => 'Tablet',     'preco' => 10999],
                ['nome' => 'iPad Air',          'cat' => 'Tablet',     'preco' => 5999],
                ['nome' => 'iPad Mini',         'cat' => 'Tablet',     'preco' => 4299],
                ['nome' => 'MacBook Air M2',    'cat' => 'Computador', 'preco' => 12999],
            ],
            'origens' => ['WhatsApp', 'Instagram', 'Indicação', 'Google', 'Facebook', 'TikTok'],
            'cidades' => ['São Paulo', 'Guarulhos', 'Osasco', 'Santo André', 'São Bernardo', 'Campinas', 'Sorocaba'],
            'leads'   => 1000,
        ]);
    }

    private function seedEmpresa(array $empresa): void
    {
        $companyId = (string) Str::uuid();

        // ── Empresa ───────────────────────────────────────────
        DB::table('companies')->insert([
            'id'         => $companyId,
            'name'       => $empresa['name'],
            'slug'       => $empresa['slug'],
            'plan'       => $empresa['plan'],
            'active'     => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ── Usuário admin ─────────────────────────────────────
        DB::table('users')->insert([
            'id'         => (string) Str::uuid(),
            'company_id' => $companyId,
            'name'       => $empresa['username'],
            'email'      => $empresa['email'],
            'password'   => Hash::make('password'),
            'role'       => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ── Produtos ──────────────────────────────────────────
        $produtos    = $empresa['produtos'];
        $productIds  = [];
        $productPrecos = [];

        foreach ($produtos as $p) {
            $id = (string) Str::uuid();
            DB::table('products')->insert([
                'id'         => $id,
                'company_id' => $companyId,
                'name'       => $p['nome'],
                'category'   => $p['cat'],
                'avg_price'  => $p['preco'],
                'created_at' => now()->subDays(rand(30, 90)),
                'updated_at' => now(),
            ]);
            $productIds[]    = $id;
            $productPrecos[] = $p['preco'];
        }

        // ── Leads — inserção em lotes de 100 para performance ─
        $statusList  = ['novo', 'em_conversa', 'pediu_preco', 'encaminhado', 'perdido', 'recuperacao'];
        $statusPesos = [15, 25, 20, 15, 15, 10];
        $totalLeads  = $empresa['leads'];

        $leadIds      = [];
        $leadStatuses = [];
        $leadDates    = [];
        $leadSources  = [];

        $this->command->info("   Inserindo {$totalLeads} leads...");

        $lote = [];
        for ($i = 0; $i < $totalLeads; $i++) {
            $id        = (string) Str::uuid();
            $status    = $this->randomWeighted($statusList, $statusPesos);
            $daysAgo   = $i < ($totalLeads * 0.5) ? rand(0, 30) : rand(31, 90);
            $createdAt = now()->subDays($daysAgo)->subHours(rand(0, 23))->subMinutes(rand(0, 59));
            $source    = $empresa['origens'][array_rand($empresa['origens'])];

            $lote[] = [
                'id'            => $id,
                'company_id'    => $companyId,
                'phone'         => $this->fakePhone(),
                'city'          => $empresa['cidades'][array_rand($empresa['cidades'])],
                'status'        => $status,
                'source'        => $source,
                'first_contact' => $createdAt,
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt->copy()->addHours(rand(0, 48)),
            ];

            $leadIds[]      = $id;
            $leadStatuses[] = $status;
            $leadDates[]    = $createdAt->copy();
            $leadSources[]  = $source;

            // Insere em lotes de 100
            if (count($lote) === 100) {
                DB::table('leads')->insert($lote);
                $lote = [];
            }
        }
        if (!empty($lote)) {
            DB::table('leads')->insert($lote);
        }

        // ── Conversas — lotes de 200 ──────────────────────────
        $this->command->info("   Inserindo conversas...");
        $mensagens = $this->mensagens($empresa['slug']);
        $lote = [];

        foreach ($leadIds as $idx => $leadId) {
            $qtd = $leadDates[$idx]->diffInDays(now()) < 15 ? rand(4, 12) : rand(1, 5);

            for ($j = 0; $j < $qtd; $j++) {
                $msgTime = $leadDates[$idx]->copy()->addMinutes(rand($j * 10, $j * 10 + 60));
                $sender  = $j % 2 === 0 ? 'lead' : ($j % 4 === 1 ? 'bot' : 'human');

                $lote[] = [
                    'id'            => (string) Str::uuid(),
                    'company_id'    => $companyId,
                    'lead_id'       => $leadId,
                    'sender'        => $sender,
                    'message'       => $mensagens[array_rand($mensagens)],
                    'response_time' => $sender !== 'lead' ? rand(800, 15000) : null,
                    'created_at'    => $msgTime,
                    'updated_at'    => $msgTime,
                ];

                if (count($lote) === 200) {
                    DB::table('conversations')->insert($lote);
                    $lote = [];
                }
            }
        }
        if (!empty($lote)) {
            DB::table('conversations')->insert($lote);
        }

        // ── Chat Sessions ─────────────────────────────────────
        $this->command->info("   Inserindo chat sessions...");
        $sessionCount  = min(intval($totalLeads * 0.55), count($leadIds));
        $sessionsLeads = (array) array_rand($leadIds, $sessionCount);
        $lote = [];

        foreach ($sessionsLeads as $idx) {
            $started = $leadDates[$idx]->copy()->addMinutes(rand(1, 30));
            $lote[] = [
                'id'                   => (string) Str::uuid(),
                'company_id'           => $companyId,
                'lead_id'              => $leadIds[$idx],
                'started_at'           => $started,
                'ended_at'             => rand(0, 3) !== 0 ? $started->copy()->addMinutes(rand(3, 90)) : null,
                'transferred_to_human' => rand(0, 4) === 0,
                'created_at'           => $started,
                'updated_at'           => $started,
            ];

            if (count($lote) === 100) {
                DB::table('chat_sessions')->insert($lote);
                $lote = [];
            }
        }
        if (!empty($lote)) {
            DB::table('chat_sessions')->insert($lote);
        }

        // ── Interesses em produtos ────────────────────────────
        $this->command->info("   Inserindo interesses em produtos...");
        $interestCount = min(intval($totalLeads * 0.75), count($leadIds));
        $interestLeads = (array) array_rand($leadIds, $interestCount);
        $usedPairs     = [];
        $lote          = [];

        foreach ($interestLeads as $idx) {
            $prodsSorteados = (array) array_rand($productIds, min(rand(1, 4), count($productIds)));
            foreach ($prodsSorteados as $pidx) {
                $pair = $leadIds[$idx] . '_' . $productIds[$pidx];
                if (isset($usedPairs[$pair])) continue;
                $usedPairs[$pair] = true;

                $lote[] = [
                    'id'         => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'lead_id'    => $leadIds[$idx],
                    'product_id' => $productIds[$pidx],
                    'created_at' => $leadDates[$idx]->copy()->addMinutes(rand(5, 120)),
                ];

                if (count($lote) === 200) {
                    DB::table('product_interest')->insert($lote);
                    $lote = [];
                }
            }
        }
        if (!empty($lote)) {
            DB::table('product_interest')->insert($lote);
        }

        // ── Followups ─────────────────────────────────────────
        $lote = [];
        foreach ($leadIds as $idx => $leadId) {
            if (in_array($leadStatuses[$idx], ['perdido', 'recuperacao'])) {
                $createdAt = $leadDates[$idx]->copy()->addDays(rand(1, 5));
                $status    = $leadStatuses[$idx] === 'recuperacao'
                    ? 'recovered'
                    : collect(['pending', 'sent', 'recovered'])->random();

                $lote[] = [
                    'id'         => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'lead_id'    => $leadId,
                    'status'     => $status,
                    'sent_at'    => in_array($status, ['sent', 'recovered'])
                        ? $createdAt->copy()->addHours(rand(1, 24))
                        : null,
                    'recovered'  => $status === 'recovered',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];

                if (count($lote) === 100) {
                    DB::table('followups')->insert($lote);
                    $lote = [];
                }
            }
        }
        if (!empty($lote)) {
            DB::table('followups')->insert($lote);
        }

        // ── Vendas — ~20% dos leads encaminhados fecham venda ─
        $this->command->info("   Inserindo vendas...");
        $lote = [];

        foreach ($leadIds as $idx => $leadId) {
            // Só leads encaminhados ou em recuperação geram venda
            if (!in_array($leadStatuses[$idx], ['encaminhado', 'recuperacao'])) continue;
            if (rand(0, 4) !== 0) continue; // ~20% fecham

            // Cada venda tem 1-2 produtos
            $numVendas = rand(1, 2);
            $prodsSorteados = (array) array_rand($productIds, min($numVendas, count($productIds)));

            foreach ($prodsSorteados as $pidx) {
                $soldAt = $leadDates[$idx]->copy()->addDays(rand(1, 7))->addHours(rand(9, 21));

                // Variação de ±10% no preço
                $preco = $productPrecos[$pidx];
                $valor = round($preco * (1 + (rand(-10, 10) / 100)), 2);

                $lote[] = [
                    'id'         => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'lead_id'    => $leadId,
                    'product_id' => $productIds[$pidx],
                    'value'      => $valor,
                    'notes'      => null,
                    'sold_at'    => $soldAt,
                    'created_at' => $soldAt,
                    'updated_at' => $soldAt,
                ];

                if (count($lote) === 100) {
                    DB::table('sales')->insert($lote);
                    $lote = [];
                }
            }
        }
        if (!empty($lote)) {
            DB::table('sales')->insert($lote);
        }

        // ── Métricas diárias (90 dias) ────────────────────────
        $lote = [];
        for ($i = 89; $i >= 0; $i--) {
            $leadsNoDia = rand(3, intval($totalLeads / 15));
            $lote[] = [
                'id'                => (string) Str::uuid(),
                'company_id'        => $companyId,
                'date'              => now()->subDays($i)->toDateString(),
                'leads'             => $leadsNoDia,
                'conversations'     => $leadsNoDia * rand(3, 10),
                'recovered_leads'   => rand(0, max(1, intval($leadsNoDia * 0.25))),
                'estimated_revenue' => $leadsNoDia * rand(2000, 12000),
            ];
        }
        DB::table('daily_metrics')->insert($lote);

        // ── Insights de IA ────────────────────────────────────
        foreach ($this->insights() as $insight) {
            DB::table('ai_insights')->insert([
                'id'         => (string) Str::uuid(),
                'company_id' => $companyId,
                'insight'    => $insight,
                'created_at' => now()->subHours(rand(1, 72)),
            ]);
        }

        // ── Resumo ────────────────────────────────────────────
        $totalVendas = DB::table('sales')->where('company_id', $companyId)->count();
        $totalConvs  = DB::table('conversations')->where('company_id', $companyId)->count();
        $totalRevenue = DB::table('sales')->where('company_id', $companyId)->sum('value');

        $this->command->info("✅ {$empresa['name']} criada!");
        $this->command->info("   📧 {$empresa['email']} / senha: password");
        $this->command->info("   👥 {$totalLeads} leads");
        $this->command->info("   💬 {$totalConvs} conversas");
        $this->command->info("   💰 {$totalVendas} vendas · R$ " . number_format($totalRevenue, 2, ',', '.'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function fakePhone(): string
    {
        $ddds = ['11', '21', '31', '41', '51', '61', '71', '81', '85', '92'];
        return '(' . $ddds[array_rand($ddds)] . ') 9' . rand(1000, 9999) . '-' . rand(1000, 9999);
    }

    private function randomWeighted(array $items, array $pesos): string
    {
        $total = array_sum($pesos);
        $rand  = rand(1, $total);
        $acum  = 0;
        foreach ($items as $i => $item) {
            $acum += $pesos[$i];
            if ($rand <= $acum) return $item;
        }
        return $items[0];
    }

    private function mensagens(string $slug = ''): array
    {
        return [
            'Olá, tudo bem?',
            'Boa tarde! Ainda tem disponível?',
            'Qual o melhor preço à vista?',
            'Vocês fazem entrega?',
            'Tem garantia?',
            'Pode parcelar no cartão?',
            'Qual o prazo de entrega?',
            'Aceita troca?',
            'Tem em estoque?',
            'Pode me mandar mais informações?',
            'Olá! Temos sim, posso te ajudar!',
            'Claro, temos parcelamento em até 12x.',
            'A garantia é de 12 meses.',
            'Entregamos em todo o Brasil.',
            'Vou verificar o estoque e já te retorno.',
            'Esse é um dos nossos mais vendidos!',
            'Temos promoção essa semana nesse item.',
            'Qual a sua cidade para calcular o frete?',
            'Queria saber o preço do iPhone 15 Pro.',
            'O iPhone 15 aceita chip nacional?',
            'Qual a diferença do Pro para o Pro Max?',
            'Esse iPhone é lacrado ou recondicionado?',
            'Tem o iPhone 14 por menos de R$ 5.000?',
            'Vende AirPods junto com desconto?',
            'O Apple Watch é compatível com Android?',
            'Tem MacBook também?',
            'Qual o iPhone mais barato que tem?',
            'Faz entrega no mesmo dia?',
            'Tem nota fiscal?',
            'Aceita Pix com desconto?',
        ];
    }

    private function insights(): array
    {
        return [
            '68% das buscas são por iPhone 15 Pro Max e iPhone 15 Pro',
            'Pico de atendimento entre 12h e 14h e após 20h',
            'Ticket médio subiu 18% em relação ao mês anterior',
            '34% dos clientes perguntam sobre parcelamento em 12x',
            'Taxa de recuperação de leads perdidos está em 24%',
            'AirPods Pro 2 teve aumento de 31% nas buscas esta semana',
            'Leads via Instagram convertem 2.4x mais que Facebook',
            'Clientes que perguntam sobre troca têm 40% mais chance de fechar',
            'Tempo médio de resposta do bot: 1.8 segundos',
            'iPhone 14 está sendo buscado como alternativa econômica ao 15',
            '22% dos leads abandonam após pedir o preço sem resposta em 10 min',
            'Horário de pico aos sábados: 10h às 12h',
        ];
    }
}