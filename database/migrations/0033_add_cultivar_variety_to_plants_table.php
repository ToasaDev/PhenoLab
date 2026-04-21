<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plants', function (Blueprint $table) {
            $table->string('cultivar', 100)->nullable()->after('clone_or_accession')
                  ->comment('Cultivar name, e.g. Golden Delicious');
            $table->string('variety', 100)->nullable()->after('cultivar')
                  ->comment('Botanical variety, e.g. var. sylvestris');
        });
    }

    public function down(): void
    {
        Schema::table('plants', function (Blueprint $table) {
            $table->dropColumn(['cultivar', 'variety']);
        });
    }
};
