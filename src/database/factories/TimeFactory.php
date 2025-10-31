<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TimeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $arrivalHour = $this->faker->numberBetween(8, 10);
        $arrivalMinute = $this->faker->randomElement(['00', '15', '30', '45']);
        $departureHour = $this->faker->numberBetween(17, 20);
        $departureMinute = $this->faker->randomElement(['00', '15', '30', '45']);
        
        return [
            'user_id' => \App\Models\User::factory(),
            'date' => $this->faker->date(),
            'arrival_time' => sprintf('%02d:%s', $arrivalHour, $arrivalMinute),
            'departure_time' => sprintf('%02d:%s', $departureHour, $departureMinute),
            'start_break_time1' => $this->faker->optional()->time('H:i'),
            'end_break_time1' => $this->faker->optional()->time('H:i'),
            'start_break_time2' => $this->faker->optional()->time('H:i'),
            'end_break_time2' => $this->faker->optional()->time('H:i'),
            'note' => $this->faker->optional()->sentence(),
            'application_flg' => $this->faker->optional()->boolean(),
        ];
    }
}
