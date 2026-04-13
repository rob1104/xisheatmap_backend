<?php

namespace Database\Factories;

use App\Models\IneRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class IneRecordFactory extends Factory
{
    protected $model = IneRecord::class;

    public function definition(): array
    {
        return [
            'clave_elector' => $this->faker->word(),
            'curp' => $this->faker->word(),
            'ocr_numero' => $this->faker->word(),
            'nombre' => $this->faker->word(),
            'apellido_paterno' => $this->faker->word(),
            'apellido_materno' => $this->faker->word(),
            'fecha_nacimiento' => Carbon::now(),
            'sexo' => $this->faker->word(),
            'calle_numero' => $this->faker->word(),
            'colonia' => $this->faker->word(),
            'codigo_postal' => $this->faker->postcode(),
            'municipio' => $this->faker->word(),
            'estado' => $this->faker->word(),
            'seccion' => $this->faker->word(),
            'vigencia' => $this->faker->word(),
            'latitud' => $this->faker->randomFloat(),
            'longitud' => $this->faker->randomFloat(),
            'foto_frente_path' => $this->faker->word(),
            'foto_reverso_path' => $this->faker->word(),
            'capturado_en' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'user_id' => User::factory(),
        ];
    }
}
