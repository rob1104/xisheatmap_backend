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
        Schema::create('tarjetas_descuento', function (Blueprint $table) {
            $table->id(); // Este será la base de nuestro folio consecutivo
            $table->string('folio')->unique()->nullable(); // Folio formateado (ej. TD-00001)
            $table->foreignId('ine_record_id')->constrained('ine_records')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users'); // Brigadista que registró
            $table->string('curp'); // Para búsqueda rápida y el QR
            $table->string('email_envio');
            $table->text('qr_data_json'); // Copia de lo que se guardó en el QR
            $table->string('image_path')->nullable(); // Ruta en el storage
            $table->boolean('enviado_por_correo')->default(false);
            $table->timestamp('fecha_validez_fin'); // Cuánto dura la tarjeta
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarjetas_descuento');
    }
};
