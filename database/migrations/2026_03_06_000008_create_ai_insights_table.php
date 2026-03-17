<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_insights', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('company_id');

            $table->text('insight');

            $table->timestamp('created_at')->useCurrent();

            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_insights');
    }
};