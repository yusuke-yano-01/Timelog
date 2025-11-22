<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Actor;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 管理者アカウントを作成
        User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('123456'),
            'actor_id' => Actor::ADMIN_ID,
            'registeredflg' => true,
            'email_verified_at' => now(),
        ]);

        // 従業員のサンプルデータを作成（ファクトリーを使用）
        User::factory(5)->create([
            'actor_id' => Actor::STAFF_ID,
        ]);
    }
}
