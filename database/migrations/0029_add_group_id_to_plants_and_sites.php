<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plants', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('owner_id')
                  ->constrained('user_groups')->nullOnDelete();
            $table->index('group_id');
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('owner_id')
                  ->constrained('user_groups')->nullOnDelete();
            $table->index('group_id');
        });
    }

    public function down(): void
    {
        Schema::table('plants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
        });
    }
};
