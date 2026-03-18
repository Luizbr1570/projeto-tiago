<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicatas mantendo o registro mais antigo (menor id como desempate)
        DB::statement('
            DELETE FROM leads
            WHERE id IN (
                SELECT id FROM (
                    SELECT id,
                           ROW_NUMBER() OVER (
                               PARTITION BY company_id, phone
                               ORDER BY created_at ASC, id ASC
                           ) as rn
                    FROM leads
                ) t
                WHERE t.rn > 1
            )
        ');

        Schema::table('leads', function (Blueprint $table) {
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