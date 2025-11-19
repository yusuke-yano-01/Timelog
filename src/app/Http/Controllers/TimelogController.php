<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Time;
use App\Models\User;
use App\Models\Breaktime;
use App\Models\Application;

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
        // dateカラムで月の範囲をフィルタリング、休憩時間も一緒に取得
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
        $isAdmin = $user->actor_id === 1; // 管理者のactor_idは1
        
        $year = $request->get('year');
        $month = $request->get('month');
        $day = $request->get('day');
        $userId = $request->get('user_id'); // 管理者が他のユーザーを見る場合のパラメータ
        
        // user_idが指定されている場合はそのユーザー、そうでなければ現在のユーザー
        // 管理者がuser_idを指定しない場合は、スタッフが自分のデータを見る場合と同じ扱い
        $targetUserId = $userId ?? $user->id;
        
        $date = Carbon::create($year, $month, $day);
        
        // 指定された日の勤怠データを取得（休憩時間も一緒に取得）
        // 必ずtargetUserIdで検索する（全ユーザーで検索しない）
        $attendanceRecord = Time::where('user_id', $targetUserId)
            ->where('date', $date->format('Y-m-d'))
            ->with('breaktimes')
            ->first();
        
        // 申請情報を取得
        // time_idがある場合はtime_idで検索、ない場合はuser_idとdateで検索
        $application = null;
        if ($attendanceRecord) {
            $application = Application::where('time_id', $attendanceRecord->id)
                ->with('breaktimes')
                ->first();
        } else {
            // $attendanceRecordがない場合でも、申請が存在する可能性がある（新規作成で申請のみ作成された場合）
            $application = Application::where('user_id', $targetUserId)
                ->where('date', $date->format('Y-m-d'))
                ->with('breaktimes')
                ->first();
        }
        
        // 対象ユーザーを取得
        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            $targetUser = $user; // フォールバック
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
     * 勤怠詳細を更新
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->actor_id === 1;
        $timeId = $request->get('time_id');
        $date = $request->get('date');
        $userId = $request->get('user_id'); // 管理者が他のユーザーのデータを編集する場合
        
        // 対象ユーザーIDを決定（管理者が編集する場合でも、対象スタッフのIDを使用）
        // 優先順位：
        // 1. 既存のTimeレコードのuser_id（最も確実）
        // 2. リクエストのuser_idパラメータ（管理者がスタッフを編集する場合）
        // 3. 現在のユーザーID（スタッフが自分のデータを編集する場合）
        $targetUserId = null;
        if ($timeId) {
            $existingTime = Time::find($timeId);
            if ($existingTime) {
                $targetUserId = $existingTime->user_id; // 既存レコードのuser_idを最優先
            }
        }
        // 既存レコードがない、または見つからない場合
        if (!$targetUserId) {
            if ($userId) {
                $targetUserId = $userId; // 管理者がスタッフを編集する場合
            } else {
                // スタッフが自分のデータを編集する場合、または新規作成する場合
                if ($isAdmin) {
                    // 管理者が新規作成する場合、dateから既存レコードを探す（管理者以外のレコード）
                    $existingTimeByDate = Time::where('date', $date)
                        ->where('user_id', '!=', $user->id) // 管理者以外
                        ->first();
                    if ($existingTimeByDate) {
                        $targetUserId = $existingTimeByDate->user_id;
                    } else {
                        // 見つからない場合でも、user_idパラメータがあればそれを使用
                        // user_idパラメータがない場合は、管理者が自分のデータを作成しようとしている可能性
                        // その場合は管理者のIDを使用（ただし通常は発生しない）
                        $targetUserId = $user->id;
                    }
                } else {
                    $targetUserId = $user->id; // スタッフが自分のデータを編集
                }
            }
        }
        
        // スタッフが自分のデータ以外を編集しようとした場合はエラー
        if (!$isAdmin && $targetUserId !== $user->id) {
            abort(403, 'Unauthorized access.');
        }
        
        // Timesテーブルの基本情報
        $timeData = [
            'arrival_time' => $request->get('arrival_time'),
            'departure_time' => $request->get('departure_time'),
            'note' => $request->get('note'),
        ];
        
        // バリデーション
        $errors = [];
        
        // 備考欄が未入力の場合
        if (empty($timeData['note'])) {
            $errors['note'] = '備考を記入してください';
        }
        
        // 出勤時間と退勤時間のバリデーション
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
        
        // 休憩時間のバリデーション
        $breakTimesData = collect($request->input('breaktimes', []))
            ->map(function ($breaktime) {
                return [
                    'start_break_time' => $breaktime['start'] ?? null,
                    'end_break_time1' => $breaktime['end'] ?? null,
                ];
            })
            ->filter(function ($breaktime) {
                return !empty($breaktime['start_break_time']) && !empty($breaktime['end_break_time1']);
            })
            ->sortBy('start_break_time')
            ->values()
            ->all();
        
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
                
                if (!empty($breakTime['end_break_time1'])) {
                    $endBreakTime = Carbon::createFromFormat('H:i', $breakTime['end_break_time1']);
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
        
        // バリデーションエラーがある場合はリダイレクト
        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }
        
        
        // 空の値をnullに変換
        foreach ($timeData as $key => $value) {
            if (empty($value)) {
                $timeData[$key] = null;
            }
        }
        
        // 申請詳細画面から修正した場合は管理者のみ編集可能で状態変更なし（承認機能のみ）
        $fromApplicationDetail = $request->get('from_application_detail');
        
        if ($timeId) {
            // 既存のレコードを更新
            $timeRecord = Time::where('id', $timeId)
                ->where('user_id', $targetUserId)
                ->first();
            
            if ($timeRecord) {
                // 既存の申請を確認
                $existingApplication = Application::where('time_id', $timeRecord->id)->first();
                
                // 管理者の場合は申請を作成せず、直接timesテーブルとbreaktimesテーブルを更新
                if ($isAdmin) {
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
                } else {
                    // スタッフの場合は申請を作成・更新（承認が必要）
                    if (!$fromApplicationDetail) {
                        // 申請がない場合または承認済みの場合のみ、新規申請を作成
                        if (!$existingApplication || $existingApplication->application_flg === 0) {
                            // 既存の申請が承認済みの場合は削除（新規作成するため）
                            if ($existingApplication && $existingApplication->application_flg === 0) {
                                $existingApplication->breaktimes()->delete();
                                $existingApplication->delete();
                            }
                            
                            // 新規申請を作成
                            $application = Application::create([
                                'user_id' => $targetUserId,
                                'time_id' => $timeRecord->id,
                                'date' => $date,
                                'arrival_time' => $timeData['arrival_time'] ?? '',
                                'departure_time' => $timeData['departure_time'] ?? '',
                                'note' => $timeData['note'] ?? null,
                                'application_flg' => 1, // 申請中
                            ]);
                            
                            // 申請の休憩時間も保存
                            foreach ($breakTimesData as $breakTimeData) {
                                \App\Models\ApplicationBreaktime::create(array_merge($breakTimeData, ['application_id' => $application->id]));
                            }
                        } else {
                            // 既存の申請が申請中（application_flg === 1）の場合、申請内容を更新
                            // 既存の申請の休憩時間を削除
                            $existingApplication->breaktimes()->delete();
                            
                            // 既存の申請を更新
                            $existingApplication->update([
                                'arrival_time' => $timeData['arrival_time'] ?? '',
                                'departure_time' => $timeData['departure_time'] ?? '',
                                'note' => $timeData['note'] ?? null,
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
            }
        } else {
            // 新しいレコードを作成
            $timeCreateData = [
                'user_id' => $targetUserId,
                'date' => $date,
                'arrival_time' => $isAdmin ? ($timeData['arrival_time'] ?? '') : '',
                'departure_time' => $isAdmin ? ($timeData['departure_time'] ?? null) : null,
                'note' => $isAdmin ? ($timeData['note'] ?? null) : null,
            ];
            
            // Timesテーブルに基本レコードを作成
            $timeRecord = Time::create($timeCreateData);
            
            // 管理者の場合は直接更新、スタッフの場合は申請を作成
            if ($isAdmin) {
                // 管理者の場合は直接timesテーブルとbreaktimesテーブルに保存
                foreach ($breakTimesData as $breakTimeData) {
                    Breaktime::create(array_merge($breakTimeData, ['time_id' => $timeRecord->id]));
                }
            } else {
                // スタッフの場合は申請を作成
                if (!$fromApplicationDetail) {
                    // 新規申請を作成
                    $application = Application::create([
                        'user_id' => $targetUserId,
                        'time_id' => $timeRecord->id,
                        'date' => $date,
                        'arrival_time' => $timeData['arrival_time'] ?? '',
                        'departure_time' => $timeData['departure_time'] ?? '',
                        'note' => $timeData['note'] ?? null,
                        'application_flg' => 1, // 申請中
                    ]);
                    
                    // 申請の休憩時間も保存
                    foreach ($breakTimesData as $breakTimeData) {
                        \App\Models\ApplicationBreaktime::create(array_merge($breakTimeData, ['application_id' => $application->id]));
                    }
                    
                    // スタッフの場合、申請がある場合はtimesテーブルの休憩時間は保存しない（承認まで更新しない）
                } else {
                    // 申請詳細画面からの作成の場合はtimesに保存
                    foreach ($breakTimesData as $breakTimeData) {
                        Breaktime::create(array_merge($breakTimeData, ['time_id' => $timeRecord->id]));
                    }
                }
            }
        }
        
        $message = $isAdmin ? '勤怠情報を更新しました。' : '申請を送信しました。';
        
        // 管理者の場合は月ごとの勤怠一覧にリダイレクト、スタッフの場合は詳細画面にリダイレクト
        if ($isAdmin) {
            $redirectParams = [
                'year' => Carbon::createFromFormat('Y-m-d', $date)->year,
                'month' => Carbon::createFromFormat('Y-m-d', $date)->month,
            ];
            
            if ($userId) {
                $redirectParams['user_id'] = $userId;
            } else {
                // userIdがない場合はtargetUserIdを使用
                $redirectParams['user_id'] = $targetUserId;
            }
            
            return redirect()->route('timelog.list', $redirectParams)->with('success', $message);
        } else {
            $redirectParams = [
                'year' => Carbon::createFromFormat('Y-m-d', $date)->year,
                'month' => Carbon::createFromFormat('Y-m-d', $date)->month,
                'day' => Carbon::createFromFormat('Y-m-d', $date)->day,
            ];
            
            return redirect()->route('timelog.detail', $redirectParams)->with('success', $message);
        }
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
        
        $applicationId = $request->get('application_id');
        $date = $request->get('date');
        $userId = $request->get('user_id');
        
        $application = Application::find($applicationId);
        
        if (!$application) {
            return back()->withErrors(['error' => '申請が見つかりません。']);
        }
        
        // 承認待ちの状態のみ承認可能
        if ($application->application_flg !== 1) {
            return back()->withErrors(['error' => 'この申請は既に承認済みです。']);
        }
        
        // 承認：application_flgを0に設定し、TimesテーブルとBreaktimesテーブルを更新
        $application->update(['application_flg' => 0]);
        
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
                    'end_break_time1' => $appBreaktime->end_break_time1,
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
            $query->where('application_flg', 1); // 申請中
        } else {
            $query->where('application_flg', 0); // 承認済み
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
        if ($currentUser->actor_id !== 1) {
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
