<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Time;
use App\Models\User;
use App\Models\Breaktime;
use App\Models\Application;
use App\Models\Actor;

class TimelogUpdateController extends Controller
{
    /**
     * 勤怠詳細を更新
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->actor_id === Actor::ADMIN_ID;
        $timeId = $request->get('time_id');
        $date = $request->get('date');
        $userId = $request->get('user_id');
        
        $targetUserId = $this->determineTargetUserId($timeId, $date, $userId, $user, $isAdmin);
        
        if (!$isAdmin && $targetUserId !== $user->id) {
            abort(403, 'Unauthorized access.');
        }
        
        $timeData = [
            'arrival_time' => $request->get('arrival_time'),
            'departure_time' => $request->get('departure_time'),
            'note' => $request->get('note'),
        ];
        
        // 空の値をnullに変換（バリデーション前に実行）
        foreach ($timeData as $key => $value) {
            if ($value === '' || $value === null) {
                $timeData[$key] = null;
            }
        }
        
        $breakTimesData = $this->prepareBreakTimesData($request);
        
        $errors = $this->validateTimelogData($timeData, $breakTimesData, $isAdmin);
        
        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }
        
        $fromApplicationDetail = $request->get('from_application_detail');
        
        if ($timeId) {
            $this->updateExistingTimelog($timeId, $targetUserId, $timeData, $breakTimesData, $date, $isAdmin, $fromApplicationDetail);
        } else {
            $this->createNewTimelog($targetUserId, $date, $timeData, $breakTimesData, $isAdmin, $fromApplicationDetail);
        }
        
        $message = $isAdmin ? '勤怠情報を更新しました。' : '申請を送信しました。';
        
        return $this->redirectAfterUpdate($date, $userId, $targetUserId, $isAdmin, $message);
    }
    
    /**
     * 対象ユーザーIDを決定
     */
    private function determineTargetUserId($timeId, $date, $userId, $user, $isAdmin)
    {
        $targetUserId = null;
        
        if ($timeId) {
            $existingTimeRecord = Time::find($timeId);
            if ($existingTimeRecord) {
                $targetUserId = $existingTimeRecord->user_id;
            }
        }
        
        if (!$targetUserId) {
            if ($userId) {
                $targetUserId = $userId;
            } else {
                if ($isAdmin) {
                    $existingTimeByDate = Time::where('date', $date)
                        ->where('user_id', '!=', $user->id)
                        ->first();
                    if ($existingTimeByDate) {
                        $targetUserId = $existingTimeByDate->user_id;
                    } else {
                        $targetUserId = $user->id;
                    }
                } else {
                    $targetUserId = $user->id;
                }
            }
        }
        
        return $targetUserId;
    }
    
    /**
     * 休憩時間データを準備
     */
    private function prepareBreakTimesData(Request $request)
    {
        return collect($request->input('breaktimes', []))
            ->map(function ($breaktime) {
                return [
                    'start_break_time' => $breaktime['start'] ?? null,
                    'end_break_time' => $breaktime['end'] ?? null,
                ];
            })
            ->filter(function ($breaktime) {
                return !empty($breaktime['start_break_time']) && !empty($breaktime['end_break_time']);
            })
            ->sortBy('start_break_time')
            ->values()
            ->all();
    }
    
