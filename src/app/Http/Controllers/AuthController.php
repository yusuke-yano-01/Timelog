<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Actor;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;

class AuthController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only(['email', 'password']);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // メール認証が完了していない場合は認証画面へ
            if (!Auth::user()->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }
            
            return redirect('/');
        }

        return back()->withErrors([
            'login' => 'ログイン情報が登録されていません',
        ]);
    }

    public function registerForm()
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request)
    {
        // RegisterRequestのバリデーションが自動的に実行される
        // バリデーションに失敗した場合は、自動的にback()でリダイレクトされ、エラーメッセージがセッションに保存される
        
        // actorsテーブルにデータが存在しない場合は作成
        Actor::firstOrCreate(['id' => 1], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => 2], ['name' => '従業員']);
        
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'actor_id' => 2, // 従業員のactor_id
                'registeredflg' => true,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // データベースレベルのエラー（例: ユニーク制約違反）が発生した場合
            if ($e->getCode() == 23000) {
                return back()->withErrors([
                    'email' => 'このメールアドレスは既に使用されています。',
                ])->withInput();
            }
            throw $e;
        }
        
        // メール認証通知を送信
        event(new Registered($user));
        
        // 登録後、自動ログイン
        Auth::login($user);
        
        // メール認証画面にリダイレクト
        return redirect()->route('verification.notice');
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/auth/login');
    }
}
