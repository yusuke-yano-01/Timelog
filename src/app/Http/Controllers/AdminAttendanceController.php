<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Time;
use App\Models\Actor;
use App\Models\Application;
use App\Constants\TimeConstants;

class AdminAttendanceController extends Controller
{
    /**
     * 当日勤怠一覧画面を表示（管理者用）
     */
    public function attendanceList(Request $request)
    {
        $requestedDate = $request->get('date');
        $targetDate = $requestedDate ? Carbon::parse($requestedDate) : Carbon::today();
        
        $staffs = User::where('registeredflg', true)
            ->where('actor_id', '!=', Actor::ADMIN_ID)
            ->orderBy('name')
            ->get();
        
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
        
        $prevDate = $targetDate->copy()->subDay();
        $nextDate = $targetDate->copy()->addDay();
        
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
     * 特定スタッフの勤怠詳細画面を表示（管理者用）
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

        if (!$year || !$month || !$day) {
            abort(400, 'Date parameters (year, month, day) are required.');
        }
        
        $date = Carbon::create($year, $month, $day);
        
        $attendanceRecord = Time::where('user_id', $targetUser->id)
            ->where('date', $date->format('Y-m-d'))
            ->with('breaktimes')
            ->first();
        
        $application = null;
        if ($attendanceRecord) {
            $application = Application::where('time_id', $attendanceRecord->id)
                ->with('breaktimes')
                ->first();
        }
        
        $isAdmin = true;
        
        return view('staff.timelog_detail', compact(
            'attendanceRecord',
            'application',
            'date',
            'targetUser',
            'isAdmin'
        ));
    }
    
    /**
     * 休憩時間を計算
     */
    private function calculateBreakTime($record)
    {
        $totalBreakMinutes = 0;
        
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
}

