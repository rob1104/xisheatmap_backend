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
        Schema::create('casa_mesils', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->nullable();
            $table->string('curp')->nullable();
            $table->string('telefono')->nullable();
            $table->text('direccion')->nullable();
            $table->string('zona')->nullable();
            $table->string('estado')->nullable();
            $table->string('municipio')->nullable();
            $table->string('distrito')->nullable();
            $table->string('seccion')->nullable();
            $table->string('manzana')->nullable();
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('casa_mesils');
    }
};
