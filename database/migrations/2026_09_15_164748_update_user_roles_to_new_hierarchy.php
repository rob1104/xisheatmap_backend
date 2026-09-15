<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Enums\UserRole;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar roles existentes al nuevo esquema
        DB::table('users')->where('role', 'Capturista')->update(['role' => UserRole::GESTOR_SECCIONAL->value]);
        DB::table('users')->where('role', 'capturista')->update(['role' => UserRole::GESTOR_SECCIONAL->value]);
        DB::table('users')->where('role', 'Supervisor')->update(['role' => UserRole::COORDINADOR_SECTOR->value]);
        DB::table('users')->where('role', 'supervisor')->update(['role' => UserRole::COORDINADOR_SECTOR->value]);
        DB::table('users')->where('role', 'Administrador')->update(['role' => UserRole::ADMINISTRADOR->value]);
        DB::table('users')->where('role', 'administrador')->update(['role' => UserRole::ADMINISTRADOR->value]);
        
        // Ensure default role is gestor seccional if it was capturista
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default(UserRole::GESTOR_SECCIONAL->value)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('role', UserRole::GESTOR_SECCIONAL->value)->update(['role' => 'Capturista']);
        DB::table('users')->where('role', UserRole::COORDINADOR_SECTOR->value)->update(['role' => 'Supervisor']);
        
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('capturista')->change();
        });
    }
};
