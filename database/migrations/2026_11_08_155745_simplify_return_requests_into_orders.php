<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columns added to the legacy return table so the whole return/exchange lives on one row.
     *
     * @var array<int, string>
     */
    private const RETURN_COLUMNS = [
        'so_tien_hoan',
        'ma_giao_dich_hoan',
        'bien_the_thay_the_id',
        'ngay_nhan_hang',
        'ngay_hoan_tat',
    ];

    /**
     * Old workflow statuses folded into the simplified four-step flow.
     *
     * @var array<int, int>
     */
    private const STATUS_MAP = [
        1 => 0, // Đang xử lý → Chờ duyệt
        6 => 2, // Chờ nhận hàng hoàn → Đã duyệt
        8 => 7, // Đã kiểm tra hàng → Đã nhận hàng
        5 => 4, // Đã ghi nhận hoàn tiền → Hoàn tất
        9 => 4, // Đã gửi hàng đổi → Hoàn tất
    ];

    /**
     * Move refund/exchange data from the support workflow tables onto `yeu_cau_doi_tras`, then drop those tables.
     */
    public function up(): void
    {
        if (Schema::hasTable('yeu_cau_doi_tras')) {
            Schema::table('yeu_cau_doi_tras', function (Blueprint $table): void {
                if (! Schema::hasColumn('yeu_cau_doi_tras', 'so_tien_hoan')) {
                    $table->decimal('so_tien_hoan', 12, 0)->nullable();
                }
                if (! Schema::hasColumn('yeu_cau_doi_tras', 'ma_giao_dich_hoan')) {
                    $table->string('ma_giao_dich_hoan', 100)->nullable();
                }
                if (! Schema::hasColumn('yeu_cau_doi_tras', 'bien_the_thay_the_id')) {
                    $table->unsignedInteger('bien_the_thay_the_id')->nullable();
                }
                if (! Schema::hasColumn('yeu_cau_doi_tras', 'ngay_nhan_hang')) {
                    $table->dateTime('ngay_nhan_hang')->nullable();
                }
                if (! Schema::hasColumn('yeu_cau_doi_tras', 'ngay_hoan_tat')) {
                    $table->dateTime('ngay_hoan_tat')->nullable();
                }
            });

            if (Schema::hasTable('support_return_workflows')) {
                DB::table('support_return_workflows')
                    ->orderBy('id')
                    ->get()
                    ->each(function (object $workflow): void {
                        DB::table('yeu_cau_doi_tras')
                            ->where('id', $workflow->return_request_id)
                            ->update([
                                'so_tien_hoan' => $workflow->refund_amount ?? null,
                                'ma_giao_dich_hoan' => $workflow->refund_reference ?? null,
                                'bien_the_thay_the_id' => $workflow->replacement_variant_id ?? null,
                                'ngay_nhan_hang' => $workflow->received_at ?? null,
                                'ngay_hoan_tat' => ($workflow->refunded_at ?? null) ?: ($workflow->delivered_at ?? null),
                            ]);
                    });
            }

            foreach (self::STATUS_MAP as $oldStatus => $newStatus) {
                DB::table('yeu_cau_doi_tras')
                    ->where('trang_thai', $oldStatus)
                    ->update(['trang_thai' => $newStatus]);
            }
        }

        Schema::dropIfExists('support_return_histories');
        Schema::dropIfExists('support_return_workflows');
    }

    /**
     * Recreate the empty support workflow tables and remove the added columns.
     * Migrated refund data and the old intermediate statuses are not restored.
     */
    public function down(): void
    {
        if (! Schema::hasTable('support_return_workflows')) {
            Schema::create('support_return_workflows', function (Blueprint $table): void {
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
                $table->dateTime('restocked_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('support_return_histories')) {
            Schema::create('support_return_histories', function (Blueprint $table): void {
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

        if (Schema::hasTable('yeu_cau_doi_tras')) {
            $existingColumns = array_values(array_filter(
                self::RETURN_COLUMNS,
                fn (string $column): bool => Schema::hasColumn('yeu_cau_doi_tras', $column),
            ));

            if ($existingColumns !== []) {
                Schema::table('yeu_cau_doi_tras', function (Blueprint $table) use ($existingColumns): void {
                    $table->dropColumn($existingColumns);
                });
            }
        }
    }
};
