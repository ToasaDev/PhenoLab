<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->foreignId('site_category_id')
                ->nullable()
                ->after('environment')
                ->constrained('site_categories')
                ->nullOnDelete();

            $table->index('site_category_id', 'sites_site_category_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropForeign(['site_category_id']);
            $table->dropIndex('sites_site_category_id_index');
            $table->dropColumn('site_category_id');
        });
    }
};
