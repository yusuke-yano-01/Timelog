<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 既存のユーザーを取得
        $users = \App\Models\User::all();
        $months = \App\Models\Month::all();
        
        if ($users->isEmpty() || $months->isEmpty()) {
            return;
        }
        
        // サンプルタイムデータを作成（既存のユーザーと月を使用）
        for ($i = 0; $i < 20; $i++) {
            \App\Models\Time::create([
                'user_id' => $users->random()->id,
                'month_id' => $months->random()->id,
                'date' => \Carbon\Carbon::now()->subDays(rand(1, 30)),
                'arrival_time' => sprintf('%02d:%02d', rand(8, 10), rand(0, 59)),
                'departure_time' => sprintf('%02d:%02d', rand(17, 20), rand(0, 59)),
                'start_break_time1' => rand(0, 1) ? sprintf('%02d:%02d', rand(12, 13), rand(0, 59)) : null,
                'end_break_time1' => rand(0, 1) ? sprintf('%02d:%02d', rand(13, 14), rand(0, 59)) : null,
                'start_break_time2' => null,
                'end_break_time2' => null,
                'note' => rand(0, 1) ? 'サンプルデータ' : null,
                'application_flg' => rand(0, 1) ? true : false,
            ]);
        }
    }
}
