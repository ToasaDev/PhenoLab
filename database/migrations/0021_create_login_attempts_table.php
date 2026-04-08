<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->index();
            $table->string('username', 255)->nullable();
            $table->string('password_hash', 255)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('reason', 50)->index(); // honeypot, invalid, success
            $table->boolean('is_honeypot')->default(false)->index();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['ip', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
