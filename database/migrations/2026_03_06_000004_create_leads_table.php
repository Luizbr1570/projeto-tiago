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
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('company_id');

            $table->string('phone');

            $table->timestamp('first_contact')->nullable();

            $table->string('city')->nullable();

            $table->enum('status',['novo','em_conversa','pediu_preco','encaminhado','perdido','recuperacao'])->default('novo');

            $table->string('source')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            // PERFORMANCE

            $table->index('company_id');

            $table->index('phone');

            $table->index('status');

            $table->index(['company_id','status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
