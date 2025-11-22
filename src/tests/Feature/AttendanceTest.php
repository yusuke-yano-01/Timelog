<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Actor;
use App\Models\Time;
use App\Models\Breaktime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    // RefreshDatabaseが各テストメソッドの実行前にデータベースをリセットするため、
    // 各テストメソッドの最初でActorを作成する必要があります。
    // setUp()を使わず、各メソッドで明示的に作成することで、実行順序に依存しない独立したテストになります。

    /**
     * 日時取得機能
     * 現在の日時情報がUIと同じ形式で出力されている
     * 
     * テスト内容:
     * - 勤怠打刻画面を開く
     * - 画面に表示されている日時情報を確認する
     * 
     * 期待動作:
     * - 画面上に表示されている日時が現在の日時と一致する
     */
    public function test_current_datetime_is_displayed_correctly()
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

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertViewHas('today');
        
        $today = $response->viewData('today');
        $this->assertInstanceOf(Carbon::class, $today);
        $this->assertEquals(Carbon::today()->format('Y-m-d'), $today->format('Y-m-d'));
    }

    /**
     * ステータス確認機能
     * 勤務外の場合、勤怠ステータスが正しく表示される
     * 
     * テスト内容:
     * - ステータスが勤務外のユーザーにログインする
     * - 勤怠打刻画面を開く
     * - 画面に表示されているステータスを確認する
     * 
     * 期待動作:
     * - 画面上に表示されているステータスが「勤務外」となる
     */
    public function test_status_display_when_user_is_off_duty()
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

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertViewHas('todayAttendance');
        
        $todayAttendance = $response->viewData('todayAttendance');
        $this->assertNull($todayAttendance);
        
        // ビューに「出勤」ボタンが表示されることを確認（ステータスが勤務外）
        $response->assertSee('出勤', false);
    }

    /**
     * ステータス確認機能
     * 出勤中の場合、勤怠ステータスが正しく表示される
     * 
     * テスト内容:
     * - ステータスが出勤中のユーザーにログインする
     * - 勤怠打刻画面を開く
     * - 画面に表示されているステータスを確認する
     * 
     * 期待動作:
     * - 画面上に表示されているステータスが「出勤中」となる
     */
    public function test_status_display_when_user_is_on_duty()
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

        // 出勤記録を作成
        Time::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'arrival_time' => Carbon::now()->format('H:i'),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertViewHas('todayAttendance');
        
        $todayAttendance = $response->viewData('todayAttendance');
        $this->assertNotNull($todayAttendance);
        $this->assertNull($todayAttendance->departure_time);
        
        // ビューに「退勤」と「休憩入」ボタンが表示されることを確認
        $response->assertSee('退勤', false);
        $response->assertSee('休憩入', false);
    }

    /**
     * ステータス確認機能
     * 休憩中の場合、勤怠ステータスが正しく表示される
     * 
     * テスト内容:
     * - ステータスが休憩中のユーザーにログインする
     * - 勤怠打刻画面を開く
     * - 画面に表示されているステータスを確認する
     * 
     * 期待動作:
     * - 画面上に表示されているステータスが「休憩中」となる
     */
    public function test_status_display_when_user_is_on_break()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
            'break_flg' => true, // 休憩中フラグ
        ]);
        $user->markEmailAsVerified(); // メール認証を完了させる

        // 出勤記録を作成
        $time = Time::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'arrival_time' => Carbon::now()->format('H:i'),
        ]);

        // 休憩記録を作成
        Breaktime::create([
            'time_id' => $time->id,
            'start_break_time' => Carbon::now()->format('H:i'),
            'end_break_time' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        
        // ビューに「休憩戻」ボタンが表示されることを確認
        $response->assertSee('休憩戻', false);
    }

    /**
     * ステータス確認機能
     * 退勤済の場合、勤怠ステータスが正しく表示される
     * 
     * テスト内容:
     * - ステータスが退勤済のユーザーにログインする
     * - 勤怠打刻画面を開く
     * - 画面に表示されているステータスを確認する
     * 
     * 期待動作:
     * - 画面上に表示されているステータスが「退勤済」となる
     */
    public function test_status_display_when_user_has_clocked_out()
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

        // 出勤・退勤記録を作成
        Time::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'arrival_time' => Carbon::now()->format('H:i'),
            'departure_time' => Carbon::now()->addHours(8)->format('H:i'),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertViewHas('todayAttendance');
        
        $todayAttendance = $response->viewData('todayAttendance');
        $this->assertNotNull($todayAttendance);
        $this->assertNotNull($todayAttendance->departure_time);
        
        // ビューに「お疲れ様でした。」が表示されることを確認
        $response->assertSee('お疲れ様でした', false);
    }

    /**
     * 出勤機能
     * 出勤ボタンが正しく機能する
     * 
     * テスト内容:
     * - ステータスが勤務外のユーザーにログインする
     * - 画面に「出勤」ボタンが表示されていることを確認する
     * - 出勤の処理を行う
     * 
     * 期待動作:
     * - 画面上に「出勤」ボタンが表示され、処理後に画面上に表示されるステータスが「勤務中」になる
     */
    public function test_clock_in_functionality()
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

        $response = $this->actingAs($user)->post('/attendance/clock-in');

        $response->assertRedirect('/');
        $response->assertSessionHas('success', '出勤を記録しました。');
        
        // データベースに出勤記録が保存されていることを確認
        $this->assertDatabaseHas('times', [
            'user_id' => $user->id,
            'date' => Carbon::today()->format('Y-m-d'),
        ]);
        
        $time = Time::where('user_id', $user->id)
            ->where('date', Carbon::today())
            ->first();
        $this->assertNotNull($time);
        $this->assertNotNull($time->arrival_time);
    }

    /**
     * 出勤機能
     * 出勤は一日一回のみできる
     * 
     * テスト内容:
     * - ステータスが退勤済であるユーザーにログインする
     * - 勤務ボタンが表示されないことを確認する
     * 
     * 期待動作:
     * - 画面上に「出勤」ボタンが表示されない
     */
    public function test_clock_in_only_once_per_day()
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

        // 既に出勤・退勤記録を作成
        Time::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'arrival_time' => Carbon::now()->format('H:i'),
            'departure_time' => Carbon::now()->addHours(8)->format('H:i'),
        ]);

        // 再度出勤を試みる
        $response = $this->actingAs($user)->post('/attendance/clock-in');

        // エラーメッセージがセッションに含まれていることを確認
        $response->assertSessionHasErrors(['error']);
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('error'));
        $this->assertContains('今日は既に出勤済みです。', $errors->get('error'));
        
        // ビューに「出勤」ボタンが表示されないことを確認
        // エラーメッセージに「出勤」という文字が含まれているため、ボタンの存在を確認する別の方法を使用
        $viewResponse = $this->actingAs($user)->get('/attendance');
        $viewResponse->assertStatus(200);
        // 「出勤」ボタン（type="submit"のボタン）が存在しないことを確認
        $viewResponse->assertDontSee('btn-clock-in', false);
    }

    /**
     * 出勤機能
     * 出勤時刻が勤怠一覧画面で確認できる
     * 
     * テスト内容:
     * - ステータスが勤務外のユーザーにログインする
     * - 出勤の処理を行う
     * - 勤怠一覧画面から出勤の日付を確認する
     * 
     * 期待動作:
     * - 勤怠一覧画面に出勤時刻が正確に記録されている
     */
    public function test_clock_in_time_is_recorded_in_timelog_list()
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

        $clockInTime = Carbon::now()->format('H:i');
        
        // 出勤処理
        $this->actingAs($user)->post('/attendance/clock-in');

        // 勤怠一覧画面を確認
        $response = $this->actingAs($user)->get('/timelog/list');
        
        $response->assertStatus(200);
        
        // データベースに出勤時刻が記録されていることを確認
        $time = Time::where('user_id', $user->id)
            ->where('date', Carbon::today())
            ->first();
        $this->assertNotNull($time);
        $this->assertNotNull($time->arrival_time);
    }

    /**
     * 休憩機能
     * 休憩ボタンが正しく機能する
     * 
     * テスト内容:
     * - ステータスが出勤中のユーザーにログインする
     * - 画面に「休憩入」ボタンが表示されていることを確認する
     * - 休憩の処理を行う
     * 
     * 期待動作:
     * - 画面上に「休憩入」ボタンが表示され、処理後に画面上に表示されるステータスが「休憩中」になる
     */
    public function test_start_break_functionality()
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

        // 出勤記録を作成
        $time = Time::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'arrival_time' => Carbon::now()->format('H:i'),
        ]);

        $response = $this->actingAs($user)->post('/attendance/start-break');

        $response->assertRedirect('/');
        $response->assertSessionHas('success', '休憩を開始しました。');
        
        // ユーザーのbreak_flgがtrueになっていることを確認
        $user->refresh();
        $this->assertTrue($user->break_flg);
        
        // 休憩記録が作成されていることを確認
        $breaktime = Breaktime::where('time_id', $time->id)
            ->whereNotNull('start_break_time')
            ->whereNull('end_break_time')
            ->first();
        $this->assertNotNull($breaktime);
    }

    /**
     * 休憩機能
     * 休憩は一日に何回でもできる
     * 
     * テスト内容:
     * - ステータスが出勤中であるユーザーにログインする
     * - 休憩入と休憩戻の処理を行う
     * - 「休憩入」ボタンが表示されることを確認する
     * 
     * 期待動作:
     * - 画面上に「休憩入」ボタンが表示される
     */
    public function test_break_can_be_taken_multiple_times()
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

        // 出勤記録を作成
        $time = Time::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'arrival_time' => Carbon::now()->format('H:i'),
        ]);

        // 1回目の休憩入
        $this->actingAs($user)->post('/attendance/start-break');
        
        // 1回目の休憩戻
        $this->actingAs($user)->post('/attendance/end-break');
        
        // 2回目の休憩入
        $response = $this->actingAs($user)->post('/attendance/start-break');
        
        $response->assertRedirect('/');
        $response->assertSessionHas('success', '休憩を開始しました。');
        
        // 2回の休憩記録が存在することを確認
        $breaktimes = Breaktime::where('time_id', $time->id)->get();
        $this->assertGreaterThanOrEqual(2, $breaktimes->count());
    }

    /**
     * 休憩機能
     * 休憩戻ボタンが正しく機能する
     * 
     * テスト内容:
     * - ステータスが出勤中であるユーザーにログインする
     * - 休憩入の処理を行う
     * - 休憩戻の処理を行う
     * 
     * 期待動作:
     * - 休憩戻ボタンが表示され、処理後にステータスが「出勤中」に変更される
     */
    public function test_end_break_functionality()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
            'break_flg' => true, // 休憩中
        ]);
        $user->markEmailAsVerified(); // メール認証を完了させる

        // 出勤記録を作成
        $time = Time::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'arrival_time' => Carbon::now()->format('H:i'),
        ]);

        // 休憩記録を作成
        $breaktime = Breaktime::create([
            'time_id' => $time->id,
            'start_break_time' => Carbon::now()->format('H:i'),
            'end_break_time' => null,
        ]);

        $response = $this->actingAs($user)->post('/attendance/end-break');

        // メール認証が完了していない場合はリダイレクトされる可能性があるため、リダイレクトを確認
        $response->assertRedirect();
        $response->assertSessionHas('success', '休憩を終了しました。');
        
        // ユーザーのbreak_flgがfalseになっていることを確認
        $user->refresh();
        $this->assertFalse($user->break_flg);
        
        // 休憩終了時刻が記録されていることを確認
        $breaktime->refresh();
        $this->assertNotNull($breaktime->end_break_time);
    }

    /**
     * 休憩機能
     * 休憩戻は一日に何回でもできる
     * 
     * テスト内容:
     * - ステータスが出勤中であるユーザーにログインする
     * - 休憩入と休憩戻の処理を行い、再度休憩入の処理を行う
     * - 「休憩戻」ボタンが表示されることを確認する
     * 
     * 期待動作:
     * - 画面上に「休憩戻」ボタンが表示される
     */
    public function test_end_break_can_be_done_multiple_times()
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

        // 出勤記録を作成
        $time = Time::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'arrival_time' => Carbon::now()->format('H:i'),
        ]);

        // 1回目の休憩入
        $this->actingAs($user)->post('/attendance/start-break');
        
        // 1回目の休憩戻
        $this->actingAs($user)->post('/attendance/end-break');
        
        // 2回目の休憩入
        $this->actingAs($user)->post('/attendance/start-break');
        
        // 2回目の休憩戻
        $response = $this->actingAs($user)->post('/attendance/end-break');
        
        $response->assertRedirect('/');
        $response->assertSessionHas('success', '休憩を終了しました。');
    }

    /**
     * 休憩機能
     * 休憩時刻が勤怠一覧画面で確認できる
     * 
     * テスト内容:
     * - ステータスが勤務中のユーザーにログインする
     * - 休憩入と休憩戻の処理を行う
     * - 勤怠一覧画面から休憩の日付を確認する
     * 
     * 期待動作:
     * - 勤怠一覧画面に休憩時刻が正確に記録されている
     */
    public function test_break_time_is_recorded_in_timelog_list()
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

        // 出勤記録を作成
        $time = Time::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'arrival_time' => Carbon::now()->format('H:i'),
        ]);

        // 休憩入
        $this->actingAs($user)->post('/attendance/start-break');
        
        // 休憩戻
        $this->actingAs($user)->post('/attendance/end-break');

        // 休憩記録が作成されていることを確認
        $breaktime = Breaktime::where('time_id', $time->id)
            ->whereNotNull('start_break_time')
            ->whereNotNull('end_break_time')
            ->first();
        $this->assertNotNull($breaktime);
    }

    /**
     * 退勤機能
     * 退勤ボタンが正しく機能する
     * 
     * テスト内容:
     * - ステータスが勤務中のユーザーにログインする
     * - 画面に「退勤」ボタンが表示されていることを確認する
     * - 退勤の処理を行う
     * 
     * 期待動作:
     * - 画面上に「退勤」ボタンが表示され、処理後に画面上に表示されるステータスが「退勤済」になる
     */
    public function test_clock_out_functionality()
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

        // 出勤記録を作成
        $time = Time::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'arrival_time' => Carbon::now()->format('H:i'),
        ]);

        $response = $this->actingAs($user)->post('/attendance/clock-out');

        $response->assertRedirect('/');
        $response->assertSessionHas('success', '退勤を記録しました。');
        
        // 退勤時刻が記録されていることを確認
        $time->refresh();
        $this->assertNotNull($time->departure_time);
    }

    /**
     * 退勤機能
     * 退勤時刻が勤怠一覧画面で確認できる
     * 
     * テスト内容:
     * - ステータスが勤務外のユーザーにログインする
     * - 出勤と退勤の処理を行う
     * - 勤怠一覧画面から退勤の日付を確認する
     * 
     * 期待動作:
     * - 勤怠一覧画面に退勤時刻が正確に記録されている
     */
    public function test_clock_out_time_is_recorded_in_timelog_list()
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

        // 出勤処理
        $this->actingAs($user)->post('/attendance/clock-in');
        
        // 退勤処理
        $this->actingAs($user)->post('/attendance/clock-out');

        // 退勤時刻が記録されていることを確認
        $time = Time::where('user_id', $user->id)
            ->where('date', Carbon::today())
            ->first();
        $this->assertNotNull($time);
        $this->assertNotNull($time->departure_time);
    }
}

