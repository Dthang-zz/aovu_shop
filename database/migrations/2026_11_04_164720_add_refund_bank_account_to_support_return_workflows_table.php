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
        Schema::table('support_return_workflows', function (Blueprint $table) {
            if (! Schema::hasColumn('support_return_workflows', 'refund_bank_name')) {
                $table->string('refund_bank_name', 100)->nullable();
            }

            if (! Schema::hasColumn('support_return_workflows', 'refund_account_number')) {
                $table->string('refund_account_number', 30)->nullable();
            }

            if (! Schema::hasColumn('support_return_workflows', 'refund_account_holder')) {
                $table->string('refund_account_holder', 100)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_return_workflows', function (Blueprint $table) {
            $table->dropColumn(['refund_bank_name', 'refund_account_number', 'refund_account_holder']);
        });
    }
};
