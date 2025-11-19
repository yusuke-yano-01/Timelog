<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Actor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 名前が未入力の場合、バリデーションメッセージが表示される
     * 
     * テスト内容:
     * - 名前以外のユーザー情報を入力する
     * - 会員登録の処理を行う
     * 
     * 期待動作:
     * - 「お名前を入力してください」というバリデーションメッセージが表示される
     */
    public function test_name_validation_when_name_is_empty()
    {
        // 1. 名前以外のユーザー情報を入力する
        $data = [
            'name' => '', // 名前は未入力
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // 2. 会員登録の処理を行う
        $response = $this->post('/auth/register', $data);

        // 期待動作: 「お名前を入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors(['name']);
        
        // バリデーションメッセージの内容を確認
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('name'));
        $this->assertContains('お名前を入力してください', $errors->get('name'));
    }

    /**
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     * 
     * テスト内容:
     * - メールアドレス以外のユーザー情報を入力する
     * - 会員登録の処理を行う
     * 
     * 期待動作:
     * - 「メールアドレスを入力してください」というバリデーションメッセージが表示される
     */
    public function test_email_validation_when_email_is_empty()
    {
        // 1. メールアドレス以外のユーザー情報を入力する
        $data = [
            'name' => 'テストユーザー',
            'email' => '', // メールアドレスは未入力
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // 2. 会員登録の処理を行う
        $response = $this->post('/auth/register', $data);

        // 期待動作: 「メールアドレスを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors(['email']);
        
        // バリデーションメッセージの内容を確認
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('email'));
        $this->assertContains('メールアドレスを入力してください', $errors->get('email'));
    }

    /**
     * パスワードが8文字未満の場合、バリデーションメッセージが表示される
     * 
     * テスト内容:
     * - パスワードを8文字未満にし、ユーザー情報を入力する
     * - 会員登録の処理を行う
     * 
     * 期待動作:
     * - 「パスワードは8文字以上で入力してください。」というバリデーションメッセージが表示される
     */
    public function test_password_validation_when_password_is_less_than_8_characters()
    {
        // 1. パスワードを8文字未満にし、ユーザー情報を入力する
        $data = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'pass123', // 7文字（8文字未満）
            'password_confirmation' => 'pass123',
        ];

        // 2. 会員登録の処理を行う
        $response = $this->post('/auth/register', $data);

        // 期待動作: 「パスワードは8文字以上で入力してください。」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors(['password']);
        
        // バリデーションメッセージの内容を確認
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('password'));
        $this->assertContains('パスワードは8文字以上で入力してください。', $errors->get('password'));
    }

    /**
     * パスワードが一致しない場合、バリデーションメッセージが表示される
     * 
     * テスト内容:
     * - 確認用のパスワードとパスワードを一致させず、ユーザー情報を入力する
     * - 会員登録の処理を行う
     * 
     * 期待動作:
     * - 「パスワードと一致しません」というバリデーションメッセージが表示される
     */
    public function test_password_validation_when_passwords_do_not_match()
    {
        // 1. 確認用のパスワードとパスワードを一致させず、ユーザー情報を入力する
        $data = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password456', // パスワードが一致しない
        ];

        // 2. 会員登録の処理を行う
        $response = $this->post('/auth/register', $data);

        // 期待動作: 「パスワードと一致しません」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors(['password']);
        
        // バリデーションメッセージの内容を確認
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('password'));
        $this->assertContains('パスワードと一致しません', $errors->get('password'));
    }

    /**
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     * 
     * テスト内容:
     * - パスワード以外のユーザー情報を入力する
     * - 会員登録の処理を行う
     * 
     * 期待動作:
     * - 「パスワードを入力してください」というバリデーションメッセージが表示される
     */
    public function test_password_validation_when_password_is_empty()
    {
        // 1. パスワード以外のユーザー情報を入力する
        $data = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '', // パスワードは未入力
            'password_confirmation' => '',
        ];

        // 2. 会員登録の処理を行う
        $response = $this->post('/auth/register', $data);

        // 期待動作: 「パスワードを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors(['password']);
        
        // バリデーションメッセージの内容を確認
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('password'));
        $this->assertContains('パスワードを入力してください', $errors->get('password'));
    }

    /**
     * フォームに内容が入力されていた場合、データが正常に保存される
     * 
     * テスト内容:
     * - ユーザー情報を入力する
     * - 会員登録の処理を行う
     * 
     * 期待動作:
     * - データベースに登録したユーザー情報が保存される
     */
    public function test_user_registration_saves_data_when_form_is_valid()
    {
        // actorsテーブルにデータを作成（外部キー制約のため必要）
        Actor::create(['name' => '管理者']);
        Actor::create(['name' => '従業員']);

        // 1. ユーザー情報を入力する
        $data = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // 2. 会員登録の処理を行う
        $response = $this->post('/auth/register', $data);

        // バリデーションエラーがないことを確認
        $response->assertSessionHasNoErrors();
        
        // リダイレクトが正しく行われることを確認（302リダイレクト）
        $response->assertStatus(302);
        $response->assertRedirect(route('verification.notice'));
        
        // 期待動作: データベースに登録したユーザー情報が保存される
        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'actor_id' => 2, // 従業員のactor_id
            'registeredflg' => true,
        ]);

        // パスワードがハッシュ化されて保存されていることを確認
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('password123', $user->password));
    }
}

