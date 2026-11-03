<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $productIdIsUnsigned = $this->productIdIsUnsigned();

        if (! Schema::hasTable('product_variants')) {
            Schema::create('product_variants', function (Blueprint $table) use ($productIdIsUnsigned) {
                $table->id();
                $table->integer('product_id', unsigned: $productIdIsUnsigned);
                $table->string('sku')->unique();
                $table->string('color')->nullable();
                $table->string('size')->nullable();
                $table->decimal('price', 12, 2)->nullable();
                $table->integer('stock')->default(0);
                $table->string('status')->default('active');
                $table->timestamps();

                $table->foreign('product_id')->references('id')->on('san_phams')->cascadeOnDelete();
            });

            return;
        }

        // A previous run may have created the table and then failed on the foreign key, so align the column first.
        Schema::table('product_variants', function (Blueprint $table) use ($productIdIsUnsigned) {
            $table->integer('product_id', unsigned: $productIdIsUnsigned)->change();
        });

        $hasSkuUnique = collect(Schema::getIndexes('product_variants'))
            ->contains(fn (array $index): bool => $index['unique'] && $index['columns'] === ['sku']);
        $hasProductForeignKey = collect(Schema::getForeignKeys('product_variants'))
            ->contains(fn (array $foreignKey): bool => $foreignKey['columns'] === ['product_id']);

        Schema::table('product_variants', function (Blueprint $table) use ($hasSkuUnique, $hasProductForeignKey) {
            if (! $hasSkuUnique) {
                $table->unique('sku');
            }

            if (! $hasProductForeignKey) {
                $table->foreign('product_id')->references('id')->on('san_phams')->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }

    /**
     * Match san_phams.id, which is a signed INT when the table comes from the aovu_shop.sql dump.
     */
    private function productIdIsUnsigned(): bool
    {
        $idColumn = collect(Schema::getColumns('san_phams'))->firstWhere('name', 'id');

        return $idColumn === null || str_contains(strtolower((string) $idColumn['type']), 'unsigned');
    }
};
