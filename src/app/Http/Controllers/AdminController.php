<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Time;

class AdminController extends Controller
{
    public function index()
    {
        return view('auth.admin_login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ], [
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => '有効なメールアドレスを入力してください。',
            'password.required' => 'パスワードを入力してください',
            'password.min' => 'パスワードは6文字以上で入力してください。',
        ]);

        $credentials = $request->only(['email', 'password']);

        if (Auth::attempt($credentials)) {
            // 管理者（actor_id = 1）でない場合はログインを拒否
            if (Auth::user()->actor_id !== 1) {
                Auth::logout();
                return back()->withErrors([
                    'login' => '管理者アカウントでログインしてください。',
                ]);
            }
            
            $request->session()->regenerate();
            return redirect('/admin/attendance/list');
        }

        return back()->withErrors([
            'login' => 'ログイン情報が登録されていません',
        ]);
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/admin/login');
    }

    /**
     * 当日勤怠一覧画面を表示（管理者用）
     */
    public function attendanceList(Request $request)
    {
        // 日付パラメータを取得（デフォルトは今日）
        $dateParam = $request->get('date');
        $targetDate = $dateParam ? Carbon::parse($dateParam) : Carbon::today();
        
        // すべての登録スタッフを取得（registeredflgがtrueでactor_idが1でないユーザー）
        $staffs = User::where('registeredflg', true)
            ->where('actor_id', '!=', 1) // 管理者を除外
            ->orderBy('name')
            ->get();
        
        // 各スタッフの当日の勤怠データを取得
        $attendanceRecords = [];
        foreach ($staffs as $staff) {
            $attendance = Time::where('user_id', $staff->id)
                ->where('date', $targetDate->format('Y-m-d'))
                ->with('breaktimes')
                ->first();
            
            $attendanceRecords[] = [
                'staff' => $staff,
                'attendance' => $attendance,
                'break_time' => $attendance ? $this->calculateBreakTime($attendance) : null,
                'total_time' => $attendance ? $this->calculateTotalTime($attendance) : null,
            ];
        }
        
        // 前日・翌日の情報
        $prevDate = $targetDate->copy()->subDay();
        $nextDate = $targetDate->copy()->addDay();
        
        // 曜日を日本語で取得
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $weekday = $weekdays[$targetDate->dayOfWeek];
        
        return view('admin.attendance_list', compact(
            'targetDate',
            'prevDate',
            'nextDate',
            'weekday',
            'attendanceRecords'
        ));
    }
    
    /**
     * 休憩時間を計算
     */
    private function calculateBreakTime($record)
    {
        $totalBreakMinutes = 0;
        
        // breaktimesリレーションから休憩時間を取得
        foreach ($record->breaktimes as $breaktime) {
            if ($breaktime->start_break_time && $breaktime->end_break_time1) {
                $start = Carbon::createFromFormat('H:i', $breaktime->start_break_time);
                $end = Carbon::createFromFormat('H:i', $breaktime->end_break_time1);
                $totalBreakMinutes += $end->diffInMinutes($start);
            }
        }
        
        if ($totalBreakMinutes > 0) {
            $hours = intval($totalBreakMinutes / 60);
            $minutes = $totalBreakMinutes % 60;
            return sprintf('%d:%02d', $hours, $minutes);
        }
        
        return null;
    }
    
    /**
     * 総勤務時間を計算
     */
    private function calculateTotalTime($record)
    {
        if (!$record->arrival_time || !$record->departure_time) {
            return null;
        }
        
        $arrival = Carbon::createFromFormat('H:i', $record->arrival_time);
        $departure = Carbon::createFromFormat('H:i', $record->departure_time);
        
        $totalMinutes = $departure->diffInMinutes($arrival);
        
        // 休憩時間を差し引く（breaktimesリレーションから取得）
        $breakMinutes = 0;
        foreach ($record->breaktimes as $breaktime) {
            if ($breaktime->start_break_time && $breaktime->end_break_time1) {
                $start = Carbon::createFromFormat('H:i', $breaktime->start_break_time);
                $end = Carbon::createFromFormat('H:i', $breaktime->end_break_time1);
                $breakMinutes += $end->diffInMinutes($start);
            }
        }
        
        $workMinutes = $totalMinutes - $breakMinutes;
        
        if ($workMinutes > 0) {
            $hours = intval($workMinutes / 60);
            $minutes = $workMinutes % 60;
            return sprintf('%d:%02d', $hours, $minutes);
        }
        
        return null;
    }

    /**
     * スタッフ一覧画面を表示（管理者用）
     */
    public function staffList()
    {
        // すべての登録スタッフを取得（registeredflgがtrueでactor_idが1でないユーザー）
        $staffs = User::where('registeredflg', true)
            ->where('actor_id', '!=', 1) // 管理者を除外
            ->orderBy('name')
            ->get();

        return view('admin.staff_list', compact('staffs'));
    }

    /**
     * 申請一覧画面を表示（管理者用）
     */
    public function applicationList(Request $request)
    {
        $status = $request->get('status', 'pending'); // pending or approved

        $query = \App\Models\Application::with(['user', 'time'])
            ->whereHas('user', function($q) {
                $q->where('registeredflg', true)
                  ->where('actor_id', '!=', 1); // 管理者を除外
            })
            ->orderBy('date', 'desc');

        if ($status === 'pending') {
            $query->where('application_flg', 1); // 申請中
        } else {
            $query->where('application_flg', 0); // 承認済み
        }

        $applications = $query->get();

        return view('admin.application_list', compact('applications', 'status'));
    }

    /**
     * 特定スタッフの勤怠詳細画面を表示（管理者用）
     * Path: /admin/attendance/{id}?year=YYYY&month=MM&day=DD
     */
    public function showAttendanceDetail(Request $request, $id)
    {
        $targetUser = User::find($id);
        if (!$targetUser) {
            abort(404, 'Staff not found.');
        }

        $year = $request->get('year');
        $month = $request->get('month');
        $day = $request->get('day');

        // 日付パラメータが不足している場合はエラー
        if (!$year || !$month || !$day) {
            abort(400, 'Date parameters (year, month, day) are required.');
        }
        
        $date = Carbon::create($year, $month, $day);
        
        // 指定された日の勤怠データを取得（休憩時間も一緒に取得）
        $attendanceRecord = Time::where('user_id', $targetUser->id)
            ->where('date', $date->format('Y-m-d'))
            ->with('breaktimes')
            ->first();
        
        // 申請情報を取得
        $application = null;
        if ($attendanceRecord) {
            $application = \App\Models\Application::where('time_id', $attendanceRecord->id)
                ->with('breaktimes')
                ->first();
        }
        
        $isAdmin = true; // 管理者用の画面なのでtrue
        
        return view('staff.timelog_detail', compact(
            'attendanceRecord',
            'application',
            'date',
            'targetUser',
            'isAdmin'
        ));
    }

    /**
     * 申請詳細画面を表示（管理者用）
     * Path: /admin/application/detail/{id}?year=YYYY&month=MM&day=DD
     */
    public function applicationDetail(Request $request, $id)
    {
        // 管理者のみアクセス可能であることを確認
        if (Auth::user()->actor_id !== 1) {
            abort(403, 'Unauthorized access.');
        }

        $targetUser = User::find($id);
        if (!$targetUser) {
            abort(404, 'Staff not found.');
        }

        $year = $request->get('year');
        $month = $request->get('month');
        $day = $request->get('day');

        // 日付パラメータが不足している場合はエラー
        if (!$year || !$month || !$day) {
            abort(400, 'Date parameters (year, month, day) are required.');
        }
        
        $date = Carbon::create($year, $month, $day);
        
        // 指定された日の申請データを取得
        $application = \App\Models\Application::where('user_id', $targetUser->id)
            ->where('date', $date->format('Y-m-d'))
            ->with(['time', 'breaktimes'])
            ->first();

        if (!$application) {
            abort(404, 'Application not found.');
        }

        $isPending = $application->application_flg === 1;
        
        return view('admin.application_detail', compact(
            'application',
            'date',
            'targetUser',
            'isPending'
        ));
    }
}

