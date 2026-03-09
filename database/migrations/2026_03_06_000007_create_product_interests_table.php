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
        Schema::create('product_interest', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('company_id');

            $table->uuid('lead_id');

            $table->uuid('product_id');

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            // PERFOMANCE

            $table->index('company_id');

            $table->index('lead_id');
            
            $table->index('product_id');

            $table->unique(['lead_id','product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_interest');
    }
};
