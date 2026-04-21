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
        Schema::table('ine_records', function (Blueprint $table) {
            $table->string('telefono', 20)->nullable()->after('vigencia');
            $table->string('correo', 100)->nullable()->after('telefono');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ine_records', function (Blueprint $table) {
            $table->dropColumn(['telefono', 'correo']);
        });
    }
};
