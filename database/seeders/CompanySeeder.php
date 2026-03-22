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
                ['nome' => 'MacBook Pro M3',    'cat' => 'Computador', 'preco' => 18999],
                ['nome' => 'Apple TV 4K',       'cat' => 'Acessório',  'preco' => 1299],
            ],
            'origens' => ['WhatsApp', 'Instagram', 'Indicação', 'Google', 'Facebook', 'TikTok', 'YouTube', 'Organic'],
            'cidades' => [
                // SP
                'São Paulo', 'Guarulhos', 'Osasco', 'Santo André', 'São Bernardo do Campo',
                'Campinas', 'Sorocaba', 'Ribeirão Preto', 'São José dos Campos', 'Santos',
                'Mogi das Cruzes', 'Diadema', 'Jundiaí', 'Piracicaba', 'Bauru',
                // RJ
                'Rio de Janeiro', 'Niterói', 'Nova Iguaçu', 'Duque de Caxias', 'São Gonçalo',
                'Belford Roxo', 'São João de Meriti',
                // MG
                'Belo Horizonte', 'Uberlândia', 'Contagem', 'Juiz de Fora', 'Betim',
                // RS
                'Porto Alegre', 'Caxias do Sul', 'Pelotas', 'Canoas',
                // PR
                'Curitiba', 'Londrina', 'Maringá', 'Joinville',
                // BA
                'Salvador', 'Feira de Santana',
                // CE
                'Fortaleza', 'Caucaia',
                // PE
                'Recife', 'Olinda',
                // GO
                'Goiânia', 'Aparecida de Goiânia',
                // AM
                'Manaus',
                // PA
                'Belém', 'Ananindeua',
                // MA
                'São Luís',
                // ES
                'Vila Velha', 'Serra',
                // RN
                'Natal',
                // PB
                'João Pessoa',
                // PI
                'Teresina',
                // MS
                'Campo Grande',
                // MT
                'Cuiabá',
                // AL
                'Maceió',
                // SE
                'Aracaju',
                // RO
                'Porto Velho',
                // AP
                'Macapá',
                // Itaperuna e cidades menores
                'Itaperuna', 'Miracema', 'Campos dos Goytacazes', 'Volta Redonda', 'Macaé',
            ],
            'leads' => 3000,
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
        $produtos      = $empresa['produtos'];
        $productIds    = [];
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

        // ── Leads — 3000, maioria de hoje ─────────────────────
        $statusList  = ['novo', 'em_conversa', 'pediu_preco', 'encaminhado', 'perdido', 'recuperacao'];
        $statusPesos = [10, 20, 20, 20, 15, 15]; // mais encaminhados = mais vendas
        $totalLeads  = $empresa['leads'];

        $leadIds      = [];
        $leadStatuses = [];
        $leadDates    = [];
        $leadSources  = [];

        $this->command->info("   Inserindo {$totalLeads} leads...");

        $lote = [];
        for ($i = 0; $i < $totalLeads; $i++) {
            $id      = (string) Str::uuid();
            $status  = $this->randomWeighted($statusList, $statusPesos);
            $source  = $empresa['origens'][array_rand($empresa['origens'])];
            $cidade  = $empresa['cidades'][array_rand($empresa['cidades'])];

            // 60% dos leads são de hoje, 20% ontem, 20% últimos 30 dias
            $rand = rand(1, 100);
            if ($rand <= 60) {
                $createdAt = now()->subHours(rand(0, 23))->subMinutes(rand(0, 59));
            } elseif ($rand <= 80) {
                $createdAt = now()->subDays(1)->subHours(rand(0, 23));
            } else {
                $createdAt = now()->subDays(rand(2, 30))->subHours(rand(0, 23));
            }

            $lote[] = [
                'id'            => $id,
                'company_id'    => $companyId,
                'phone'         => $this->fakePhone(),
                'city'          => $cidade,
                'status'        => $status,
                'source'        => $source,
                'first_contact' => $createdAt,
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt->copy()->addHours(rand(0, 12)),
            ];

            $leadIds[]      = $id;
            $leadStatuses[] = $status;
            $leadDates[]    = $createdAt->copy();
            $leadSources[]  = $source;

            if (count($lote) === 100) {
                DB::table('leads')->insert($lote);
                $lote = [];
            }
        }
        if (!empty($lote)) {
            DB::table('leads')->insert($lote);
        }

        // ── Conversas — muitas! ───────────────────────────────
        $this->command->info("   Inserindo conversas...");
        $mensagens = $this->mensagens();
        $lote = [];

        foreach ($leadIds as $idx => $leadId) {
            // Leads de hoje têm mais conversas
            $isHoje = $leadDates[$idx]->isToday();
            $qtd    = $isHoje ? rand(8, 20) : rand(3, 10);

            for ($j = 0; $j < $qtd; $j++) {
                $msgTime = $leadDates[$idx]->copy()->addMinutes(rand($j * 5, $j * 5 + 30));
                $sender  = $j % 2 === 0 ? 'lead' : ($j % 4 === 1 ? 'bot' : 'human');

                $lote[] = [
                    'id'            => (string) Str::uuid(),
                    'company_id'    => $companyId,
                    'lead_id'       => $leadId,
                    'sender'        => $sender,
                    'message'       => $mensagens[array_rand($mensagens)],
                    'response_time' => $sender !== 'lead' ? rand(500, 10000) : null,
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
        $sessionCount  = min(intval($totalLeads * 0.70), count($leadIds));
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
                'transferred_to_human' => rand(0, 3) === 0,
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
        $this->command->info("   Inserindo interesses...");
        $interestCount = min(intval($totalLeads * 0.85), count($leadIds));
        $interestLeads = (array) array_rand($leadIds, $interestCount);
        $usedPairs     = [];
        $lote          = [];

        foreach ($interestLeads as $idx) {
            $prodsSorteados = (array) array_rand($productIds, min(rand(1, 5), count($productIds)));
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
                $createdAt = $leadDates[$idx]->copy()->addDays(rand(1, 3));
                $status    = $leadStatuses[$idx] === 'recuperacao'
                    ? 'recovered'
                    : collect(['pending', 'sent', 'recovered'])->random();

                $lote[] = [
                    'id'         => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'lead_id'    => $leadId,
                    'status'     => $status,
                    'sent_at'    => in_array($status, ['sent', 'recovered'])
                        ? $createdAt->copy()->addHours(rand(1, 12))
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

        // ── Vendas — ~50% dos encaminhados fecham, com quantidade ─
        $this->command->info("   Inserindo vendas...");
        $lote = [];

        foreach ($leadIds as $idx => $leadId) {
            if (!in_array($leadStatuses[$idx], ['encaminhado', 'recuperacao'])) continue;
            if (rand(0, 1) !== 0) continue; // ~50% fecham

            $numVendas      = rand(1, 3); // até 3 vendas por lead
            $prodsSorteados = (array) array_rand($productIds, min($numVendas, count($productIds)));

            foreach ($prodsSorteados as $pidx) {
                $soldAt   = $leadDates[$idx]->copy()->addHours(rand(1, 12));
                $preco    = $productPrecos[$pidx];
                $valor    = round($preco * (1 + (rand(-10, 10) / 100)), 2);
                $quantity = rand(1, 3);

                $lote[] = [
                    'id'         => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'lead_id'    => $leadId,
                    'product_id' => $productIds[$pidx],
                    'value'      => round($valor * $quantity, 2),
                    'quantity'   => $quantity,
                    'notes'      => rand(0, 2) === 0 ? $this->fakeNote() : null,
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
            // Hoje tem muito mais movimento
            $isHoje      = $i === 0;
            $leadsNoDia  = $isHoje ? rand(80, 120) : rand(5, intval($totalLeads / 10));
            $lote[] = [
                'id'                => (string) Str::uuid(),
                'company_id'        => $companyId,
                'date'              => now()->subDays($i)->toDateString(),
                'leads'             => $leadsNoDia,
                'conversations'     => $leadsNoDia * rand(5, 15),
                'recovered_leads'   => rand(0, max(1, intval($leadsNoDia * 0.3))),
                'estimated_revenue' => $leadsNoDia * rand(3000, 15000),
            ];
        }
        DB::table('daily_metrics')->insert($lote);

        // ── Insights de IA ────────────────────────────────────
        foreach ($this->insights() as $insight) {
            DB::table('ai_insights')->insert([
                'id'         => (string) Str::uuid(),
                'company_id' => $companyId,
                'insight'    => $insight,
                'created_at' => now()->subHours(rand(1, 48)),
            ]);
        }

        // ── Resumo ────────────────────────────────────────────
        $totalVendas  = DB::table('sales')->where('company_id', $companyId)->count();
        $totalConvs   = DB::table('conversations')->where('company_id', $companyId)->count();
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
        $ddds = ['11', '21', '31', '41', '51', '61', '71', '81', '85', '92', '22', '24', '27', '28', '62', '63', '64', '65', '66', '67', '68', '69', '82', '83', '84', '86', '87', '88', '89', '91', '93', '94', '95', '96', '97', '98', '99'];
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

    private function fakeNote(): string
    {
        $notas = [
            'Cliente pediu nota fiscal',
            'Pagamento via Pix',
            'Parcelado em 12x',
            'Retirada na loja',
            'Entrega expressa',
            'Cliente VIP',
            'Segunda compra',
            'Indicado por amigo',
            'Combo com AirPods',
            'Troca de aparelho antigo',
        ];
        return $notas[array_rand($notas)];
    }

    private function mensagens(): array
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
            'Qual o modelo mais vendido?',
            'Tem alguma promoção ativa?',
            'Posso retirar pessoalmente?',
            'Vocês são loja física ou online?',
            'Têm loja em São Paulo?',
            'Qual a diferença entre iPhone 14 e 15?',
            'O MacBook Air M2 vale a pena?',
            'AirPods Pro 2 tem cancelamento de ruído?',
            'Quanto tempo demora a entrega?',
            'Vocês têm suporte pós-venda?',
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
            'Leads do TikTok cresceram 45% essa semana',
            'MacBook Pro M3 tem ticket médio 2x maior que os demais',
            'Cidades do interior estão respondendo melhor às campanhas',
        ];
    }
}