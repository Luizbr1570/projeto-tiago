<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicatas antes de criar a constraint, caso já existam no banco.
        // Mantém o registro mais antigo (menor created_at) de cada par company_id+phone.
        DB::statement('
            DELETE FROM leads
            WHERE id NOT IN (
                SELECT id FROM (
                    SELECT MIN(id) as id
                    FROM leads
                    GROUP BY company_id, phone
                ) AS keep
            )
        ');

        Schema::table('leads', function (Blueprint $table) {
            // Garante que o mesmo telefone não seja cadastrado duas vezes
            // na mesma empresa. Empresas diferentes podem ter o mesmo número.
            $table->unique(['company_id', 'phone'], 'leads_company_phone_unique');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropUnique('leads_company_phone_unique');
        });
    }
};