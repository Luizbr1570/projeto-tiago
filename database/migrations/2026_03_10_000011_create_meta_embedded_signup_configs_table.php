<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_embedded_signup_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->unique();
            $table->string('facebook_app_id');
            $table->string('graph_api_version')->default('v25.0');
            $table->string('configuration_id');
            $table->string('redirect_uri');
            $table->string('integration_status')->nullable();
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamp('last_callback_at')->nullable();
            $table->json('last_error')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['company_id', 'integration_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_embedded_signup_configs');
    }
};
