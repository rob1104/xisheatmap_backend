<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones_log', function (Blueprint $table) {
            $table->id();
            $table->string('referencia_type', 120);
            $table->unsignedBigInteger('referencia_id');
            $table->foreignId('destinatario_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo_destinatario', 20);
            $table->string('canal', 20);
            $table->foreignId('mensaje_plantilla_id')->constrained('plantillas_mensajes');
            $table->string('estado_envio', 20)->default('PENDIENTE');
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->string('provider_message_id', 191)->nullable();
            $table->text('error_detalle')->nullable();
            $table->dateTime('fecha_envio')->nullable();
            $table->timestamps();

            $table->index(['referencia_type', 'referencia_id'], 'idx_notificaciones_referencia');
            $table->index(['estado_envio', 'canal'], 'idx_notificaciones_estado_canal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones_log');
    }
};
