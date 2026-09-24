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
        Schema::create('lista_nominal_cortes', function (Blueprint $table) {
            $table->id();
            // Fecha real del corte oficial emitido por la autoridad electoral
                $table->date('fecha_corte')->index();

                // Fuente oficial (ej. "INE - Dirección Ejecutiva del Registro Federal deElectores")
                $table->string('fuente', 150);

                // Contexto adicional o notas del corte
                $table->string('descripcion', 255)->nullable();

                // Solo un corte puede estar marcado como activo a la vez para los cálculos del sistema
                $table->date('is_active')->default(false)->index();

                $table->timestamps();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lista_nominal_cortes');
    }
};
