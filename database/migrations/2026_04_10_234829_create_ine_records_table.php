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
        Schema::create('ine_records', function (Blueprint $table) {
            $table->id();

            // Relación con el capturista (Quién tomó la foto)
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // --- IDENTIFICADORES ÚNICOS ---
            // La clave de elector tiene exactamente 18 caracteres.
            $table->string('clave_elector', 18)->unique();
            $table->string('curp', 18)->nullable()->unique();
            // El número de OCR está al reverso (13 dígitos), útil para validar que la INE es real
            $table->string('ocr_numero', 13)->nullable();

            // --- DATOS PERSONALES ---
            $table->string('nombre');
            $table->string('apellido_paterno');
            $table->string('apellido_materno')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->char('sexo', 1)->nullable(); // H o M

            // --- DATOS GEOGRÁFICOS Y DE LOCALIZACIÓN ---
            // Estos son los que alimentarán tu mapa de calor en Ciudad Victoria
            $table->string('calle_numero')->nullable();
            $table->string('colonia');
            $table->string('codigo_postal', 5)->nullable();
            $table->string('municipio')->default('VICTORIA');
            $table->string('estado')->default('TAMPS');
            // La sección electoral (4 dígitos) es el dato político más importante
            $table->string('seccion', 4);
            $table->string('vigencia', 4)->nullable(); // Año en que vence (ej. 2028)

            // Coordenadas exactas del momento de la captura (generadas por el GPS del celular)
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();

            // --- ARCHIVOS MULTIMEDIA ---
            // Guardaremos las rutas relativas de donde se guarden las imágenes (ej. storage/app/public/ines/...)
            $table->string('foto_frente_path')->nullable();
            $table->string('foto_reverso_path')->nullable();

            // --- METADATOS DE SINCRONIZACIÓN ---
            // Cuándo se tomó realmente la foto en el celular (offline) vs cuándo llegó al servidor (created_at)
            $table->timestamp('capturado_en')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ine_records');
    }
};
