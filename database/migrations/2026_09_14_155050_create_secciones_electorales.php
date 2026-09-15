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
        Schema::create('secciones_electorales', function (Blueprint $table) {
            $table->id();

            // Jerarquía administrativa INE
            $table->unsignedTinyInteger('entidad')->default(28);            // 28 = Tamaulipas
            $table->unsignedSmallInterger('municipio')->default(41);        // 41 = Victoria
            $table->string('seccion', 4)->index();                          // ej. "1563"
            $table->unsignedTinyInterger('distrito_federal')->nullable();   // ej. 5
            $table->unsignedTinyInterger('distrito_local')->nullable();     // ej. 14 o 15
            $table->unsignedTinyInterger('tipo')->nullable();               // 2: Urbana, 1:

            $table->unsignedInteger('control')->nullable();

            // Geometria espacial WGS84 (EPSG:4326)
            // Se usa GEOMETRY para soportar tanto Polygon como MultiPolygon (enclaves/islas)
            $table->geometry('poligono', scrid: 4326);

            // Índice espacial para accelerar ST_Contains / ST_Within
            $table->spatialIndex('poligono');

            // Unicidad lógica: una sección no se repite dentro del mismo municipio y entidad
            $table->unique(['entidad','municipio','seccion'],'uk_entidad_mun_seccion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('secciones_electorales');
    }
};
