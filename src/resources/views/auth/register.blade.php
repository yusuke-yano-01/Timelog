@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')
<div class="auth-container">
  <div class="auth-form">
    <h2 class="auth-title">会員登録</h2>
    <form class="form" action="/auth/register" method="post">
      @csrf
      <div class="form-group">
        <label class="form-label">名前</label>
        <input type="text" name="name" value="{{ old('name') }}" class="form-input" placeholder="例: 山田 太郎"/>
        @error('name')
          <div class="form-error">{{ $message }}</div>
        @enderror
      </div>
      
      <div class="form-group">
        <label class="form-label">メールアドレス</label>
        <input type="email" name="email" value="{{ old('email') }}" class="form-input @error('email') is-invalid @enderror" placeholder="例: test@example.com"/>
        @error('email')
          <div class="form-error">{{ $message }}</div>
        @enderror
      </div>
      
      <div class="form-group">
        <label class="form-label">パスワード</label>
        <input type="password" name="password" class="form-input" placeholder="例: coachtechno6"/>
        @error('password')
          <div class="form-error">{{ $message }}</div>
        @enderror
      </div>
      
      <div class="form-group">
        <label class="form-label">パスワード確認</label>
        <input type="password" name="password_confirmation" class="form-input" placeholder="例: coachtechno6"/>
      </div>
      
      <button class="btn-submit" type="submit">登録する</button>
      
      @if ($errors->any())
        <div class="error-message">
          @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
          @endforeach
        </div>
      @endif
    </form>
    
    <div class="auth-link">
      <a href="/auth/login" class="link-login">ログインはこちら</a>
    </div>
  </div>
</div>
@endsection