<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plant_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('action_type_id')->constrained('plant_action_types')->cascadeOnDelete();
            $table->date('action_date');
            $table->string('title', 255)->nullable();
            $table->text('notes')->nullable();
            $table->string('product_name', 255)->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->string('unit', 30)->nullable();
            $table->string('dosage', 100)->nullable();
            $table->string('method', 255)->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('performer_name', 100)->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('weather_conditions', 100)->nullable();
            $table->boolean('is_private')->default(false);
            $table->timestamps();

            $table->index('plant_id');
            $table->index('action_type_id');
            $table->index('action_date');
            $table->index('performed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_actions');
    }
};
