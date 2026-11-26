<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantillas_mensajes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 60)->unique();
            $table->string('canal', 20);
            $table->text('cuerpo_plantilla');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['canal', 'activo'], 'idx_plantillas_canal_activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantillas_mensajes');
    }
};
