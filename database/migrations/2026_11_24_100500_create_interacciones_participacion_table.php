<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interacciones_participacion', function (Blueprint $table) {
            $table->id();
            $table->string('folio_interno', 40)->unique();
            $table->char('idempotency_uuid', 36)->unique();
            $table->foreignId('jornada_id')->constrained('jornadas');
            $table->foreignId('ciudadano_id')->constrained('ciudadanos');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('seccion_folio_id')->constrained('secciones_electorales');
            $table->string('metodo_validacion', 16)->default('QR');
            $table->unsignedSmallInteger('qr_version_usada')->nullable();
            $table->boolean('consentimiento_datos');
            $table->string('consentimiento_version', 20);
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            $table->string('seccion_gps', 4)->nullable();
            $table->dateTime('fecha_captura_dispositivo')->nullable();
            $table->dateTime('fecha_servidor');
            $table->timestamps();

            $table->unique(['ciudadano_id', 'jornada_id'], 'uk_interaccion_ciudadano_jornada');
            $table->index(['jornada_id', 'seccion_folio_id'], 'idx_interacciones_jornada_seccion_folio');
            $table->index(['jornada_id', 'seccion_gps'], 'idx_interacciones_jornada_seccion_gps');
            $table->index(['jornada_id', 'user_id'], 'idx_interacciones_jornada_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interacciones_participacion');
    }
};
