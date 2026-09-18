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
        Schema::table('apoyos', function (Blueprint $table) {
            $table->string('seccion', 4)->nullable()->after('longitud')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apoyos', function (Blueprint $table) {
            $table->dropColumn('seccion');
        });
    }
};
