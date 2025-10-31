<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Time;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
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
        
        return view('attendance', compact('user', 'todayAttendance', 'currentMonth', 'today', 'weekday'));
    }
    
    public function clockIn(Request $request)
    {
        $user = Auth::user();
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
        
        // 休憩開始時間を記録
        $breakStartTime = Carbon::now()->format('H:i');
        
        if (!$attendanceRecord->start_break_time1) {
            $attendanceRecord->update([
                'start_break_time1' => $breakStartTime,
            ]);
            $user->update(['break_flg' => true]);
        } elseif (!$attendanceRecord->start_break_time2) {
            $attendanceRecord->update([
                'start_break_time2' => $breakStartTime,
            ]);
            $user->update(['break_flg' => true]);
        } else {
            return back()->withErrors(['error' => '休憩回数の上限に達しています。']);
        }
        
        return redirect('/')->with('success', '休憩を開始しました。');
    }
    
    public function endBreak(Request $request)
    {
        $user = Auth::user();
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
        
        // 休憩終了時間を記録
        $breakEndTime = Carbon::now()->format('H:i');
        
        if ($attendanceRecord->start_break_time1 && !$attendanceRecord->end_break_time1) {
            $attendanceRecord->update([
                'end_break_time1' => $breakEndTime,
            ]);
            $user->update(['break_flg' => false]);
        } elseif ($attendanceRecord->start_break_time2 && !$attendanceRecord->end_break_time2) {
            $attendanceRecord->update([
                'end_break_time2' => $breakEndTime,
            ]);
            $user->update(['break_flg' => false]);
        }
        
        return redirect('/')->with('success', '休憩を終了しました。');
    }
}
