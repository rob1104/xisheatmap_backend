<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('dispositivo_id', 100);
            $table->string('fcm_token', 255);
            $table->char('fcm_token_hash', 64)->unique();
            $table->string('plataforma', 20)->default('ANDROID');
            $table->boolean('activo')->default(true);
            $table->dateTime('last_seen_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'dispositivo_id'], 'uk_user_devices_user_dispositivo');
            $table->index(['user_id', 'activo'], 'idx_user_devices_user_activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
