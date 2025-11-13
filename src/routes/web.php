<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\TimelogController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    if (Auth::check()) {
        // 管理者の場合は当日勤怠一覧画面へ
        if (Auth::user()->actor_id === 1) {
            return redirect('/admin/attendance/list');
        }
        // スタッフの場合は出勤・退勤画面へ
        return redirect()->action([AttendanceController::class, 'index']);
    }
    return redirect('/auth/login');
});

Route::get('/attendance', [AttendanceController::class, 'index'])->middleware('auth');
Route::get('/attendance/history', [TimelogController::class, 'index'])->middleware('auth')->name('attendance.history');
Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->middleware('auth');
Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->middleware('auth');
Route::post('/attendance/start-break', [AttendanceController::class, 'startBreak'])->middleware('auth');
Route::post('/attendance/end-break', [AttendanceController::class, 'endBreak'])->middleware('auth');

// 勤怠一覧関連のルート
Route::get('/timelog/list', [TimelogController::class, 'index'])->middleware('auth')->name('timelog.list');
Route::get('/timelog/detail', [TimelogController::class, 'detail'])->middleware('auth')->name('timelog.detail');
Route::match(['GET', 'POST'], '/timelog/update', [TimelogController::class, 'update'])->middleware('auth')->name('timelog.update');
Route::post('/timelog/approve', [TimelogController::class, 'approve'])->middleware('auth')->name('timelog.approve');
Route::get('/timelog/application', [TimelogController::class, 'application'])->middleware('auth')->name('timelog.application');
Route::get('/timelog/csv', [TimelogController::class, 'downloadCsv'])->middleware('auth')->name('timelog.csv');

// 認証関連のルート
Route::group(['prefix' => 'auth'], function() {
    Route::get('login', [AuthController::class, 'index']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('register', [AuthController::class, 'registerForm']);
    Route::post('register', [AuthController::class, 'register']);
    Route::match(['GET', 'POST'], 'logout', [AuthController::class, 'logout']);
});

// 管理者関連のルート
Route::group(['prefix' => 'admin'], function() {
    Route::get('login', [AdminController::class, 'index']);
    Route::post('login', [AdminController::class, 'login']);
    Route::match(['GET', 'POST'], 'logout', [AdminController::class, 'logout']);
    Route::get('attendance/list', [AdminController::class, 'attendanceList'])->middleware('auth')->name('admin.attendance.list');
    Route::get('attendance/{id}', [AdminController::class, 'showAttendanceDetail'])->middleware('auth')->name('admin.attendance.detail');
    Route::get('staff/list', [AdminController::class, 'staffList'])->middleware('auth')->name('admin.staff.list');
});

// 申請一覧（管理者用）
Route::get('/stamp_correction_request/list', [AdminController::class, 'applicationList'])->middleware('auth')->name('admin.application.list');
Route::get('/admin/application/detail/{id}', [AdminController::class, 'applicationDetail'])->middleware('auth')->name('admin.application.detail');