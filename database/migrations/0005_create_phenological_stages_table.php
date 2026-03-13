<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phenological_stages', function (Blueprint $table) {
            $table->id();
            $table->string('stage_code', 10)->unique();
            $table->string('stage_description', 255);
            $table->integer('main_event_code');
            $table->string('main_event_description', 255);
            $table->string('phenological_scale', 100)->default('BBCH Tela Botanica');
            $table->timestamps();

            $table->index('main_event_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phenological_stages');
    }
};
