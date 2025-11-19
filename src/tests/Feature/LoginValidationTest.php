<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Actor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginValidationTest extends TestCase
{
    use RefreshDatabase;

    // RefreshDatabaseが各テストメソッドの実行前にデータベースをリセットするため、
    // setUp()でActorを作成しても消えてしまいます。
    // 各テストメソッドの最初でActorを作成する必要があります。

    /**
     * ログイン認証機能（一般ユーザー）
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     * 
     * テスト内容:
     * - ユーザーを登録する
     * - メールアドレス以外のユーザー情報を入力する
     * - ログインの処理を行う
     * 
     * 期待動作:
     * - 「メールアドレスを入力してください」というバリデーションメッセージが表示される
     */
    public function test_user_login_validation_when_email_is_empty()
    {
        // actorsテーブルにデータを作成（外部キー制約のため必要）
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        // 1. ユーザーを登録する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
            'email_verified_at' => now(),
        ]);

        // 2. メールアドレス以外のユーザー情報を入力する
        $data = [
            'email' => '', // メールアドレスは未入力
            'password' => 'password123',
        ];

        // 3. ログインの処理を行う
        $response = $this->post('/auth/login', $data);

        // 期待動作: 「メールアドレスを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors(['email']);
        
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('email'));
        $this->assertContains('メールアドレスを入力してください', $errors->get('email'));
    }

    /**
     * ログイン認証機能（一般ユーザー）
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     * 
     * テスト内容:
     * - ユーザーを登録する
     * - パスワード以外のユーザー情報を入力する
     * - ログインの処理を行う
     * 
     * 期待動作:
     * - 「パスワードを入力してください」というバリデーションメッセージが表示される
     */
    public function test_user_login_validation_when_password_is_empty()
    {
        // actorsテーブルにデータを作成（外部キー制約のため必要）
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        // 1. ユーザーを登録する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
            'email_verified_at' => now(),
        ]);

        // 2. パスワード以外のユーザー情報を入力する
        $data = [
            'email' => 'test@example.com',
            'password' => '', // パスワードは未入力
        ];

        // 3. ログインの処理を行う
        $response = $this->post('/auth/login', $data);

        // 期待動作: 「パスワードを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors(['password']);
        
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('password'));
        $this->assertContains('パスワードを入力してください', $errors->get('password'));
    }

    /**
     * ログイン認証機能（一般ユーザー）
     * 登録内容と一致しない場合、バリデーションメッセージが表示される
     * 
     * テスト内容:
     * - ユーザーを登録する
     * - 誤ったメールアドレスのユーザー情報を入力する
     * - ログインの処理を行う
     * 
     * 期待動作:
     * - 「ログイン情報が登録されていません」というバリデーションメッセージが表示される
     */
    public function test_user_login_validation_when_credentials_do_not_match()
    {
        // actorsテーブルにデータを作成（外部キー制約のため必要）
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        // 1. ユーザーを登録する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
            'email_verified_at' => now(),
        ]);

        // 2. 誤ったメールアドレスのユーザー情報を入力する
        $data = [
            'email' => 'wrong@example.com', // 誤ったメールアドレス
            'password' => 'password123',
        ];

        // 3. ログインの処理を行う
        $response = $this->post('/auth/login', $data);

        // 期待動作: 「ログイン情報が登録されていません」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors(['login']);
        
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('login'));
        $this->assertContains('ログイン情報が登録されていません', $errors->get('login'));
    }

    /**
     * ログイン認証機能（管理者）
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     * 
     * テスト内容:
     * - ユーザーを登録する
     * - メールアドレス以外のユーザー情報を入力する
     * - ログインの処理を行う
     * 
     * 期待動作:
     * - 「メールアドレスを入力してください」というバリデーションメッセージが表示される
     */
    public function test_admin_login_validation_when_email_is_empty()
    {
        // actorsテーブルにデータを作成（外部キー制約のため必要）
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        // 1. ユーザーを登録する（管理者）
        $user = User::create([
            'name' => '管理者ユーザー',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 1, // 管理者
            'registeredflg' => true,
            'email_verified_at' => now(),
        ]);

        // 2. メールアドレス以外のユーザー情報を入力する
        $data = [
            'email' => '', // メールアドレスは未入力
            'password' => 'password123',
        ];

        // 3. ログインの処理を行う
        $response = $this->post('/admin/login', $data);

        // 期待動作: 「メールアドレスを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors(['email']);
        
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('email'));
        $this->assertContains('メールアドレスを入力してください', $errors->get('email'));
    }

    /**
     * ログイン認証機能（管理者）
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     * 
     * テスト内容:
     * - ユーザーを登録する
     * - パスワード以外のユーザー情報を入力する
     * - ログインの処理を行う
     * 
     * 期待動作:
     * - 「パスワードを入力してください」というバリデーションメッセージが表示される
     */
    public function test_admin_login_validation_when_password_is_empty()
    {
        // actorsテーブルにデータを作成（外部キー制約のため必要）
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        // 1. ユーザーを登録する（管理者）
        $user = User::create([
            'name' => '管理者ユーザー',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 1, // 管理者
            'registeredflg' => true,
            'email_verified_at' => now(),
        ]);

        // 2. パスワード以外のユーザー情報を入力する
        $data = [
            'email' => 'admin@example.com',
            'password' => '', // パスワードは未入力
        ];

        // 3. ログインの処理を行う
        $response = $this->post('/admin/login', $data);

        // 期待動作: 「パスワードを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors(['password']);
        
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('password'));
        $this->assertContains('パスワードを入力してください', $errors->get('password'));
    }

    /**
     * ログイン認証機能（管理者）
     * 登録内容と一致しない場合、バリデーションメッセージが表示される
     * 
     * テスト内容:
     * - ユーザーを登録する
     * - 誤ったメールアドレスのユーザー情報を入力する
     * - ログインの処理を行う
     * 
     * 期待動作:
     * - 「ログイン情報が登録されていません」というバリデーションメッセージが表示される
     */
    public function test_admin_login_validation_when_credentials_do_not_match()
    {
        // actorsテーブルにデータを作成（外部キー制約のため必要）
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        // 1. ユーザーを登録する（管理者）
        $user = User::create([
            'name' => '管理者ユーザー',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 1, // 管理者
            'registeredflg' => true,
            'email_verified_at' => now(),
        ]);

        // 2. 誤ったメールアドレスのユーザー情報を入力する
        $data = [
            'email' => 'wrong@example.com', // 誤ったメールアドレス
            'password' => 'password123',
        ];

        // 3. ログインの処理を行う
        $response = $this->post('/admin/login', $data);

        // 期待動作: 「ログイン情報が登録されていません」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors(['login']);
        
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('login'));
        $this->assertContains('ログイン情報が登録されていません', $errors->get('login'));
    }
}

