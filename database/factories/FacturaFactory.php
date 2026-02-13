<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Factura>
 */
class FacturaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'file_id_xml' => \App\Models\Archivo::factory(),
            'uuid' => $this->faker->uuid,
            'monto' => $this->faker->randomFloat(2, 100, 10000),
            'fecha_emision' => $this->faker->date(),
            'rfc' => $this->faker->regexify('[A-Z]{4}\d{6}[A-Z0-9]{3}'),
            'nombre' => $this->faker->company,
            'verificado' => true,
        ];
    }
}
