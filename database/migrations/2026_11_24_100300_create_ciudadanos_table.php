<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ciudadanos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ine_record_id')->nullable()->unique()->constrained('ine_records')->nullOnDelete();
            $table->string('clave_unica', 36)->unique();
            $table->string('nombre', 120);
            $table->string('apellido_paterno', 120);
            $table->string('apellido_materno', 120)->nullable();
            $table->string('telefono', 20)->nullable()->index();
            $table->foreignId('seccion_electoral_id')->constrained('secciones_electorales');
            $table->unsignedSmallInteger('qr_version')->default(1);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['seccion_electoral_id', 'updated_at', 'id'], 'idx_ciudadanos_sync_delta');
            $table->index(['apellido_paterno', 'apellido_materno'], 'idx_ciudadanos_apellidos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ciudadanos');
    }
};
