<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jornadas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('tipo_jornada', 50);
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->string('aviso_privacidad_version', 20)->default('v1.0');
            $table->boolean('activo')->default(false)->index();
            $table->timestamps();

            $table->index(['activo', 'fecha_inicio', 'fecha_fin'], 'idx_jornadas_activas_rango');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jornadas');
    }
};
