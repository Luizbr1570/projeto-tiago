<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->uuid('company_id');

            $table->uuid('lead_id');

            $table->string('sender');

            $table->text('message');

            // Timestamps para rastrear criação e atualização
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            // Tempo de resposta em milissegundos
            $table->unsignedInteger('response_time')->nullable()->comment('Tempo de resposta em milissegundos');

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');

            // PERFORMANCE - Índices
            $table->index('company_id');
            $table->index('lead_id');
            $table->index('sender');
            $table->index('response_time');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};