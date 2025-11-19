<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Actor;
use App\Models\Time;
use App\Models\Breaktime;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Tests\TestCase;

class AdminTimelogTest extends TestCase
{
    use RefreshDatabase;

    // RefreshDatabaseが各テストメソッドの実行前にデータベースをリセットするため、
    // setUp()でActorを作成しても消えてしまいます。
    // 各テストメソッドの最初でActorを作成する必要があります。

    /**
     * 勤怠一覧情報取得機能（管理者）
     * その日になされた全ユーザーの勤怠情報が正確に確認できる
     * 
     * テスト内容:
     * - 管理者ユーザーにログインする
     * - 勤怠一覧画面を開く
     * 
     * 期待動作:
     * - その日の全ユーザーの勤怠情報が正確な値になっている
     */
    public function test_admin_attendance_list_displays_all_users_attendance()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 1,
            'registeredflg' => true,
        ]);
        $admin->markEmailAsVerified(); // メール認証を完了させる

        $staff1 = User::create([
            'name' => 'スタッフ1',
            'email' => 'staff1@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $staff1->markEmailAsVerified();

        $staff2 = User::create([
            'name' => 'スタッフ2',
            'email' => 'staff2@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $staff2->markEmailAsVerified();

        $today = Carbon::today();
        Time::create([
            'user_id' => $staff1->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        Time::create([
            'user_id' => $staff2->id,
            'date' => $today,
            'arrival_time' => '10:00',
            'departure_time' => '19:00',
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        // 302リダイレクトの場合、リダイレクト先を確認
        if ($response->status() === 302) {
            $this->fail('302リダイレクトが発生しました。リダイレクト先: ' . $response->headers->get('Location'));
        }
        
        $response->assertStatus(200);
        $response->assertViewHas('attendanceRecords');
        
        $attendanceRecords = $response->viewData('attendanceRecords');
        $this->assertGreaterThanOrEqual(2, count($attendanceRecords));
        
        // スタッフ1の勤怠情報が正しく表示されていることを確認
        $staff1Record = collect($attendanceRecords)->first(function ($record) use ($staff1) {
            return $record['staff']->id === $staff1->id;
        });
        $this->assertNotNull($staff1Record, 'スタッフ1の勤怠記録が見つかりません');
        $this->assertEquals('スタッフ1', $staff1Record['staff']->name);
        $this->assertNotNull($staff1Record['attendance'], 'スタッフ1の勤怠データがnullです');
        $this->assertEquals('09:00', $staff1Record['attendance']->arrival_time);
        $this->assertEquals('18:00', $staff1Record['attendance']->departure_time);
        
        // スタッフ2の勤怠情報が正しく表示されていることを確認
        $staff2Record = collect($attendanceRecords)->first(function ($record) use ($staff2) {
            return $record['staff']->id === $staff2->id;
        });
        $this->assertNotNull($staff2Record, 'スタッフ2の勤怠記録が見つかりません');
        $this->assertEquals('スタッフ2', $staff2Record['staff']->name);
        $this->assertNotNull($staff2Record['attendance'], 'スタッフ2の勤怠データがnullです');
        $this->assertEquals('10:00', $staff2Record['attendance']->arrival_time);
        $this->assertEquals('19:00', $staff2Record['attendance']->departure_time);
    }

    /**
     * 勤怠一覧情報取得機能（管理者）
     * 遷移した際に現在の日付が表示される
     * 
     * テスト内容:
     * - 管理者ユーザーにログインする
     * - 勤怠一覧画面を開く
     * 
     * 期待動作:
     * - 勤怠一覧画面にその日の日付が表示されている
     */
    public function test_admin_attendance_list_displays_current_date()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 1,
            'registeredflg' => true,
        ]);
        $admin->markEmailAsVerified(); // メール認証を完了させる

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertViewHas('targetDate');
        
        $targetDate = $response->viewData('targetDate');
        $this->assertEquals(Carbon::today()->format('Y-m-d'), $targetDate->format('Y-m-d'));
    }

    /**
     * 勤怠一覧情報取得機能（管理者）
     * 「前日」を押下した時に前の日の勤怠情報が表示される
     * 
     * テスト内容:
     * - 管理者ユーザーにログインする
     * - 勤怠一覧画面を開く
     * - 「前日」ボタンを押す
     * 
     * 期待動作:
     * - 前日の日付の勤怠情報が表示される
     */
    public function test_admin_attendance_list_displays_previous_date()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 1,
            'registeredflg' => true,
        ]);
        $admin->markEmailAsVerified(); // メール認証を完了させる

        $prevDate = Carbon::today()->subDay();

        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=' . $prevDate->format('Y-m-d'));

        $response->assertStatus(200);
        $response->assertViewHas('targetDate');
        
        $targetDate = $response->viewData('targetDate');
        $this->assertEquals($prevDate->format('Y-m-d'), $targetDate->format('Y-m-d'));
    }

    /**
     * 勤怠一覧情報取得機能（管理者）
     * 「翌日」を押下した時に次の日の勤怠情報が表示される
     * 
     * テスト内容:
     * - 管理者ユーザーにログインする
     * - 勤怠一覧画面を開く
     * - 「翌日」ボタンを押す
     * 
     * 期待動作:
     * - 翌日の日付の勤怠情報が表示される
     */
    public function test_admin_attendance_list_displays_next_date()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 1,
            'registeredflg' => true,
        ]);
        $admin->markEmailAsVerified(); // メール認証を完了させる

        $nextDate = Carbon::today()->addDay();

        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=' . $nextDate->format('Y-m-d'));

        $response->assertStatus(200);
        $response->assertViewHas('targetDate');
        
        $targetDate = $response->viewData('targetDate');
        $this->assertEquals($nextDate->format('Y-m-d'), $targetDate->format('Y-m-d'));
    }

    /**
     * ユーザー情報取得機能（管理者）
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
     * 
     * テスト内容:
     * - 管理者でログインする
     * - スタッフ一覧ページを開く
     * 
     * 期待動作:
     * - 全ての一般ユーザーの氏名とメールアドレスが正しく表示されている
     */
    public function test_admin_staff_list_displays_all_staff_info()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 1,
            'registeredflg' => true,
        ]);
        $admin->markEmailAsVerified(); // メール認証を完了させる

        $staff1 = User::create([
            'name' => 'スタッフ1',
            'email' => 'staff1@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $staff1->markEmailAsVerified();

        $staff2 = User::create([
            'name' => 'スタッフ2',
            'email' => 'staff2@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $staff2->markEmailAsVerified();

        $response = $this->actingAs($admin)->get('/admin/staff/list');

        $response->assertStatus(200);
        $response->assertViewHas('staffs');
        
        $staffs = $response->viewData('staffs');
        $this->assertGreaterThanOrEqual(2, $staffs->count());
        
        $staffNames = $staffs->pluck('name')->toArray();
        $this->assertContains('スタッフ1', $staffNames);
        $this->assertContains('スタッフ2', $staffNames);
    }

    /**
     * 勤怠情報修正機能（管理者）
     * 承認待ちの修正申請が全て表示されている
     * 
     * テスト内容:
     * - 管理者ユーザーにログインをする
     * - 修正申請一覧ページを開き、承認待ちのタブを開く
     * 
     * 期待動作:
     * - 全ユーザーの未承認の修正申請が表示される
     */
    public function test_admin_application_list_displays_pending_applications()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 1,
            'registeredflg' => true,
        ]);
        $admin->markEmailAsVerified(); // メール認証を完了させる

        $staff = User::create([
            'name' => 'スタッフ',
            'email' => 'staff@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $staff->markEmailAsVerified();

        $today = Carbon::today();
        $time = Time::create([
            'user_id' => $staff->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        Application::create([
            'user_id' => $staff->id,
            'time_id' => $time->id,
            'date' => $today,
            'arrival_time' => '09:30',
            'departure_time' => '18:30',
            'note' => '修正申請',
            'application_flg' => 1, // 申請中
        ]);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/list?status=pending');

        $response->assertStatus(200);
        $response->assertViewHas('applications');
        
        $applications = $response->viewData('applications');
        $this->assertGreaterThan(0, $applications->count());
    }

    /**
     * 勤怠情報修正機能（管理者）
     * 承認済みの修正申請が全て表示されている
     * 
     * テスト内容:
     * - 管理者ユーザーにログインをする
     * - 修正申請一覧ページを開き、承認済みのタブを開く
     * 
     * 期待動作:
     * - 全ユーザーの承認済みの修正申請が表示される
     */
    public function test_admin_application_list_displays_approved_applications()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 1,
            'registeredflg' => true,
        ]);
        $admin->markEmailAsVerified(); // メール認証を完了させる

        $staff = User::create([
            'name' => 'スタッフ',
            'email' => 'staff@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $staff->markEmailAsVerified();

        $today = Carbon::today();
        $time = Time::create([
            'user_id' => $staff->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        Application::create([
            'user_id' => $staff->id,
            'time_id' => $time->id,
            'date' => $today,
            'arrival_time' => '09:30',
            'departure_time' => '18:30',
            'note' => '修正申請',
            'application_flg' => 0, // 承認済み
        ]);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/list?status=approved');

        $response->assertStatus(200);
        $response->assertViewHas('applications');
        
        $applications = $response->viewData('applications');
        $this->assertGreaterThan(0, $applications->count());
    }

    /**
     * 勤怠情報修正機能（管理者）
     * 修正申請の承認処理が正しく行われる
     * 
     * テスト内容:
     * - 管理者ユーザーにログインをする
     * - 修正申請の詳細画面で「承認」ボタンを押す
     * 
     * 期待動作:
     * - 修正申請が承認され、勤怠情報が更新される
     */
    public function test_admin_application_approval_updates_attendance()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 1,
            'registeredflg' => true,
        ]);
        $admin->markEmailAsVerified(); // メール認証を完了させる

        $staff = User::create([
            'name' => 'スタッフ',
            'email' => 'staff@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $staff->markEmailAsVerified();

        $today = Carbon::today();
        $time = Time::create([
            'user_id' => $staff->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $application = Application::create([
            'user_id' => $staff->id,
            'time_id' => $time->id,
            'date' => $today,
            'arrival_time' => '09:30',
            'departure_time' => '18:30',
            'note' => '修正申請',
            'application_flg' => 1, // 申請中
        ]);

        $response = $this->actingAs($admin)->post('/timelog/approve', [
            'application_id' => $application->id,
            'date' => $today->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        
        // 申請が承認されていることを確認
        $application->refresh();
        $this->assertEquals(0, $application->application_flg); // 承認済み
        
        // 勤怠情報が更新されていることを確認
        $time->refresh();
        $this->assertEquals('09:30', $time->arrival_time);
        $this->assertEquals('18:30', $time->departure_time);
    }

    /**
     * 勤怠詳細情報取得・修正機能（管理者）
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     * 
     * テスト内容:
     * - 管理者ユーザーにログインをする
     * - 勤怠詳細ページを開く
     * 
     * 期待動作:
     * - 詳細画面の内容が選択した情報と一致する
     */
    public function test_admin_attendance_detail_displays_selected_data()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 1,
            'registeredflg' => true,
        ]);
        $admin->markEmailAsVerified(); // メール認証を完了させる

        $staff = User::create([
            'name' => 'スタッフ',
            'email' => 'staff@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $staff->markEmailAsVerified();

        $today = Carbon::today();
        $time = Time::create([
            'user_id' => $staff->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/' . $staff->id . '?year=' . $today->year . '&month=' . $today->month . '&day=' . $today->day);

        $response->assertStatus(200);
        $response->assertViewHas('attendanceRecord');
        $response->assertViewHas('targetUser');
        $response->assertViewHas('date');
        
        $attendanceRecord = $response->viewData('attendanceRecord');
        $targetUser = $response->viewData('targetUser');
        $date = $response->viewData('date');
        
        $this->assertNotNull($attendanceRecord);
        $this->assertEquals($staff->id, $targetUser->id);
        $this->assertEquals($today->format('Y-m-d'), $date->format('Y-m-d'));
        $this->assertEquals('09:00', $attendanceRecord->arrival_time);
        $this->assertEquals('18:00', $attendanceRecord->departure_time);
    }

    /**
     * 勤怠詳細情報取得・修正機能（管理者）
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     * 
     * テスト内容:
     * - 管理者ユーザーにログインをする
     * - 勤怠詳細ページを開く
     * - 出勤時間を退勤時間より後に設定する
     * - 保存処理をする
     * 
     * 期待動作:
     * - 「出勤時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
     */
    public function test_admin_timelog_update_validation_when_arrival_time_after_departure_time()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 1,
            'registeredflg' => true,
        ]);
        $admin->markEmailAsVerified(); // メール認証を完了させる

        $staff = User::create([
            'name' => 'スタッフ',
            'email' => 'staff@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $staff->markEmailAsVerified();

        $today = Carbon::today();
        $time = Time::create([
            'user_id' => $staff->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $response = $this->actingAs($admin)->post('/timelog/update', [
            'time_id' => $time->id,
            'date' => $today->format('Y-m-d'),
            'user_id' => $staff->id,
            'arrival_time' => '19:00', // 退勤時間より後
            'departure_time' => '18:00',
            'note' => 'テスト備考',
        ]);

        $response->assertSessionHasErrors(['arrival_time']);
        
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('arrival_time'));
        $this->assertContains('出勤時間もしくは退勤時間が不適切な値です', $errors->get('arrival_time'));
    }

    /**
     * 勤怠詳細情報取得・修正機能（管理者）
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     * 
     * テスト内容:
     * - 管理者ユーザーにログインをする
     * - 勤怠詳細ページを開く
     * - 休憩開始時間を退勤時間より後に設定する
     * - 保存処理をする
     * 
     * 期待動作:
     * - 「休憩時間が不適切な値です」というバリデーションメッセージが表示される
     */
    public function test_admin_timelog_update_validation_when_break_start_time_after_departure_time()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 1,
            'registeredflg' => true,
        ]);
        $admin->markEmailAsVerified(); // メール認証を完了させる

        $staff = User::create([
            'name' => 'スタッフ',
            'email' => 'staff@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $staff->markEmailAsVerified();

        $today = Carbon::today();
        $time = Time::create([
            'user_id' => $staff->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $response = $this->actingAs($admin)->post('/timelog/update', [
            'time_id' => $time->id,
            'date' => $today->format('Y-m-d'),
            'user_id' => $staff->id,
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
     * 勤怠詳細情報取得・修正機能（管理者）
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     * 
     * テスト内容:
     * - 管理者ユーザーにログインをする
     * - 勤怠詳細ページを開く
     * - 休憩終了時間を退勤時間より後に設定する
     * - 保存処理をする
     * 
     * 期待動作:
     * - 「休憩時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
     */
    public function test_admin_timelog_update_validation_when_break_end_time_after_departure_time()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 1,
            'registeredflg' => true,
        ]);
        $admin->markEmailAsVerified(); // メール認証を完了させる

        $staff = User::create([
            'name' => 'スタッフ',
            'email' => 'staff@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $staff->markEmailAsVerified();

        $today = Carbon::today();
        $time = Time::create([
            'user_id' => $staff->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $response = $this->actingAs($admin)->post('/timelog/update', [
            'time_id' => $time->id,
            'date' => $today->format('Y-m-d'),
            'user_id' => $staff->id,
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
     * 勤怠詳細情報取得・修正機能（管理者）
     * 備考欄が未入力の場合のエラーメッセージが表示される
     * 
     * テスト内容:
     * - 管理者ユーザーにログインをする
     * - 勤怠詳細ページを開く
     * - 備考欄を未入力のまま保存処理をする
     * 
     * 期待動作:
     * - 「備考を記入してください」というバリデーションメッセージが表示される
     */
    public function test_admin_timelog_update_validation_when_note_is_empty()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 1,
            'registeredflg' => true,
        ]);
        $admin->markEmailAsVerified(); // メール認証を完了させる

        $staff = User::create([
            'name' => 'スタッフ',
            'email' => 'staff@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $staff->markEmailAsVerified();

        $today = Carbon::today();
        $time = Time::create([
            'user_id' => $staff->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $response = $this->actingAs($admin)->post('/timelog/update', [
            'time_id' => $time->id,
            'date' => $today->format('Y-m-d'),
            'user_id' => $staff->id,
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

    /**
     * ユーザー情報取得機能（管理者）
     * ユーザーの勤怠情報が正しく表示される
     * 
     * テスト内容:
     * - 管理者ユーザーでログインする
     * - 選択したユーザーの勤怠一覧ページを開く
     * 
     * 期待動作:
     * - 勤怠情報が正確に表示される
     */
    public function test_admin_staff_timelog_displays_correctly()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 1,
            'registeredflg' => true,
        ]);
        $admin->markEmailAsVerified(); // メール認証を完了させる

        $staff = User::create([
            'name' => 'スタッフ',
            'email' => 'staff@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $staff->markEmailAsVerified();

        $today = Carbon::today();
        Time::create([
            'user_id' => $staff->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $response = $this->actingAs($admin)->get('/timelog/list?user_id=' . $staff->id);

        $response->assertStatus(200);
        $response->assertViewHas('attendanceData');
        $response->assertViewHas('targetUser');
        
        $targetUser = $response->viewData('targetUser');
        $this->assertEquals($staff->id, $targetUser->id);
    }

    /**
     * ユーザー情報取得機能（管理者）
     * 「前月」を押下した時に表示月の前月の情報が表示される
     * 
     * テスト内容:
     * - 管理者ユーザーにログインをする
     * - 勤怠一覧ページを開く
     * - 「前月」ボタンを押す
     * 
     * 期待動作:
     * - 前月の情報が表示されている
     */
    public function test_admin_staff_timelog_displays_previous_month()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 1,
            'registeredflg' => true,
        ]);
        $admin->markEmailAsVerified(); // メール認証を完了させる

        $staff = User::create([
            'name' => 'スタッフ',
            'email' => 'staff@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $staff->markEmailAsVerified();

        $currentMonth = Carbon::now();
        $prevMonth = $currentMonth->copy()->subMonth();

        Time::create([
            'user_id' => $staff->id,
            'date' => $prevMonth->copy()->day(15),
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $response = $this->actingAs($admin)->get('/timelog/list?user_id=' . $staff->id . '&year=' . $prevMonth->year . '&month=' . $prevMonth->month);

        $response->assertStatus(200);
        $response->assertViewHas('currentMonth');
        
        $currentMonthData = $response->viewData('currentMonth');
        $this->assertEquals($prevMonth->year, $currentMonthData->year);
        $this->assertEquals($prevMonth->month, $currentMonthData->month);
    }

    /**
     * ユーザー情報取得機能（管理者）
     * 「翌月」を押下した時に表示月の前月の情報が表示される
     * 
     * テスト内容:
     * - 管理者ユーザーにログインをする
     * - 勤怠一覧ページを開く
     * - 「翌月」ボタンを押す
     * 
     * 期待動作:
     * - 翌月の情報が表示されている
     */
    public function test_admin_staff_timelog_displays_next_month()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 1,
            'registeredflg' => true,
        ]);
        $admin->markEmailAsVerified(); // メール認証を完了させる

        $staff = User::create([
            'name' => 'スタッフ',
            'email' => 'staff@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $staff->markEmailAsVerified();

        $currentMonth = Carbon::now();
        $nextMonth = $currentMonth->copy()->addMonth();

        Time::create([
            'user_id' => $staff->id,
            'date' => $nextMonth->copy()->day(15),
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $response = $this->actingAs($admin)->get('/timelog/list?user_id=' . $staff->id . '&year=' . $nextMonth->year . '&month=' . $nextMonth->month);

        $response->assertStatus(200);
        $response->assertViewHas('currentMonth');
        
        $currentMonthData = $response->viewData('currentMonth');
        $this->assertEquals($nextMonth->year, $currentMonthData->year);
        $this->assertEquals($nextMonth->month, $currentMonthData->month);
    }

    /**
     * ユーザー情報取得機能（管理者）
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     * 
     * テスト内容:
     * - 管理者ユーザーにログインをする
     * - 勤怠一覧ページを開く
     * - 「詳細」ボタンを押下する
     * 
     * 期待動作:
     * - その日の勤怠詳細画面に遷移する
     */
    public function test_admin_staff_timelog_detail_button_redirects_to_detail_page()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 1,
            'registeredflg' => true,
        ]);
        $admin->markEmailAsVerified(); // メール認証を完了させる

        $staff = User::create([
            'name' => 'スタッフ',
            'email' => 'staff@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $staff->markEmailAsVerified();

        $today = Carbon::today();
        Time::create([
            'user_id' => $staff->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/' . $staff->id . '?year=' . $today->year . '&month=' . $today->month . '&day=' . $today->day);

        $response->assertStatus(200);
        $response->assertViewHas('attendanceRecord');
        $response->assertViewHas('targetUser');
        
        $targetUser = $response->viewData('targetUser');
        $this->assertEquals($staff->id, $targetUser->id);
    }

    /**
     * 勤怠情報修正機能（管理者）
     * 修正申請の詳細内容が正しく表示されている
     * 
     * テスト内容:
     * - 管理者ユーザーにログインをする
     * - 修正申請の詳細画面を開く
     * 
     * 期待動作:
     * - 申請内容が正しく表示されている
     */
    public function test_admin_application_detail_displays_correct_content()
    {
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 1,
            'registeredflg' => true,
        ]);
        $admin->markEmailAsVerified(); // メール認証を完了させる

        $staff = User::create([
            'name' => 'スタッフ',
            'email' => 'staff@example.com',
            'password' => Hash::make('password123'),
            'actor_id' => 2,
            'registeredflg' => true,
        ]);
        $staff->markEmailAsVerified();

        $today = Carbon::today();
        $time = Time::create([
            'user_id' => $staff->id,
            'date' => $today,
            'arrival_time' => '09:00',
            'departure_time' => '18:00',
        ]);

        $application = Application::create([
            'user_id' => $staff->id,
            'time_id' => $time->id,
            'date' => $today,
            'arrival_time' => '09:30',
            'departure_time' => '18:30',
            'note' => '修正申請テスト',
            'application_flg' => 1, // 申請中
        ]);

        $response = $this->actingAs($admin)->get('/admin/application/detail/' . $staff->id . '?year=' . $today->year . '&month=' . $today->month . '&day=' . $today->day);

        $response->assertStatus(200);
        $response->assertViewHas('application');
        $response->assertViewHas('targetUser');
        $response->assertViewHas('date');
        
        $applicationData = $response->viewData('application');
        $targetUser = $response->viewData('targetUser');
        $date = $response->viewData('date');
        
        $this->assertNotNull($applicationData);
        $this->assertEquals($staff->id, $targetUser->id);
        $this->assertEquals($today->format('Y-m-d'), $date->format('Y-m-d'));
        $this->assertEquals('09:30', $applicationData->arrival_time);
        $this->assertEquals('18:30', $applicationData->departure_time);
        $this->assertEquals('修正申請テスト', $applicationData->note);
    }
}

