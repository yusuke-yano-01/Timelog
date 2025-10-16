<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MonthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 2024年の各月のデータを作成
        for ($month = 1; $month <= 12; $month++) {
            \App\Models\Month::create([
                'year' => 2024,
                'month' => $month,
                'end_date' => \Carbon\Carbon::create(2024, $month)->daysInMonth,
            ]);
        }
        
        // 2025年の各月のデータを作成
        for ($month = 1; $month <= 12; $month++) {
            \App\Models\Month::create([
                'year' => 2025,
                'month' => $month,
                'end_date' => \Carbon\Carbon::create(2025, $month)->daysInMonth,
            ]);
        }
    }
}
