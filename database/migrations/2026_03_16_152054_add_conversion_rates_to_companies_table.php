<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Taxa de conversão sem IA (ex: 0.150 = 15%).
            // Default de 15% baseado em benchmark do setor.
            $table->decimal('conversion_base', 5, 3)->default(0.150)->after('active');

            // Taxa de conversão com IA (ex: 0.178 = 17.8%).
            // Default de 17.8% baseado em benchmark do setor.
            $table->decimal('conversion_with_ai', 5, 3)->default(0.178)->after('conversion_base');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['conversion_base', 'conversion_with_ai']);
        });
    }
};