@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')
<div class="auth-container">
  <div class="auth-form auth-form-verify">
    @if (session('status'))
      <div class="success-message">
        {{ session('status') }}
      </div>
    @endif

    <div class="verify-message">
      <p>登録していただいたメールアドレスに認証メールを送付しました。</p>
      <p>メール認証を完了してください。</p>
    </div>

    @if (!Auth::user()->hasVerifiedEmail())
      <form class="form" action="{{ route('verification.send') }}" method="post">
        @csrf
        <button class="btn-resend" type="submit">認証メールを再送する</button>
      </form>
    @endif
  </div>
</div>
@endsection

