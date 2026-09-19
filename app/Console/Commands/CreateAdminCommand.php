<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create 
                            {email? : Correo electrónico del nuevo administrador}
                            {--name= : Nombre completo del administrador}
                            {--password= : Contraseña para la cuenta}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea o promueve un usuario con rol de Administrador en el sistema';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=============================================');
        $this->info('      Creación de Usuario Administrador     ');
        $this->info('=============================================');

        // 1. Obtener y validar el correo electrónico
        $email = $this->argument('email') ?? $this->ask('Correo electrónico del administrador:');
        
        while (!$this->validateField(['email' => $email], ['email' => 'required|email'])) {
            $email = $this->ask('Por favor ingresa un correo electrónico válido:');
        }

        // 2. Verificar si el usuario ya existe
        $user = User::where('email', $email)->first();

        if ($user) {
            $this->warn("Ya existe un usuario con el correo: {$email} (Rol actual: {$user->role})");
            
            if (!$this->confirm('¿Deseas actualizarlo y asignarle el rol de Administrador?', true)) {
                $this->info('Operación cancelada.');
                return Command::SUCCESS;
            }

            // Nombre opcional si ya existe
            $name = $this->option('name') ?? $this->ask('Nombre completo (deja en blanco para conservar el actual):', $user->name);
            
            // Password opcional si ya existe
            $password = $this->option('password');
            if (!$password && $this->confirm('¿Deseas cambiar su contraseña?', false)) {
                $password = $this->askValidPassword();
            }

            $user->name = $name ?: $user->name;
            $user->role = 'Administrador';
            if ($password) {
                $user->password = Hash::make($password);
            }
            if (!$user->email_verified_at) {
                $user->email_verified_at = now();
            }
            $user->save();

            $this->components->info('Usuario actualizado exitosamente con permisos de Administrador.');
            $this->mostrarResumen($user);

            return Command::SUCCESS;
        }

        // 3. Obtener el nombre
        $name = $this->option('name') ?? $this->ask('Nombre completo del administrador:');
        while (!$this->validateField(['name' => $name], ['name' => 'required|string|min:2|max:255'])) {
            $name = $this->ask('El nombre no puede estar vacío. Ingrésalo nuevamente:');
        }

        // 4. Obtener la contraseña
        $password = $this->option('password');
        if (!$password) {
            $password = $this->askValidPassword();
        } else {
            while (!$this->validateField(['password' => $password], ['password' => 'required|string|min:8'])) {
                $this->error('La contraseña proporcionada en la opción debe tener al menos 8 caracteres.');
                $password = $this->askValidPassword();
            }
        }

        // 5. Crear el nuevo Administrador
        $admin = new User([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'Administrador',
        ]);
        $admin->email_verified_at = now();
        $admin->save();

        $this->newLine();
        $this->components->info('¡Administrador creado exitosamente!');
        $this->mostrarResumen($admin);

        return Command::SUCCESS;
    }

    /**
     * Solicita y valida una contraseña oculta por consola con confirmación.
     */
    protected function askValidPassword(): string
    {
        while (true) {
            $password = $this->secret('Ingresa la contraseña (mínimo 8 caracteres):');

            if (!$this->validateField(['password' => $password], ['password' => 'required|string|min:8'])) {
                $this->error('La contraseña debe tener al menos 8 caracteres.');
                continue;
            }

            $confirm = $this->secret('Confirma la contraseña:');

            if ($password !== $confirm) {
                $this->error('Las contraseñas no coinciden. Inténtalo de nuevo.');
                continue;
            }

            return $password;
        }
    }

    /**
     * Valida un campo utilizando el validador de Laravel.
     */
    protected function validateField(array $data, array $rules): bool
    {
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return false;
        }

        return true;
    }

    /**
     * Imprime una tabla resumen con los datos del administrador.
     */
    protected function mostrarResumen(User $user): void
    {
        $this->table(
            ['ID', 'Nombre', 'Correo', 'Rol', 'Verificado el'],
            [[
                $user->id,
                $user->name,
                $user->email,
                $user->role,
                $user->email_verified_at?->toDateTimeString() ?? 'N/A'
            ]]
        );
    }
}
