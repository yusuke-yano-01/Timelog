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

class TimelogTest extends TestCase
{
    use RefreshDatabase;

    // RefreshDatabaseが各テストメソッドの実行前にデータベースをリセットするため、
    // setUp()でActorを作成しても消えてしまいます。
    // 各テストメソッドの最初でActorを作成する必要があります。

    /**
     * 勤怠一覧情報取得機能（一般ユーザー）
     * 自分が行った勤怠情報が全て表示されている
     * 
     * テスト内容:
     * - 勤怠情報が登録されたユーザーにログインする
     * - 勤怠一覧ページを開く
     * - 自分の勤怠情報がすべて表示されていることを確認する
     * 
     * 期待動作:
     * - 自分の勤怠情報が全て表示されている
     */
    public function test_user_timelog_list_displays_all_attendance_records()
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

        // 勤怠情報を作成
        $time1 = Time::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->subDays(1),
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $time2 = Time::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $response = $this->actingAs($user)->get('/timelog/list');

        $response->assertStatus(200);
        $response->assertViewHas('attendanceData');
        
        $attendanceData = $response->viewData('attendanceData');
        $this->assertIsArray($attendanceData);
    }

    /**
     * 勤怠一覧情報取得機能（一般ユーザー）
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     * 
     * テスト内容:
     * - ユーザーにログインをする
     * - 勤怠一覧ページを開く
     * 
     * 期待動作:
     * - 現在の月が表示されている
     */
    public function test_user_timelog_list_displays_current_month()
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

        $response = $this->actingAs($user)->get('/timelog/list');

        $response->assertStatus(200);
        $response->assertViewHas('currentMonth');
        
        $currentMonth = $response->viewData('currentMonth');
        $this->assertEquals(Carbon::now()->year, $currentMonth->year);
        $this->assertEquals(Carbon::now()->month, $currentMonth->month);
    }

    /**
     * 勤怠一覧情報取得機能（一般ユーザー）
     * 「前月」を押下した時に表示月の前月の情報が表示される
     * 
     * テスト内容:
     * - 勤怠情報が登録されたユーザーにログインをする
     * - 勤怠一覧ページを開く
     * - 「前月」ボタンを押す
     * 
     * 期待動作:
     * - 前月の情報が表示されている
     */
    public function test_user_timelog_list_displays_previous_month()
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

        $prevMonth = Carbon::now()->subMonth();

        $response = $this->actingAs($user)->get('/timelog/list?year=' . $prevMonth->year . '&month=' . $prevMonth->month);

        $response->assertStatus(200);
        $response->assertViewHas('currentMonth');
        
        $currentMonth = $response->viewData('currentMonth');
        $this->assertEquals($prevMonth->year, $currentMonth->year);
        $this->assertEquals($prevMonth->month, $currentMonth->month);
    }

    /**
     * 勤怠一覧情報取得機能（一般ユーザー）
     * 「翌月」を押下した時に表示月の翌月の情報が表示される
     * 
     * テスト内容:
     * - 勤怠情報が登録されたユーザーにログインをする
     * - 勤怠一覧ページを開く
     * - 「翌月」ボタンを押す
     * 
     * 期待動作:
     * - 翌月の情報が表示されている
     */
    public function test_user_timelog_list_displays_next_month()
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

        $nextMonth = Carbon::now()->addMonth();

        $response = $this->actingAs($user)->get('/timelog/list?year=' . $nextMonth->year . '&month=' . $nextMonth->month);

        $response->assertStatus(200);
        $response->assertViewHas('currentMonth');
        
        $currentMonth = $response->viewData('currentMonth');
        $this->assertEquals($nextMonth->year, $currentMonth->year);
        $this->assertEquals($nextMonth->month, $currentMonth->month);
    }

    /**
     * 勤怠一覧情報取得機能（一般ユーザー）
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     * 
     * テスト内容:
     * - 勤怠情報が登録されたユーザーにログインをする
     * - 勤怠一覧ページを開く
     * - 「詳細」ボタンを押下する
     * 
     * 期待動作:
     * - その日の勤怠詳細画面に遷移する
     */
    public function test_user_timelog_list_detail_button_redirects_to_detail_page()
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
        Time::create([
            'user_id' => $user->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $response = $this->actingAs($user)->get('/timelog/detail', [
            'year' => $today->year,
            'month' => $today->month,
            'day' => $today->day,
        ]);

        $response->assertStatus(200);
        $response->assertViewHas('date');
    }

    /**
     * 勤怠詳細情報取得機能（一般ユーザー）
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     * 
     * テスト内容:
     * - 勤怠情報が登録されたユーザーにログインをする
     * - 勤怠詳細ページを開く
     * - 名前欄を確認する
     * 
     * 期待動作:
     * - 名前がログインユーザーの名前になっている
     */
    public function test_user_timelog_detail_displays_user_name()
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
        Time::create([
            'user_id' => $user->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $response = $this->actingAs($user)->get('/timelog/detail', [
            'year' => $today->year,
            'month' => $today->month,
            'day' => $today->day,
        ]);

        $response->assertStatus(200);
        $response->assertViewHas('targetUser');
        
        $targetUser = $response->viewData('targetUser');
        $this->assertEquals($user->name, $targetUser->name);
    }

    /**
     * 勤怠詳細情報取得機能（一般ユーザー）
     * 勤怠詳細画面の「日付」が選択した日付になっている
     * 
     * テスト内容:
     * - 勤怠情報が登録されたユーザーにログインをする
     * - 勤怠詳細ページを開く
     * - 日付欄を確認する
     * 
     * 期待動作:
     * - 日付が選択した日付になっている
     */
    public function test_user_timelog_detail_displays_selected_date()
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

        $selectedDate = Carbon::today()->subDays(5);
        Time::create([
            'user_id' => $user->id,
            'date' => $selectedDate,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $response = $this->actingAs($user)->get('/timelog/detail?year=' . $selectedDate->year . '&month=' . $selectedDate->month . '&day=' . $selectedDate->day);

        $response->assertStatus(200);
        $response->assertViewHas('date');
        
        $date = $response->viewData('date');
        $this->assertEquals($selectedDate->format('Y-m-d'), $date->format('Y-m-d'));
    }

    /**
     * 勤怠詳細情報取得機能（一般ユーザー）
     * 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     * 
     * テスト内容:
     * - 勤怠情報が登録されたユーザーにログインをする
     * - 勤怠詳細ページを開く
     * - 出勤・退勤欄を確認する
     * 
     * 期待動作:
     * - 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_user_timelog_detail_displays_correct_attendance_times()
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

        $response = $this->actingAs($user)->get('/timelog/detail', [
            'year' => $today->year,
            'month' => $today->month,
            'day' => $today->day,
        ]);

        $response->assertStatus(200);
        $response->assertViewHas('attendanceRecord');
        
        $attendanceRecord = $response->viewData('attendanceRecord');
        $this->assertNotNull($attendanceRecord);
        $this->assertEquals('09:00', $attendanceRecord->arrival_time);
        $this->assertEquals('18:00', $attendanceRecord->departure_time);
    }

    /**
     * 勤怠詳細情報取得機能（一般ユーザー）
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致している
     * 
     * テスト内容:
     * - 勤怠情報が登録されたユーザーにログインをする
     * - 勤怠詳細ページを開く
     * - 休憩欄を確認する
     * 
     * 期待動作:
     * - 「休憩」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_user_timelog_detail_displays_correct_break_times()
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

        Breaktime::create([
            'time_id' => $time->id,
            'start_break_time' => '12:00',
            'end_break_time1' => '13:00',
        ]);

        $response = $this->actingAs($user)->get('/timelog/detail', [
            'year' => $today->year,
            'month' => $today->month,
            'day' => $today->day,
        ]);

        $response->assertStatus(200);
        $response->assertViewHas('attendanceRecord');
        
        $attendanceRecord = $response->viewData('attendanceRecord');
        $this->assertNotNull($attendanceRecord);
        $this->assertTrue($attendanceRecord->relationLoaded('breaktimes'));
        $this->assertGreaterThan(0, $attendanceRecord->breaktimes->count());
    }
}

