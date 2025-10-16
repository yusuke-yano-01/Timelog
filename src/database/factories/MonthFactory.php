<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MonthFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $year = $this->faker->numberBetween(2020, 2025);
        $month = $this->faker->numberBetween(1, 12);
        
        return [
            'year' => $year,
            'month' => $month,
            'end_date' => $this->faker->numberBetween(28, 31),
        ];
    }
}
