<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ActorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // IDを指定して作成（既に存在する場合は更新しない）
        \App\Models\Actor::firstOrCreate(
            ['id' => 1],
            ['name' => '管理者']
        );
        
        \App\Models\Actor::firstOrCreate(
            ['id' => 2],
            ['name' => '従業員']
        );
    }
}
