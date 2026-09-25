<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lista_nominal_detalles', function (Blueprint $table) {
            $table->id();

            // Relación con la cabecera del corte oficial
            $table->foreignId('lista_nominal_corte_id')
                ->constrained('lista_nominal_cortes')
                ->cascadeOnDelete();

            // Relación directa con la sección electoral cartográfica
            $table->foreignId('seccion_electoral_id')
                ->constrained('secciones_electorales')
                ->cascadeOnDelete();

            // Cifras oficiales emitidas por la autoridad electoral
            $table->unsignedMediumInteger('total_lista_nominal');          // Denominador principal para cobertura
            $table->unsignedMediumInteger('padron_electoral')->nullable(); // Total empadronados (opcional)
            $table->unsignedMediumInteger('hombres')->nullable();
            $table->unsignedMediumInteger('mujeres')->nullable();
            $table->unsignedMediumInteger('no_binario')->default(0);

            $table->timestamps();

            // Unicidad lógica: un corte no puede duplicar la misma sección electoral
            $table->unique(
                ['lista_nominal_corte_id', 'seccion_electoral_id'],
                'uk_corte_seccion_electoral'
            );

            // Índice compuesto para acelerar JOINs y consultas desde secciones hacia el corte activo
            $table->index(
                ['seccion_electoral_id', 'lista_nominal_corte_id'],
                'idx_seccion_corte'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lista_nominal_detalles');
    }
};
