<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Archivo>
 */
class ArchivoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'original_name' => $this->faker->word.'.xml',
            'path' => $this->faker->filePath(),
            'mime' => 'application/xml',
            'size' => $this->faker->numberBetween(1000, 50000),
            'checksum' => $this->faker->md5,
            'estatus' => 'procesado',
        ];
    }
}
