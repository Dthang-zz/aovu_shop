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
            if (! Schema::hasColumn('support_return_workflows', 'refund_proof_path')) {
                $table->string('refund_proof_path')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_return_workflows', function (Blueprint $table) {
            $table->dropColumn('refund_proof_path');
        });
    }
};
