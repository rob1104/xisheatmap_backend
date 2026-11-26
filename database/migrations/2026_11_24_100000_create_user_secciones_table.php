<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_secciones', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seccion_electoral_id')->constrained('secciones_electorales')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['user_id', 'seccion_electoral_id'], 'pk_user_secciones');
            $table->index('seccion_electoral_id', 'idx_user_secciones_seccion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_secciones');
    }
};
