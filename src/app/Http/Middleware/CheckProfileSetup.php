<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckProfileSetup
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // 認証済みユーザーのみチェック
        if (auth()->check()) {
            $user = auth()->user();
            
            // 住所が未入力または登録未完了の場合はプロフィール設定画面へ
            if (empty($user->postcode) || empty($user->address) || !$user->registeredflg) {
                // プロフィール設定画面以外の場合はリダイレクト
                if (!$request->routeIs('profile.*')) {
                    return redirect()->route('profile.setup')
                        ->with('info', 'プロフィール設定を完了してください。');
                }
            }
        }
        
        return $next($request);
    }
}
