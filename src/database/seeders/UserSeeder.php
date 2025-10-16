<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

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
        \App\Models\User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('123456'),
            'actor_id' => 1, // 管理者のactor_id
            'registeredflg' => true,
            'email_verified_at' => now(),
        ]);

        // 従業員のサンプルデータを作成（ファクトリーを使用）
        \App\Models\User::factory(5)->create([
            'actor_id' => 2, // 従業員のactor_id
        ]);
    }
}
