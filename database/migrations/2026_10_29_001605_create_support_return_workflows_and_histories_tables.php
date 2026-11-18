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
        Schema::create('support_return_workflows', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('return_request_id')->unique();
            $table->foreignId('conversation_id')
                ->nullable()
                ->constrained('support_conversations')
                ->nullOnDelete();
            $table->boolean('customer_agreed')->default(false);
            $table->dateTime('customer_agreed_at')->nullable();
            $table->boolean('policy_reviewed')->default(false);
            $table->text('policy_notes')->nullable();
            $table->boolean('collection_waived')->default(false);
            $table->dateTime('received_at')->nullable();
            $table->text('inspection_notes')->nullable();
            $table->unsignedInteger('replacement_variant_id')->nullable();
            $table->string('replacement_name')->nullable();
            $table->decimal('replacement_price', 12, 0)->nullable();
            $table->decimal('price_difference', 12, 0)->default(0);
            $table->boolean('price_difference_settled')->default(false);
            $table->string('price_difference_note')->nullable();
            $table->string('tracking_code', 100)->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->decimal('refund_amount', 12, 0)->nullable();
            $table->string('refund_method', 30)->nullable();
            $table->string('refund_reference', 100)->nullable();
            $table->dateTime('refunded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('support_return_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('return_request_id')->index();
            $table->unsignedTinyInteger('from_status')->nullable();
            $table->unsignedTinyInteger('to_status');
            $table->string('actor_name', 100)->default('CSKH');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['return_request_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_return_histories');
        Schema::dropIfExists('support_return_workflows');
    }
};
