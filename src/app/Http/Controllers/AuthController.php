<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsersRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    public function login(UsersRequest $request)
    {
        $credentials = $request->only(['email', 'password']);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect('/productlist');
        }

        return back()->withErrors([
            'login' => 'メールアドレスまたはパスワードが正しくありません。',
        ]);
    }

    public function registerForm()
    {
        return view('auth.register');
    }

    public function register(UsersRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        
        // 登録後、自動ログインしてプロフィール設定画面へ
        Auth::login($user);
        
        return redirect()->route('profile.setup');
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/auth/login');
    }
}
