<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_logisticas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interaccion_id')->constrained('interacciones_participacion')->cascadeOnDelete();
            $table->foreignId('seccion_electoral_id')->constrained('secciones_electorales');
            $table->foreignId('tipo_apoyo_id')->constrained('tipos_apoyo');
            $table->text('descripcion_operativa')->nullable();
            $table->string('estatus', 20)->default('PENDIENTE');
            $table->foreignId('atendido_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('fecha_cierre')->nullable();
            $table->timestamps();

            $table->index(['estatus', 'seccion_electoral_id'], 'idx_solicitudes_estatus_seccion');
            $table->index('interaccion_id', 'idx_solicitudes_interaccion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_logisticas');
    }
};
