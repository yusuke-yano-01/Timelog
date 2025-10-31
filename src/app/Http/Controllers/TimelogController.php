<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Time;
use App\Models\User;

class TimelogController extends Controller
{
    /**
     * 勤怠一覧画面を表示
     */
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        
        // user_idパラメータが渡されている場合はそのユーザー、そうでなければ現在のユーザー（管理者が他のスタッフを見る場合）
        $targetUserId = $request->get('user_id');
        if ($targetUserId) {
            // 管理者の場合のみ他のユーザーのデータを見ることができる
            if ($currentUser->actor_id !== 1) {
                abort(403, 'Unauthorized access.');
            }
            $targetUser = User::find($targetUserId);
            if (!$targetUser) {
                abort(404, 'User not found.');
            }
        } else {
            $targetUser = $currentUser;
        }
        
        // 年・月のパラメータを取得（デフォルトは現在の年月）
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);
        
        // Carbonで月の開始日と終了日を計算
        $currentMonthStart = Carbon::create($year, $month, 1)->startOfMonth();
        $currentMonthEnd = Carbon::create($year, $month, 1)->endOfMonth();
        
        // 前月・翌月の情報を取得
        $prevMonth = Carbon::create($year, $month)->subMonth();
        $nextMonth = Carbon::create($year, $month)->addMonth();
        
        // 指定された月の勤怠データを取得（対象ユーザーのデータ）
        // dateカラムで月の範囲をフィルタリング
        $attendanceRecords = Time::where('user_id', $targetUser->id)
            ->whereBetween('date', [
                $currentMonthStart->format('Y-m-d'),
                $currentMonthEnd->format('Y-m-d')
            ])
            ->get()
            ->keyBy(function ($record) {
                return $record->date->format('j'); // 日付の日部分をキーにする
            });
        
        // 月の全日付の勤怠データを準備
        $attendanceData = [];
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day);
            $dayOfWeek = $this->getDayOfWeekJapanese($date->dayOfWeek);
            
            $record = $attendanceRecords->get($day);
            
            if ($record) {
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
        
        // ビューに渡すために年月情報を準備
        $currentMonth = (object)[
            'year' => $year,
            'month' => $month,
        ];
        
        return view('timelog_list', compact(
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
        $isAdmin = $user->actor_id === 1; // 管理者のactor_idは1
        
        $year = $request->get('year');
        $month = $request->get('month');
        $day = $request->get('day');
        $userId = $request->get('user_id'); // 管理者が他のユーザーを見る場合のパラメータ
        
        // user_idが指定されている場合はそのユーザー、そうでなければ現在のユーザー
        $targetUserId = $userId ?? $user->id;
        
        $date = Carbon::create($year, $month, $day);
        
        // 指定された日の勤怠データを取得
        $attendanceRecord = Time::where('user_id', $targetUserId)
            ->where('date', $date->format('Y-m-d'))
            ->first();
        
        // 対象ユーザーを取得（管理者が他のユーザーを見る場合）
        $targetUser = $userId ? User::find($targetUserId) : $user;
        
        return view('timelog_detail', compact(
            'attendanceRecord',
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
     * 勤怠詳細を更新
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->actor_id === 1;
        $timeId = $request->get('time_id');
        $date = $request->get('date');
        $userId = $request->get('user_id'); // 管理者が他のユーザーのデータを編集する場合
        
        // 対象ユーザーIDを決定
        $targetUserId = $userId ?? $user->id;
        
        // スタッフが自分のデータ以外を編集しようとした場合はエラー
        if (!$isAdmin && $targetUserId !== $user->id) {
            abort(403, 'Unauthorized access.');
        }
        
        $data = [
            'arrival_time' => $request->get('arrival_time'),
            'departure_time' => $request->get('departure_time'),
            'start_break_time1' => $request->get('start_break_time1'),
            'end_break_time1' => $request->get('end_break_time1'),
            'start_break_time2' => $request->get('start_break_time2'),
            'end_break_time2' => $request->get('end_break_time2'),
            'note' => $request->get('note'),
        ];
        
        // 勤怠詳細画面から修正した場合は、管理者・スタッフ問わず申請状態にする
        // 申請詳細画面から修正した場合は管理者のみ編集可能で状態変更なし（承認機能のみ）
        $fromApplicationDetail = $request->get('from_application_detail');
        
        if (!$fromApplicationDetail) {
            // 勤怠詳細画面からの修正：申請状態にする
            $data['application_flg'] = true;
        }
        // 申請詳細画面からの修正の場合はapplication_flgを更新しない（現在の値を維持）
        
        // 空の値をnullに変換
        foreach ($data as $key => $value) {
            if (empty($value)) {
                $data[$key] = null;
            }
        }
        
        if ($timeId) {
            // 既存のレコードを更新
            $timeRecord = Time::where('id', $timeId)
                ->where('user_id', $targetUserId)
                ->first();
            
            if ($timeRecord) {
                // 申請詳細画面からの修正の場合は承認待ちフラグを維持（変更しない）
                if ($fromApplicationDetail && $isAdmin) {
                    // application_flgを除外して更新
                    unset($data['application_flg']);
                }
                $timeRecord->update($data);
            }
        } else {
            // 新しいレコードを作成
            $data['user_id'] = $targetUserId;
            $data['date'] = $date;
            
            // 勤怠詳細画面から作成した場合は申請状態にする
            if (!$fromApplicationDetail) {
                $data['application_flg'] = true;
            } else {
                $data['application_flg'] = false; // 申請詳細画面からの作成は承認済み（管理者のみ）
            }
            
            Time::create($data);
        }
        
        $redirectParams = [
            'year' => Carbon::createFromFormat('Y-m-d', $date)->year,
            'month' => Carbon::createFromFormat('Y-m-d', $date)->month,
            'day' => Carbon::createFromFormat('Y-m-d', $date)->day,
        ];
        
        if ($userId) {
            $redirectParams['user_id'] = $userId;
        }
        
        $message = $isAdmin ? '勤怠情報を更新しました。' : '申請を送信しました。';
        
        return redirect()->route('timelog.detail', $redirectParams)->with('success', $message);
    }
    
    /**
     * 申請を承認（管理者のみ）
     */
    public function approve(Request $request)
    {
        $user = Auth::user();
        
        // 管理者のみアクセス可能
        if ($user->actor_id !== 1) {
            abort(403, 'Unauthorized access.');
        }
        
        $timeId = $request->get('time_id');
        $date = $request->get('date');
        $userId = $request->get('user_id');
        
        $timeRecord = Time::find($timeId);
        
        if (!$timeRecord) {
            return back()->withErrors(['error' => '勤怠記録が見つかりません。']);
        }
        
        // 承認待ちの状態のみ承認可能
        if (!$timeRecord->application_flg) {
            return back()->withErrors(['error' => 'この申請は既に承認済みです。']);
        }
        
        // 承認（application_flgをfalseに設定）
        $timeRecord->update(['application_flg' => false]);
        
        // 申請詳細画面から来た場合は申請詳細画面に戻る、そうでなければ通常の詳細画面に戻る
        $fromApplicationDetail = $request->get('from_application_detail');
        
        if ($fromApplicationDetail) {
            // 申請詳細画面に戻る
            $redirectParams = [
                'id' => $timeRecord->user_id,
                'year' => Carbon::createFromFormat('Y-m-d', $date)->year,
                'month' => Carbon::createFromFormat('Y-m-d', $date)->month,
                'day' => Carbon::createFromFormat('Y-m-d', $date)->day,
            ];
            return redirect()->route('admin.application.detail', $redirectParams)->with('success', '申請を承認しました。');
        }
        
        // 通常の詳細画面に戻る場合
        $detailParams = [
            'year' => Carbon::createFromFormat('Y-m-d', $date)->year,
            'month' => Carbon::createFromFormat('Y-m-d', $date)->month,
            'day' => Carbon::createFromFormat('Y-m-d', $date)->day,
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
        
        $query = Time::where('user_id', $user->id)
            ->with('user')
            ->orderBy('date', 'desc');
        
        if ($status === 'pending') {
            $query->where('application_flg', true);
        } else {
            $query->where('application_flg', false);
        }
        
        $applications = $query->get();
        
        return view('timelog_application', compact('applications', 'status'));
    }
}
