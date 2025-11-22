<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Time;
use App\Models\User;
use App\Models\Breaktime;
use App\Models\Application;
use App\Models\Actor;
use App\Constants\TimeConstants;

class TimelogController extends Controller
{
    /**
     * 勤怠一覧画面を表示
     */
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        
        $targetUserId = $request->get('user_id');
        if ($targetUserId) {
            if ($currentUser->actor_id !== Actor::ADMIN_ID) {
                abort(403, 'Unauthorized access.');
            }
            $targetUser = User::find($targetUserId);
            if (!$targetUser) {
                abort(404, 'User not found.');
            }
        } else {
            $targetUser = $currentUser;
        }
        
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);
        
        $currentMonthStart = Carbon::create($year, $month, 1)->startOfMonth();
        $currentMonthEnd = Carbon::create($year, $month, 1)->endOfMonth();
        
        $prevMonth = Carbon::create($year, $month)->subMonth();
        $nextMonth = Carbon::create($year, $month)->addMonth();
        
        $attendanceRecords = Time::where('user_id', $targetUser->id)
            ->whereBetween('date', [
                $currentMonthStart->format('Y-m-d'),
                $currentMonthEnd->format('Y-m-d')
            ])
            ->with('breaktimes')
            ->get()
            ->keyBy(function ($record) {
                return $record->date->format('j');
            });
        
        // 申請情報を取得（申請中のものを確認するため）
        $applications = Application::where('user_id', $targetUser->id)
            ->whereBetween('date', [
                $currentMonthStart->format('Y-m-d'),
                $currentMonthEnd->format('Y-m-d')
            ])
            ->get()
            ->keyBy(function ($application) {
                return Carbon::parse($application->date)->format('j');
            });
        
        $attendanceData = [];
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day);
            $dayOfWeek = $this->getDayOfWeekJapanese($date->dayOfWeek);
            
            $record = $attendanceRecords->get($day);
            $application = $applications->get($day);
            
            // 申請中（application_flg === 1）の場合は、承認されるまで「-」を表示
            $isPending = $application && $application->application_flg === Application::STATUS_PENDING;
            
            if ($isPending) {
                $attendanceData[$day] = [
                    'dayOfWeek' => $dayOfWeek,
                    'arrival_time' => '-',
                    'departure_time' => '-',
                    'break_time' => '-',
                    'total_time' => '-',
                ];
            } elseif ($record) {
                $attendanceData[$day] = [
                    'dayOfWeek' => $dayOfWeek,
                    'arrival_time' => $record->arrival_time,
                    'departure_time' => $record->departure_time,
                    'break_time' => $this->calculateBreakTime($record),
                    'total_time' => $this->calculateTotalTime($record),
                ];
            } else {
                $attendanceData[$day] = [
                    'dayOfWeek' => $dayOfWeek,
                    'arrival_time' => null,
                    'departure_time' => null,
                    'break_time' => null,
                    'total_time' => null,
                ];
            }
        }
        
        $currentMonth = (object)[
            'year' => $year,
            'month' => $month,
        ];
        
        return view('staff.timelog_list', compact(
            'currentMonth',
            'prevMonth',
            'nextMonth',
            'attendanceData',
            'targetUser',
            'targetUserId'
        ));
    }
    
    /**
     * 勤怠詳細画面を表示
     */
    public function detail(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->actor_id === Actor::ADMIN_ID;
        
        $year = $request->get('year');
        $month = $request->get('month');
        $day = $request->get('day');
        $userId = $request->get('user_id');
        
        $targetUserId = $userId ?? $user->id;
        
        $date = Carbon::create($year, $month, $day);
        
        $attendanceRecord = Time::where('user_id', $targetUserId)
            ->where('date', $date->format('Y-m-d'))
            ->with('breaktimes')
            ->first();
        
        $application = null;
        if ($attendanceRecord) {
            $application = Application::where('time_id', $attendanceRecord->id)
                ->with('breaktimes')
                ->first();
        } else {
            $application = Application::where('user_id', $targetUserId)
                ->where('date', $date->format('Y-m-d'))
                ->with('breaktimes')
                ->first();
        }
        
        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            $targetUser = $user;
        }
        
        return view('staff.timelog_detail', compact(
            'attendanceRecord',
            'application',
            'date',
            'targetUser',
            'isAdmin'
        ));
    }
    
    /**
     * 曜日を日本語で取得
     */
    private function getDayOfWeekJapanese($dayOfWeek)
    {
        $days = ['日', '月', '火', '水', '木', '金', '土'];
        return $days[$dayOfWeek];
    }
    
    /**
     * 休憩時間を計算
     */
    private function calculateBreakTime($record)
    {
        $totalBreakMinutes = 0;
        
        // breaktimesリレーションから休憩時間を取得
        foreach ($record->breaktimes as $breaktime) {
            if ($breaktime->start_break_time && $breaktime->end_break_time) {
                $start = Carbon::createFromFormat('H:i', $breaktime->start_break_time);
                $end = Carbon::createFromFormat('H:i', $breaktime->end_break_time);
                $totalBreakMinutes += $end->diffInMinutes($start);
            }
        }
        
        if ($totalBreakMinutes > 0) {
            $hours = intval($totalBreakMinutes / TimeConstants::MINUTES_PER_HOUR);
            $minutes = $totalBreakMinutes % TimeConstants::MINUTES_PER_HOUR;
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
                    if ($breaktime->start_break_time && $breaktime->end_break_time) {
                        $start = Carbon::createFromFormat('H:i', $breaktime->start_break_time);
                        $end = Carbon::createFromFormat('H:i', $breaktime->end_break_time);
                $breakMinutes += $end->diffInMinutes($start);
            }
        }
        
        $workMinutes = $totalMinutes - $breakMinutes;
        
        if ($workMinutes > 0) {
            $hours = intval($workMinutes / TimeConstants::MINUTES_PER_HOUR);
            $minutes = $workMinutes % TimeConstants::MINUTES_PER_HOUR;
            return sprintf('%d:%02d', $hours, $minutes);
        }
        
        return null;
    }
    
    /**
     * 申請を承認（管理者のみ）
     */
    public function approve(Request $request)
    {
        $user = Auth::user();
        
        // 管理者のみアクセス可能
        if ($user->actor_id !== Actor::ADMIN_ID) {
            abort(403, 'Unauthorized access.');
        }
        
        $applicationId = $request->get('application_id');
        $date = $request->get('date');
        $userId = $request->get('user_id');
        
        $application = Application::find($applicationId);
        
        if (!$application) {
            return back()->withErrors(['error' => '申請が見つかりません。']);
        }
        
        // 承認待ちの状態のみ承認可能
        if ($application->application_flg !== Application::STATUS_PENDING) {
            return back()->withErrors(['error' => 'この申請は既に承認済みです。']);
        }
        
        // 承認：application_flgを0に設定し、TimesテーブルとBreaktimesテーブルを更新
        $application->update(['application_flg' => Application::STATUS_APPROVED]);
        
        // Timesテーブルを更新（申請の内容を反映）
        $timeRecord = $application->time;
        if ($timeRecord) {
            $timeRecord->update([
                'arrival_time' => $application->arrival_time,
                'departure_time' => $application->departure_time,
                'note' => $application->note,
            ]);
            
            // 既存の休憩時間を削除
            $timeRecord->breaktimes()->delete();
            
            // 申請の休憩時間をTimesに反映
            foreach ($application->breaktimes as $appBreaktime) {
                        Breaktime::create([
                            'time_id' => $timeRecord->id,
                            'start_break_time' => $appBreaktime->start_break_time,
                            'end_break_time' => $appBreaktime->end_break_time,
                        ]);
            }
        }
        
        // 申請詳細画面から来た場合は申請詳細画面に戻る、そうでなければ通常の詳細画面に戻る
        $fromApplicationDetail = $request->get('from_application_detail');
        
        if ($fromApplicationDetail) {
            // 申請詳細画面に戻る
            // $dateがCarbonオブジェクトの場合はそのまま使用、文字列の場合はパース
            $dateObj = $date instanceof Carbon ? $date : Carbon::parse($date);
            $redirectParams = [
                'id' => $application->user_id,
                'year' => $dateObj->year,
                'month' => $dateObj->month,
                'day' => $dateObj->day,
            ];
            return redirect()->route('admin.application.detail', $redirectParams)->with('success', '申請を承認しました。');
        }
        
        // 通常の詳細画面に戻る場合
        // $application->dateがCarbonオブジェクトの場合はそのまま使用、文字列の場合はパース
        $applicationDate = $application->date instanceof Carbon ? $application->date : Carbon::parse($application->date);
        $detailParams = [
            'year' => $applicationDate->year,
            'month' => $applicationDate->month,
            'day' => $applicationDate->day,
        ];
        
        if ($userId) {
            $detailParams['user_id'] = $userId;
        }
        
        return redirect()->route('timelog.detail', $detailParams)->with('success', '申請を承認しました。');
    }
    
    /**
     * 申請一覧画面を表示
     */
    public function application(Request $request)
    {
        $user = Auth::user();
        $status = $request->get('status', 'pending'); // pending or approved
        
        $query = Application::where('user_id', $user->id)
            ->with(['user', 'time'])
            ->orderBy('date', 'desc');
        
        if ($status === 'pending') {
            $query->where('application_flg', Application::STATUS_PENDING);
        } else {
            $query->where('application_flg', Application::STATUS_APPROVED);
        }
        
        $applications = $query->get();
        
        return view('staff.timelog_application', compact('applications', 'status'));
    }
    
    /**
     * CSVダウンロード（管理者用）
     */
    public function downloadCsv(Request $request)
    {
        $currentUser = Auth::user();
        
        // 管理者のみアクセス可能
        if ($currentUser->actor_id !== Actor::ADMIN_ID) {
            abort(403, 'Unauthorized access.');
        }
        
        // user_idパラメータが必須
        $targetUserId = $request->get('user_id');
        if (!$targetUserId) {
            abort(400, 'User ID is required.');
        }
        
        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            abort(404, 'User not found.');
        }
        
        // 年・月のパラメータを取得（デフォルトは現在の年月）
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);
        
        // Carbonで月の開始日と終了日を計算
        $currentMonthStart = Carbon::create($year, $month, 1)->startOfMonth();
        $currentMonthEnd = Carbon::create($year, $month, 1)->endOfMonth();
        
        // 指定された月の勤怠データを取得
        $attendanceRecords = Time::where('user_id', $targetUser->id)
            ->whereBetween('date', [
                $currentMonthStart->format('Y-m-d'),
                $currentMonthEnd->format('Y-m-d')
            ])
            ->with('breaktimes')
            ->get()
            ->keyBy(function ($record) {
                return $record->date->format('j'); // 日付の日部分をキーにする
            });
        
        // CSVデータを準備
        $csvData = [];
        $csvData[] = ['日付', '出勤', '退勤', '休憩', '合計'];
        
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day);
            $record = $attendanceRecords->get($day);
            
            $dateStr = sprintf('%04d/%02d/%02d', $year, $month, $day);
            $arrivalTime = '-';
            $departureTime = '-';
            $breakTime = '-';
            $totalTime = '-';
            
            if ($record) {
                $arrivalTime = $record->arrival_time ?? '-';
                $departureTime = $record->departure_time ?? '-';
                $breakTime = $this->calculateBreakTime($record) ?? '-';
                $totalTime = $this->calculateTotalTime($record) ?? '-';
            }
            
            $csvData[] = [
                $dateStr,
                $arrivalTime,
                $departureTime,
                $breakTime,
                $totalTime
            ];
        }
        
        // CSVファイル名を生成
        $filename = sprintf('%s_%04d%02d.csv', $targetUser->name, $year, $month);
        
        // CSV出力
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            // BOMを追加（Excelで文字化けを防ぐ）
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
