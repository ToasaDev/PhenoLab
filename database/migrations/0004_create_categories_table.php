<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->string('category_type', 20)->comment('trees, shrubs, plants, animals, insects');
            $table->timestamps();

            $table->index('category_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
