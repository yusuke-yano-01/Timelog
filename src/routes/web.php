<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;

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
        return redirect()->action([AttendanceController::class, 'index']);
    }
    return redirect('/auth/login');
});

Route::get('/attendance', [AttendanceController::class, 'index'])->middleware('auth');
Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->middleware('auth');
Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->middleware('auth');
Route::post('/attendance/start-break', [AttendanceController::class, 'startBreak'])->middleware('auth');
Route::post('/attendance/end-break', [AttendanceController::class, 'endBreak'])->middleware('auth');

// 認証関連のルート
Route::group(['prefix' => 'auth'], function() {
    Route::get('login', [AuthController::class, 'index']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('register', [AuthController::class, 'registerForm']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('logout', [AuthController::class, 'logout']);
});

// 管理者関連のルート
Route::group(['prefix' => 'admin'], function() {
    Route::get('login', [AdminController::class, 'index']);
    Route::post('login', [AdminController::class, 'login']);
    Route::post('logout', [AdminController::class, 'logout']);
});