    /**
     * 勤怠データをバリデーション
     */
    private function validateTimelogData($timeData, $breakTimesData, $isAdmin)
    {
        $errors = [];
        
        // noteがnullまたは空文字列の場合にエラー
        if ($timeData['note'] === null || trim($timeData['note']) === '') {
            $errors['note'] = '備考を記入してください';
        }
        
        if (!empty($timeData['arrival_time']) && !empty($timeData['departure_time'])) {
            $arrivalTime = Carbon::createFromFormat('H:i', $timeData['arrival_time']);
            $departureTime = Carbon::createFromFormat('H:i', $timeData['departure_time']);
            
            if ($arrivalTime->greaterThanOrEqualTo($departureTime)) {
                if ($isAdmin) {
                    $errors['arrival_time'] = '出勤時間もしくは退勤時間が不適切な値です';
                } else {
                    $errors['arrival_time'] = '出勤時間が不適切な値です';
                }
            }
        }
        
        if (!empty($timeData['departure_time'])) {
            $departureTime = Carbon::createFromFormat('H:i', $timeData['departure_time']);
            
            foreach ($breakTimesData as $breakTime) {
                if (!empty($breakTime['start_break_time'])) {
                    $startBreakTime = Carbon::createFromFormat('H:i', $breakTime['start_break_time']);
                    if ($startBreakTime->greaterThanOrEqualTo($departureTime)) {
                        $errors['breaktime'] = '休憩時間が不適切な値です';
                        break;
                    }
                }
                
                if (!empty($breakTime['end_break_time'])) {
                    $endBreakTime = Carbon::createFromFormat('H:i', $breakTime['end_break_time']);
                    if ($endBreakTime->greaterThanOrEqualTo($departureTime)) {
                        if ($isAdmin) {
                            $errors['breaktime'] = '休憩時間もしくは退勤時間が不適切な値です';
                        } else {
                            $errors['breaktime'] = '休憩時間もしくは退勤時間が不適切な値です';
                        }
                        break;
                    }
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * 既存の勤怠記録を更新
     */
    private function updateExistingTimelog($timeId, $targetUserId, $timeData, $breakTimesData, $date, $isAdmin, $fromApplicationDetail)
    {
        $timeRecord = Time::where('id', $timeId)
            ->where('user_id', $targetUserId)
            ->first();
        
        if (!$timeRecord) {
            return;
        }
        
        $existingApplication = Application::where('time_id', $timeRecord->id)->first();
        
        if ($isAdmin) {
            $this->updateTimelogForAdmin($timeRecord, $existingApplication, $timeData, $breakTimesData);
        } else {
            $this->updateTimelogForStaff($timeRecord, $existingApplication, $targetUserId, $date, $timeData, $breakTimesData, $fromApplicationDetail);
        }
    }
    
    /**
     * 管理者用の勤怠記録更新
     */
    private function updateTimelogForAdmin($timeRecord, $existingApplication, $timeData, $breakTimesData)
    {
        // 既存の申請がある場合は削除（管理者は直接更新するため）
        if ($existingApplication) {
            $existingApplication->breaktimes()->delete();
            $existingApplication->delete();
        }
        
        // Timesテーブルを直接更新
        $timeRecord->update($timeData);
        
        // 既存の休憩時間を削除して新規作成
        $timeRecord->breaktimes()->delete();
        foreach ($breakTimesData as $breakTimeData) {
            Breaktime::create(array_merge($breakTimeData, ['time_id' => $timeRecord->id]));
        }
    }
    
    /**
     * スタッフ用の勤怠記録更新
     */
    private function updateTimelogForStaff($timeRecord, $existingApplication, $targetUserId, $date, $timeData, $breakTimesData, $fromApplicationDetail)
    {
        if (!$fromApplicationDetail) {
            // 申請がない場合または承認済みの場合のみ、新規申請を作成
            if (!$existingApplication || $existingApplication->application_flg === Application::STATUS_APPROVED) {
                // 既存の申請が承認済みの場合は削除（新規作成するため）
                if ($existingApplication && $existingApplication->application_flg === Application::STATUS_APPROVED) {
                    $existingApplication->breaktimes()->delete();
                    $existingApplication->delete();
                    $existingApplication = null;
                }
                
                // 新規申請を作成
                $application = Application::create([
                    'user_id' => $targetUserId,
                    'time_id' => $timeRecord->id,
                    'date' => $date,
                    'arrival_time' => $timeData['arrival_time'] ?: '',
                    'departure_time' => $timeData['departure_time'] ?: '',
                    'note' => $timeData['note'] ?: null,
                    'application_flg' => Application::STATUS_PENDING,
                ]);
                
                // 申請の休憩時間も保存
                foreach ($breakTimesData as $breakTimeData) {
                    \App\Models\ApplicationBreaktime::create(array_merge($breakTimeData, ['application_id' => $application->id]));
                }
                
                // 申請を作成したので、$existingApplicationを更新
                $existingApplication = $application;
            } else {
                // 既存の申請が申請中（application_flg === 1）の場合、申請内容を更新
                // 既存の申請の休憩時間を削除
                $existingApplication->breaktimes()->delete();
                
                // 既存の申請を更新
                $existingApplication->update([
                    'arrival_time' => $timeData['arrival_time'] ?: '',
                    'departure_time' => $timeData['departure_time'] ?: '',
                    'note' => $timeData['note'] ?: null,
                    // application_flgは変更しない（申請中のまま）
                ]);
                
                // 申請の休憩時間を更新
                foreach ($breakTimesData as $breakTimeData) {
                    \App\Models\ApplicationBreaktime::create(array_merge($breakTimeData, ['application_id' => $existingApplication->id]));
                }
            }
        }
        
        // スタッフの場合：申請がない場合のみtimesテーブルを更新
        if (!$existingApplication) {
            $timeRecord->update($timeData);
            
            // 既存の休憩時間を削除して新規作成
            $timeRecord->breaktimes()->delete();
            foreach ($breakTimesData as $breakTimeData) {
                Breaktime::create(array_merge($breakTimeData, ['time_id' => $timeRecord->id]));
            }
        }
        // 申請がある場合（申請中）、timesテーブルは更新しない（承認まで待つ）
    }
    
    /**
     * 新しい勤怠記録を作成
     */
    private function createNewTimelog($targetUserId, $date, $timeData, $breakTimesData, $isAdmin, $fromApplicationDetail)
    {
        $timeCreateData = [
            'user_id' => $targetUserId,
            'date' => $date,
            'arrival_time' => $isAdmin ? ($timeData['arrival_time'] ?? '') : '',
            'departure_time' => $isAdmin ? ($timeData['departure_time'] ?? null) : null,
            'note' => $isAdmin ? ($timeData['note'] ?? null) : null,
        ];
        
        // Timesテーブルに基本レコードを作成
        $timeRecord = Time::create($timeCreateData);
        
        // 管理者の場合：直接更新、スタッフの場合：申請を作成
        if ($isAdmin) {
            // 管理者の場合：直接timesテーブルとbreaktimesテーブルに保存
            foreach ($breakTimesData as $breakTimeData) {
                Breaktime::create(array_merge($breakTimeData, ['time_id' => $timeRecord->id]));
            }
        } else {
            // スタッフの場合：申請を作成
            if (!$fromApplicationDetail) {
                // 新規申請を作成
                $application = Application::create([
                    'user_id' => $targetUserId,
                    'time_id' => $timeRecord->id,
                    'date' => $date,
                    'arrival_time' => $timeData['arrival_time'] ?: '',
                    'departure_time' => $timeData['departure_time'] ?: '',
                    'note' => $timeData['note'] ?: null,
                    'application_flg' => Application::STATUS_PENDING,
                ]);
                
                // 申請の休憩時間も保存
                foreach ($breakTimesData as $breakTimeData) {
                    \App\Models\ApplicationBreaktime::create(array_merge($breakTimeData, ['application_id' => $application->id]));
                }
            } else {
                // 申請詳細画面から来た場合は直接breaktimesテーブルに保存
                foreach ($breakTimesData as $breakTimeData) {
                    Breaktime::create(array_merge($breakTimeData, ['time_id' => $timeRecord->id]));
                }
            }
        }
    }
    
    /**
     * 休憩時間レコードを作成
     */
    private function createBreaktimesForRecord($timeRecord, $breakTimesData)
    {
        foreach ($breakTimesData as $breakTimeData) {
            Breaktime::create(array_merge($breakTimeData, ['time_id' => $timeRecord->id]));
        }
    }
    
    /**
     * 申請の休憩時間レコードを作成
     */
    private function createApplicationBreaktimes($application, $breakTimesData)
    {
        foreach ($breakTimesData as $breakTimeData) {
            \App\Models\ApplicationBreaktime::create(array_merge($breakTimeData, ['application_id' => $application->id]));
        }
    }
    
    /**
     * 更新後のリダイレクト処理
     */
    private function redirectAfterUpdate($date, $userId, $targetUserId, $isAdmin, $message)
    {
        $dateCarbon = Carbon::createFromFormat('Y-m-d', $date);
        
        if ($isAdmin) {
            // 管理者の場合は勤怠詳細画面にリダイレクト
            $redirectParams = [
                'id' => $targetUserId,
                'year' => $dateCarbon->year,
                'month' => $dateCarbon->month,
                'day' => $dateCarbon->day,
            ];
            
            return redirect()->route('admin.attendance.detail', $redirectParams)->with('success', $message);
        } else {
            $redirectParams = [
                'year' => $dateCarbon->year,
                'month' => $dateCarbon->month,
                'day' => $dateCarbon->day,
            ];
            
            return redirect()->route('timelog.detail', $redirectParams)->with('success', $message);
        }
    }
}

