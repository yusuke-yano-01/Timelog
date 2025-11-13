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
        
        if ($users->isEmpty()) {
            return;
        }
        
        // サンプルタイムデータを作成（既存のユーザーを使用）
        for ($i = 0; $i < 20; $i++) {
            $time = \App\Models\Time::create([
                'user_id' => $users->random()->id,
                'date' => \Carbon\Carbon::now()->subDays(rand(1, 30)),
                'arrival_time' => sprintf('%02d:%02d', rand(8, 10), rand(0, 59)),
                'departure_time' => sprintf('%02d:%02d', rand(17, 20), rand(0, 59)),
                'note' => rand(0, 1) ? 'サンプルデータ' : null,
            ]);
            
            // 休憩時間を追加
            if (rand(0, 1)) {
                \App\Models\Breaktime::create([
                    'time_id' => $time->id,
                    'start_break_time' => sprintf('%02d:%02d', rand(12, 13), rand(0, 59)),
                    'end_break_time1' => sprintf('%02d:%02d', rand(13, 14), rand(0, 59)),
                ]);
            }
        }
    }
}
