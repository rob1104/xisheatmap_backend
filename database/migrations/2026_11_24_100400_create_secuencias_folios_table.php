<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secuencias_folios', function (Blueprint $table) {
            $table->foreignId('jornada_id')->constrained('jornadas')->cascadeOnDelete();
            $table->foreignId('seccion_electoral_id')->constrained('secciones_electorales');
            $table->unsignedInteger('ultimo_consecutivo')->default(0);

            $table->primary(['jornada_id', 'seccion_electoral_id'], 'pk_secuencias_folios');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secuencias_folios');
    }
};
