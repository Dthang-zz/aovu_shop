<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Title, excerpt and SEO fields now hold editor HTML, which is longer than the visible text
     * (the 255 / 500 character limits are checked against the visible text instead).
     */
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table): void {
            $table->text('title')->change();
            $table->text('excerpt')->nullable()->change();
            $table->text('seo_title')->nullable()->change();
            $table->text('seo_description')->nullable()->change();
        });
    }

    /**
     * Back to the original lengths; formatted values longer than the old limit would be truncated by the database.
     */
    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table): void {
            $table->string('title')->change();
            $table->string('excerpt', 500)->nullable()->change();
            $table->string('seo_title')->nullable()->change();
            $table->string('seo_description', 500)->nullable()->change();
        });
    }
};
