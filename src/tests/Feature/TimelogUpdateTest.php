<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Actor;
use App\Models\Time;
use App\Models\Breaktime;
use App\Models\Application;
use App\Models\ApplicationBreaktime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Tests\TestCase;

class TimelogUpdateTest extends TestCase
{
    use RefreshDatabase;

    // RefreshDatabaseが各テストメソッドの実行前にデータベースをリセットするため、
    // setUp()でActorを作成しても消えてしまいます。
    // 各テストメソッドの最初でActorを作成する必要があります。

    /**
     * 勤怠詳細情報修正機能（一般ユーザー）
     * 修正申請処理が実行される
     * 
     * テスト内容:
     * - 勤怠情報が登録されたユーザーにログインをする
     * - 勤怠詳細を修正し保存処理をする
     * - 管理者ユーザーで承認画面と申請一覧画面を確認する
     * 
     * 期待動作:
     * - 修正申請が実行され、管理者の承認画面と申請一覧画面に表示される
     */
    public function test_user_timelog_update_creates_application()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $user->markEmailAsVerified(); // メール認証を完了させる

        $today = Carbon::today();
        $time = Time::create([
            'user_id' => $user->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $response = $this->actingAs($user)->post('/timelog/update', [
            'time_id' => $time->id,
            'date' => $today->format('Y-m-d'),
            'arrival_time' => '09:30',
            'departure_time' => '18:30',
            'note' => '修正申請テスト',
        ]);

        $response->assertRedirect();
        
        // 申請が作成されていることを確認
        $application = Application::where('user_id', $user->id)
            ->where('time_id', $time->id)
            ->first();
        $this->assertNotNull($application);
        $this->assertEquals(1, $application->application_flg); // 申請中
    }

    /**
     * 勤怠詳細情報修正機能（一般ユーザー）
     * 「承認待ち」にログインユーザーが行った申請が全て表示されていること
     * 
     * テスト内容:
     * - 勤怠情報が登録されたユーザーにログインをする
     * - 勤怠詳細を修正し保存処理をする
     * - 申請一覧画面を確認する
     * 
     * 期待動作:
     * - 申請一覧に自分の申請が全て表示されている
     */
    public function test_user_application_list_displays_pending_applications()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $user->markEmailAsVerified(); // メール認証を完了させる

        $today = Carbon::today();
        $time = Time::create([
            'user_id' => $user->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        Application::create([
            'user_id' => $user->id,
            'time_id' => $time->id,
            'date' => $today,
            'arrival_time' => '09:30',
            'departure_time' => '18:30',
            'note' => '修正申請',
            'application_flg' => 1, // 申請中
        ]);

        $response = $this->actingAs($user)->get('/timelog/application?status=pending');

        $response->assertStatus(200);
        $response->assertViewHas('applications');
        
        $applications = $response->viewData('applications');
        $this->assertGreaterThan(0, $applications->count());
    }

    /**
     * 勤怠詳細情報修正機能（一般ユーザー）
     * 「承認済み」に管理者が承認した修正申請が全て表示されている
     * 
     * テスト内容:
     * - 勤怠情報が登録されたユーザーにログインをする
     * - 勤怠詳細を修正し保存処理をする
     * - 申請一覧画面を開く
     * - 管理者が承認した修正申請が全て表示されていることを確認
     * 
     * 期待動作:
     * - 承認済みに管理者が承認した申請が全て表示されている
     */
    public function test_user_application_list_displays_approved_applications()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $user->markEmailAsVerified(); // メール認証を完了させる

        $today = Carbon::today();
        $time = Time::create([
            'user_id' => $user->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        Application::create([
            'user_id' => $user->id,
            'time_id' => $time->id,
            'date' => $today,
            'arrival_time' => '09:30',
            'departure_time' => '18:30',
            'note' => '修正申請',
            'application_flg' => 0, // 承認済み
        ]);

        $response = $this->actingAs($user)->get('/timelog/application?status=approved');

        $response->assertStatus(200);
        $response->assertViewHas('applications');
        
        $applications = $response->viewData('applications');
        $this->assertGreaterThan(0, $applications->count());
    }

    /**
     * 勤怠詳細情報修正機能（一般ユーザー）
     * 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
     * 
     * テスト内容:
     * - 勤怠情報が登録されたユーザーにログインをする
     * - 勤怠詳細を修正し保存処理をする
     * - 申請一覧画面を開く
     * - 「詳細」ボタンを押す
     * 
     * 期待動作:
     * - 勤怠詳細画面に遷移する
     */
    public function test_user_application_detail_button_redirects_to_detail_page()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $user->markEmailAsVerified(); // メール認証を完了させる

        $today = Carbon::today();
        $time = Time::create([
            'user_id' => $user->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $application = Application::create([
            'user_id' => $user->id,
            'time_id' => $time->id,
            'date' => $today,
            'arrival_time' => '09:30',
            'departure_time' => '18:30',
            'note' => '修正申請',
            'application_flg' => 1,
        ]);

        $response = $this->actingAs($user)->get('/timelog/detail', [
            'year' => $today->year,
            'month' => $today->month,
            'day' => $today->day,
        ]);

        $response->assertStatus(200);
        $response->assertViewHas('application');
    }

    /**
     * 勤怠詳細情報修正機能（一般ユーザー）
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     * 
     * テスト内容:
     * - 勤怠情報が登録されたユーザーにログインをする
     * - 勤怠詳細ページを開く
     * - 出勤時間を退勤時間より後に設定する
     * - 保存処理をする
     * 
     * 期待動作:
     * - 「出勤時間が不適切な値です」というバリデーションメッセージが表示される
     */
    public function test_user_timelog_update_validation_when_arrival_time_after_departure_time()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $user->markEmailAsVerified(); // メール認証を完了させる

        $today = Carbon::today();
        $time = Time::create([
            'user_id' => $user->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $response = $this->actingAs($user)->post('/timelog/update', [
            'time_id' => $time->id,
            'date' => $today->format('Y-m-d'),
            'arrival_time' => '19:00', // 退勤時間より後
            'departure_time' => '18:00',
            'note' => 'テスト備考',
        ]);

        $response->assertSessionHasErrors(['arrival_time']);
        
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('arrival_time'));
        $this->assertContains('出勤時間が不適切な値です', $errors->get('arrival_time'));
    }

    /**
     * 勤怠詳細情報修正機能（一般ユーザー）
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     * 
     * テスト内容:
     * - 勤怠情報が登録されたユーザーにログインをする
     * - 勤怠詳細ページを開く
     * - 休憩開始時間を退勤時間より後に設定する
     * - 保存処理をする
     * 
     * 期待動作:
     * - 「休憩時間が不適切な値です」というバリデーションメッセージが表示される
     */
    public function test_user_timelog_update_validation_when_break_start_time_after_departure_time()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $user->markEmailAsVerified(); // メール認証を完了させる

        $today = Carbon::today();
        $time = Time::create([
            'user_id' => $user->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $response = $this->actingAs($user)->post('/timelog/update', [
            'time_id' => $time->id,
            'date' => $today->format('Y-m-d'),
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
            'note' => 'テスト備考',
            'breaktimes' => [
                [
                    'start' => '19:00', // 退勤時間より後
                    'end' => '19:30',
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['breaktime']);
        
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('breaktime'));
        $this->assertContains('休憩時間が不適切な値です', $errors->get('breaktime'));
    }

    /**
     * 勤怠詳細情報修正機能（一般ユーザー）
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     * 
     * テスト内容:
     * - 勤怠情報が登録されたユーザーにログインをする
     * - 勤怠詳細ページを開く
     * - 休憩終了時間を退勤時間より後に設定する
     * - 保存処理をする
     * 
     * 期待動作:
     * - 「休憩時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
     */
    public function test_user_timelog_update_validation_when_break_end_time_after_departure_time()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $user->markEmailAsVerified(); // メール認証を完了させる

        $today = Carbon::today();
        $time = Time::create([
            'user_id' => $user->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $response = $this->actingAs($user)->post('/timelog/update', [
            'time_id' => $time->id,
            'date' => $today->format('Y-m-d'),
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
            'note' => 'テスト備考',
            'breaktimes' => [
                [
                    'start' => '12:00',
                    'end' => '19:00', // 退勤時間より後
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['breaktime']);
        
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('breaktime'));
        $this->assertContains('休憩時間もしくは退勤時間が不適切な値です', $errors->get('breaktime'));
    }

    /**
     * 勤怠詳細情報修正機能（一般ユーザー）
     * 備考欄が未入力の場合のエラーメッセージが表示される
     * 
     * テスト内容:
     * - 勤怠情報が登録されたユーザーにログインをする
     * - 勤怠詳細ページを開く
     * - 備考欄を未入力のまま保存処理をする
     * 
     * 期待動作:
     * - 「備考を記入してください」というバリデーションメッセージが表示される
     */
    public function test_user_timelog_update_validation_when_note_is_empty()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $user->markEmailAsVerified(); // メール認証を完了させる

        $today = Carbon::today();
        $time = Time::create([
            'user_id' => $user->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $response = $this->actingAs($user)->post('/timelog/update', [
            'time_id' => $time->id,
            'date' => $today->format('Y-m-d'),
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
            'note' => '', // 備考欄が未入力
        ]);

        $response->assertSessionHasErrors(['note']);
        
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('note'));
        $this->assertContains('備考を記入してください', $errors->get('note'));
    }
}

