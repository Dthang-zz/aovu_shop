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
        Schema::create('support_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_token_hash', 64)->index();
            $table->string('customer_name', 100);
            $table->string('customer_email')->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamps();

            $table->index(['status', 'updated_at']);
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_conversation_id')
                ->constrained('support_conversations')
                ->cascadeOnDelete();
            $table->string('sender_type', 20);
            $table->string('sender_name', 100)->nullable();
            $table->text('body');
            $table->timestamps();

            $table->index(['support_conversation_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_conversations');
    }
};
