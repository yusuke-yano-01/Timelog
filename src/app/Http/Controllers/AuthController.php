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
use App\Constants\DatabaseConstants;

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
        Actor::firstOrCreate(['id' => Actor::ADMIN_ID], ['name' => '管理者']);
        Actor::firstOrCreate(['id' => Actor::STAFF_ID], ['name' => '従業員']);
        
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'actor_id' => Actor::STAFF_ID,
                'registeredflg' => true,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == DatabaseConstants::SQL_ERROR_UNIQUE_CONSTRAINT) {
                return back()->withErrors([
                    'email' => 'このメールアドレスは既に使用されています。',
                ])->withInput();
            }
            throw $e;
        }
        
        event(new Registered($user));
        Auth::login($user);
        
        return redirect()->route('verification.notice');
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/auth/login');
    }
}
