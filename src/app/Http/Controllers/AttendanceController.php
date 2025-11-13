<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Time;
use App\Models\Breaktime;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // 管理者は出勤・退勤画面にアクセスできない
        if ($user->actor_id === 1) {
            return redirect('/admin/attendance/list');
        }
        
        $today = Carbon::today();
        
        // 今日の勤怠記録を取得
        $todayAttendance = Time::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
        
        // Carbonで月の情報を計算
        $currentMonth = (object)[
            'year' => $today->year,
            'month' => $today->month,
            'end_date' => $today->daysInMonth,
        ];
        
        // 曜日の漢字配列
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $weekday = $weekdays[$today->dayOfWeek];
        
        return view('staff.attendance', compact('user', 'todayAttendance', 'currentMonth', 'today', 'weekday'));
    }
    
    public function clockIn(Request $request)
    {
        $user = Auth::user();
        
        // 管理者は出勤・退勤機能を使用できない
        if ($user->actor_id === 1) {
            return redirect('/admin/attendance/list')->withErrors(['error' => '管理者は出勤・退勤機能を使用できません。']);
        }
        
        $today = Carbon::today();
        
        // 既に今日の出勤記録があるかチェック
        $existingRecord = Time::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
        
        if ($existingRecord) {
            return back()->withErrors(['error' => '今日は既に出勤済みです。']);
        }
        
        // 出勤記録を作成
        Time::create([
            'user_id' => $user->id,
            'date' => $today,
            'arrival_time' => Carbon::now()->format('H:i'),
        ]);
        
        return redirect('/')->with('success', '出勤を記録しました。');
    }
    
    public function clockOut(Request $request)
    {
        $user = Auth::user();
        
        // 管理者は出勤・退勤機能を使用できない
        if ($user->actor_id === 1) {
            return redirect('/admin/attendance/list')->withErrors(['error' => '管理者は出勤・退勤機能を使用できません。']);
        }
        
        $today = Carbon::today();
        
        // 今日の出勤記録を取得
        $attendanceRecord = Time::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
        
        if (!$attendanceRecord) {
            return back()->withErrors(['error' => '出勤記録が見つかりません。']);
        }
        
        if ($attendanceRecord->departure_time) {
            return back()->withErrors(['error' => '今日は既に退勤済みです。']);
        }
        
        // 退勤時間を更新
        $attendanceRecord->update([
            'departure_time' => Carbon::now()->format('H:i'),
        ]);
        
        return redirect('/')->with('success', '退勤を記録しました。');
    }
    
    public function startBreak(Request $request)
    {
        $user = Auth::user();
        
        // 管理者は出勤・退勤機能を使用できない
        if ($user->actor_id === 1) {
            return redirect('/admin/attendance/list')->withErrors(['error' => '管理者は出勤・退勤機能を使用できません。']);
        }
        
        $today = Carbon::today();
        
        // 今日の出勤記録を取得
        $attendanceRecord = Time::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
        
        if (!$attendanceRecord) {
            return back()->withErrors(['error' => '出勤記録が見つかりません。']);
        }
        
        if ($attendanceRecord->departure_time) {
            return back()->withErrors(['error' => '既に退勤済みです。']);
        }
        
        if ($user->break_flg) {
            return back()->withErrors(['error' => '既に休憩中です。']);
        }
        
        // 休憩開始時間を記録（Breaktimesテーブルに保存）
        $breakStartTime = Carbon::now()->format('H:i');
        
        // 既存の休憩時間を確認（リレーションを読み込む）
        $attendanceRecord->load('breaktimes');
        $breaktimes = $attendanceRecord->breaktimes;
        $openBreaktime = $breaktimes->first(function($bt) {
            return $bt->start_break_time && !$bt->end_break_time1;
        });
        
        if (!$openBreaktime) {
            // 新しい休憩時間レコードを作成
            if ($breaktimes->count() < 2) {
                Breaktime::create([
                    'time_id' => $attendanceRecord->id,
                    'start_break_time' => $breakStartTime,
                    'end_break_time1' => null, // 休憩終了時に更新
                ]);
                $user->break_flg = true;
                $user->save();
            } else {
                return back()->withErrors(['error' => '休憩回数の上限に達しています。']);
            }
        } else {
            return back()->withErrors(['error' => '既に休憩中です。']);
        }
        
        return redirect('/')->with('success', '休憩を開始しました。');
    }
    
    public function endBreak(Request $request)
    {
        $user = Auth::user();
        
        // 管理者は出勤・退勤機能を使用できない
        if ($user->actor_id === 1) {
            return redirect('/admin/attendance/list')->withErrors(['error' => '管理者は出勤・退勤機能を使用できません。']);
        }
        
        $today = Carbon::today();
        
        // 今日の出勤記録を取得
        $attendanceRecord = Time::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
        
        if (!$attendanceRecord) {
            return back()->withErrors(['error' => '出勤記録が見つかりません。']);
        }
        
        if (!$user->break_flg) {
            return back()->withErrors(['error' => '休憩中ではありません。']);
        }
        
        // 休憩終了時間を記録（Breaktimesテーブルを更新）
        $breakEndTime = Carbon::now()->format('H:i');
        
        // 開始済みで終了していない休憩時間を取得（リレーションを読み込む）
        $attendanceRecord->load('breaktimes');
        $openBreaktime = $attendanceRecord->breaktimes->first(function($bt) {
            return $bt->start_break_time && !$bt->end_break_time1;
        });
        
        if ($openBreaktime) {
            $openBreaktime->end_break_time1 = $breakEndTime;
            $openBreaktime->save();
            $user->break_flg = false;
            $user->save();
        }
        
        return redirect('/')->with('success', '休憩を終了しました。');
    }
}
