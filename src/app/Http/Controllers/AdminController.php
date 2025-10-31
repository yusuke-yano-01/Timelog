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
        ]);

        $credentials = $request->only(['email', 'password']);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect('/admin/attendance/list');
        }

        return back()->withErrors([
            'login' => 'メールアドレスまたはパスワードが正しくありません。',
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
            ->get();
        
        // 各スタッフの当日の勤怠データを取得
        $attendanceRecords = [];
        foreach ($staffs as $staff) {
            $attendance = Time::where('user_id', $staff->id)
                ->where('date', $targetDate->format('Y-m-d'))
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
        
        // 休憩1
        if ($record->start_break_time1 && $record->end_break_time1) {
            $start1 = Carbon::createFromFormat('H:i', $record->start_break_time1);
            $end1 = Carbon::createFromFormat('H:i', $record->end_break_time1);
            $totalBreakMinutes += $end1->diffInMinutes($start1);
        }
        
        // 休憩2
        if ($record->start_break_time2 && $record->end_break_time2) {
            $start2 = Carbon::createFromFormat('H:i', $record->start_break_time2);
            $end2 = Carbon::createFromFormat('H:i', $record->end_break_time2);
            $totalBreakMinutes += $end2->diffInMinutes($start2);
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
        
        // 休憩時間を差し引く
        $breakMinutes = 0;
        
        if ($record->start_break_time1 && $record->end_break_time1) {
            $start1 = Carbon::createFromFormat('H:i', $record->start_break_time1);
            $end1 = Carbon::createFromFormat('H:i', $record->end_break_time1);
            $breakMinutes += $end1->diffInMinutes($start1);
        }
        
        if ($record->start_break_time2 && $record->end_break_time2) {
            $start2 = Carbon::createFromFormat('H:i', $record->start_break_time2);
            $end2 = Carbon::createFromFormat('H:i', $record->end_break_time2);
            $breakMinutes += $end2->diffInMinutes($start2);
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

        $query = Time::with('user')
            ->whereHas('user', function($q) {
                $q->where('registeredflg', true)
                  ->where('actor_id', '!=', 1); // 管理者を除外
            })
            ->orderBy('date', 'desc');

        if ($status === 'pending') {
            $query->where('application_flg', true);
        } else {
            $query->where('application_flg', false);
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
        
        // 指定された日の勤怠データを取得
        $attendanceRecord = Time::where('user_id', $targetUser->id)
            ->where('date', $date->format('Y-m-d'))
            ->first();
        
        return view('timelog_detail', compact(
            'attendanceRecord',
            'date',
            'targetUser'
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
        
        // 指定された日の勤怠データを取得
        $attendanceRecord = Time::where('user_id', $targetUser->id)
            ->where('date', $date->format('Y-m-d'))
            ->first();

        if (!$attendanceRecord) {
            abort(404, 'Attendance record not found.');
        }

        $isPending = $attendanceRecord->application_flg;
        
        return view('admin.application_detail', compact(
            'attendanceRecord',
            'date',
            'targetUser',
            'isPending'
        ));
    }
}

