<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    public function test_can_create_admin_with_arguments_and_options(): void
    {
        $email = 'admin_test_' . uniqid() . '@example.com';
        $name = 'Administrador de Pruebas';
        $password = 'SecretPass123!';

        $this->artisan('admin:create', [
            'email' => $email,
            '--name' => $name,
            '--password' => $password,
        ])
        ->assertSuccessful();

        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertEquals($name, $user->name);
        $this->assertEquals('Administrador', $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check($password, $user->password));

        // Cleanup
        $user->delete();
    }

    public function test_can_promote_existing_user_to_admin(): void
    {
        $user = User::factory()->create([
            'role' => 'Capturista',
        ]);

        $this->artisan('admin:create', [
            'email' => $user->email,
        ])
        ->expectsConfirmation('¿Deseas actualizarlo y asignarle el rol de Administrador?', 'yes')
        ->expectsQuestion('Nombre completo (deja en blanco para conservar el actual):', $user->name)
        ->expectsConfirmation('¿Deseas cambiar su contraseña?', 'no')
        ->assertSuccessful();

        $user->refresh();
        $this->assertEquals('Administrador', $user->role);

        // Cleanup
        $user->delete();
    }
}
