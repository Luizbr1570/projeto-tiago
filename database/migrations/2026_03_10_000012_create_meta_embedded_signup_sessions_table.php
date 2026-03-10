<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_embedded_signup_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('meta_embedded_signup_config_id')->nullable();
            $table->string('source')->default('embedded_signup');
            $table->string('event_type')->nullable();
            $table->string('connection_status')->nullable();
            $table->string('waba_id')->nullable();
            $table->string('phone_number_id')->nullable();
            $table->string('business_id')->nullable();
            $table->string('display_name')->nullable();
            $table->string('code')->nullable();
            $table->text('access_token')->nullable();
            $table->json('setup_info')->nullable();
            $table->json('raw_payload');
            $table->json('normalized_payload')->nullable();
            $table->timestamp('meta_timestamp')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('meta_embedded_signup_config_id', 'meta_signup_sessions_config_fk')
                ->references('id')
                ->on('meta_embedded_signup_configs')
                ->nullOnDelete();

            $table->index(['company_id', 'created_at']);
            $table->index(['company_id', 'phone_number_id']);
            $table->index(['company_id', 'waba_id']);
            $table->index(['company_id', 'business_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_embedded_signup_sessions');
    }
};
