<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Movimiento>
 */
class MovimientoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'file_id' => \App\Models\Archivo::factory(),
            'banco_id' => \App\Models\Banco::factory(),
            'fecha' => $this->faker->date(),
            'monto' => $this->faker->randomFloat(2, 100, 10000),
            'tipo' => 'abono',
            'referencia' => $this->faker->word,
            'descripcion' => $this->faker->sentence,
            'hash' => $this->faker->md5,
        ];
    }
}
