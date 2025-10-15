@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')
<div class="auth-container">
  <div class="auth-form">
    <h2 class="auth-title">管理者ログイン</h2>
    <form class="form" action="/admin/login" method="post">
      @csrf
      <div class="form-group">
        <label class="form-label">メールアドレス</label>
        <input type="email" name="email" value="{{ old('email') }}" class="form-input" placeholder="例: admin@example.com" />
        @error('email')
          <div class="form-error">{{ $message }}</div>
        @enderror
      </div>
      
      <div class="form-group">
        <label class="form-label">パスワード</label>
        <input type="password" name="password" class="form-input" placeholder="例: admin123"/>
        @error('password')
          <div class="form-error">{{ $message }}</div>
        @enderror
      </div>
      
      <button class="btn-submit" type="submit">ログイン</button>
      
      @if ($errors->has('login'))
        <div class="error-message">{{ $errors->first('login') }}</div>
      @endif
    </form>
  </div>
</div>
@endsection
