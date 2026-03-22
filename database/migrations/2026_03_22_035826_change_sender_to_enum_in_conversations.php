<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    public function up(): void
    {
        // Garante que não há valores inválidos antes de alterar o tipo
        DB::table('conversations')
            ->whereNotIn('sender', ['lead', 'bot', 'human'])
            ->update(['sender' => 'lead']);

        DB::statement("ALTER TABLE conversations 
            ALTER COLUMN sender TYPE VARCHAR(5),
            ALTER COLUMN sender SET NOT NULL");

        DB::statement("ALTER TABLE conversations 
            ADD CONSTRAINT conversations_sender_check 
            CHECK (sender IN ('lead', 'bot', 'human'))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE conversations 
            DROP CONSTRAINT IF EXISTS conversations_sender_check");
    }
};