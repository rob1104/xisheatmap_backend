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
            $table->unsignedTinyInteger('entidad')->default(28);          // 28 = Tamaulipas
            $table->unsignedSmallInteger('municipio')->default(41);       // 41 = Victoria
            $table->string('seccion', 4)->index();                        // Clave electoral (ej. "1563")
            $table->unsignedTinyInteger('distrito_federal')->nullable();  // Distrito Electoral Federal (ej. 5)
            $table->unsignedTinyInteger('distrito_local')->nullable();    // Distrito Electoral Local (ej. 14 o 15)
            $table->unsignedTinyInteger('tipo')->nullable();              // 1: Rural, 2: Urbana, 3: Mixta
            $table->unsignedInteger('control')->nullable();

            // Geometría espacial WGS84 (EPSG:4326) - Polygon y MultiPolygon
            $table->geometry('poligono', srid: 4326);

            // Índice espacial para acelerar ST_Contains / Point-in-Polygon
            $table->spatialIndex('poligono');

            // Unicidad lógica por entidad, municipio y sección
            $table->unique(['entidad', 'municipio', 'seccion'], 'uk_entidad_mun_seccion');

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
