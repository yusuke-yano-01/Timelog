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
        \App\Models\Actor::create([
            'name' => '管理者',
        ]);
        
        \App\Models\Actor::create([
            'name' => '従業員',
        ]);
    }
}
