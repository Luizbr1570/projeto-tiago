<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('lead_id');
            $table->uuid('product_id')->nullable();
            $table->decimal('value', 10, 2);
            $table->string('notes')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();

            $table->index('company_id');
            $table->index('lead_id');
            $table->index('product_id');
            $table->index('sold_at');
            $table->index(['company_id', 'sold_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};