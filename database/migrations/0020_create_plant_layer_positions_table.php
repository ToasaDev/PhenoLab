<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plant_layer_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layer_id')->constrained('site_plan_layers')->cascadeOnDelete();
            $table->foreignId('plant_id')->constrained('plants')->cascadeOnDelete();
            $table->decimal('map_position_x', 5, 2)->nullable();
            $table->decimal('map_position_y', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['layer_id', 'plant_id']);
            $table->index('plant_id');
        });

        // Backfill from existing plants.layer_id + map_position_x/y cache
        $rows = DB::table('plants')
            ->whereNotNull('layer_id')
            ->whereNotNull('map_position_x')
            ->whereNotNull('map_position_y')
            ->select('id as plant_id', 'layer_id', 'map_position_x', 'map_position_y')
            ->get();

        $now = now();
        foreach ($rows as $row) {
            DB::table('plant_layer_positions')->insert([
                'layer_id'       => $row->layer_id,
                'plant_id'       => $row->plant_id,
                'map_position_x' => $row->map_position_x,
                'map_position_y' => $row->map_position_y,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_layer_positions');
    }
};
