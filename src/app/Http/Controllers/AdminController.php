<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Time;
use App\Models\Actor;

class AdminController extends Controller
{
    public function index()
    {
        return view('auth.admin_login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ], [
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => '有効なメールアドレスを入力してください。',
            'password.required' => 'パスワードを入力してください',
            'password.min' => 'パスワードは6文字以上で入力してください。',
        ]);

        $credentials = $request->only(['email', 'password']);

        if (Auth::attempt($credentials)) {
            // 管理者でない場合はログインを拒否
            if (Auth::user()->actor_id !== Actor::ADMIN_ID) {
                Auth::logout();
                return back()->withErrors([
                    'login' => '管理者アカウントでログインしてください。',
                ]);
            }
            
            $request->session()->regenerate();
            return redirect('/admin/attendance/list');
        }

        return back()->withErrors([
            'login' => 'ログイン情報が登録されていません',
        ]);
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/admin/login');
    }

    /**
     * スタッフ一覧画面を表示（管理者用）
     */
    public function staffList()
    {
        $staffs = User::where('registeredflg', true)
            ->where('actor_id', '!=', Actor::ADMIN_ID)
            ->orderBy('name')
            ->get();

        return view('admin.staff_list', compact('staffs'));
    }
}

