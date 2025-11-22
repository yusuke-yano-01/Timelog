<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Actor;

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
        Actor::firstOrCreate(
            ['id' => Actor::ADMIN_ID],
            ['name' => '管理者']
        );
        
        Actor::firstOrCreate(
            ['id' => Actor::STAFF_ID],
            ['name' => '従業員']
        );
    }
}
