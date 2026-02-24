<?php

namespace Database\Factories;

use App\Models\ExportRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExportRequest>
 */
class ExportRequestFactory extends Factory
{
    protected $model = ExportRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'team_id' => Team::factory(),
            'type' => $this->faker->randomElement(['excel', 'pdf']),
            'filters' => json_encode([
                'month' => date('m'),
                'year' => date('Y'),
            ]),
            'status' => 'pending',
            'file_path' => null,
            'error_message' => null,
            'file_name' => $this->faker->word().'.xlsx',
        ];
    }
}
