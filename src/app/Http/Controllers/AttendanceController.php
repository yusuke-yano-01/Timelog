<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Time;
use App\Models\Month;
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
        
        // 現在の月のデータを取得または作成
        $currentMonth = Month::where('year', $today->year)
            ->where('month', $today->month)
            ->first();
        
        if (!$currentMonth) {
            $currentMonth = Month::create([
                'year' => $today->year,
                'month' => $today->month,
                'end_date' => $today->daysInMonth,
            ]);
        }
        
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
        
        // 現在の月のデータを取得
        $currentMonth = Month::where('year', $today->year)
            ->where('month', $today->month)
            ->first();
        
        // 出勤記録を作成
        Time::create([
            'user_id' => $user->id,
            'month_id' => $currentMonth->id,
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
}
