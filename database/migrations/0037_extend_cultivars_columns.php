<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cultivars', function (Blueprint $table) {
            $table->string('breeder', 500)->nullable()->change();
            $table->string('synonyms', 1000)->nullable()->change();
            $table->string('parentage', 500)->nullable()->change();
            $table->string('pollinators', 500)->nullable()->change();
            $table->string('rootstocks', 500)->nullable()->change();
            $table->string('disease_resistance', 500)->nullable()->change();
            $table->string('source', 1000)->nullable()->change();
            $table->string('national_id', 200)->nullable()->change();
            $table->string('eupvp_uuid', 200)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cultivars', function (Blueprint $table) {
            $table->string('breeder', 200)->nullable()->change();
            $table->string('synonyms', 500)->nullable()->change();
            $table->string('parentage', 300)->nullable()->change();
            $table->string('pollinators', 300)->nullable()->change();
            $table->string('rootstocks', 300)->nullable()->change();
            $table->string('disease_resistance', 300)->nullable()->change();
            $table->string('source', 500)->nullable()->change();
            $table->string('national_id', 100)->nullable()->change();
            $table->string('eupvp_uuid', 100)->nullable()->change();
        });
    }
};
