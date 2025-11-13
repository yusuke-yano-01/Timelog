<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class EmailVerificationController extends Controller
{
    /**
     * メール認証通知画面を表示
     */
    public function show()
    {
        return view('auth.verify-email');
    }

    /**
     * メール認証を処理
     */
    public function verify(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect('/')->with('status', 'メールアドレスは既に認証済みです。');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect('/')->with('status', 'メールアドレスの認証が完了しました。');
    }

    /**
     * メール認証通知を再送信
     */
    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect('/')->with('status', 'メールアドレスは既に認証済みです。');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', '認証メールを再送信しました。');
    }
}

