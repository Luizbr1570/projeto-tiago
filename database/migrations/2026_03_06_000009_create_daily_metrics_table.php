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
        Schema::create('daily_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('company_id');

            $table->date('date');

            $table->integer('leads')->default(0);

            $table->integer('conversations')->default(0);

            $table->integer('recovered_leads')->default(0);

            $table->decimal('estimated_revenue', 10, 2)->default(0);

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            // PERFORMANCE

            $table->index('company_id');

            $table->index('date');
            
            $table->unique(['company_id','date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_metrics');
    }
};
