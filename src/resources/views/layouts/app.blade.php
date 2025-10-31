<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>TimeLog</title>
  <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
  <link rel="stylesheet" href="{{ asset('css/common.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  @yield('css')
</head>

<body class="@if(request()->is('auth/*')) auth-page @endif">
  <header class="header">
    <div class="header__inner">
      <div class="header-utilities">
        <a class="header__logo" href="/">
          <img src="{{ asset('assets/images/logo.svg') }}" alt="CT COACHTECH" class="header__logo-image">
        </a>
        <nav class="header-nav">
            @if (Auth::check())
              <div class="header-nav__user">
                @php
                  $user = Auth::user();
                  $isAdmin = $user->actor_id === 1; // 管理者のactor_idは1
                @endphp
                
                @if($isAdmin)
                  {{-- 管理者用ナビゲーション --}}
                  <a class="header-nav__link" href="/admin/attendance/list">勤怠一覧</a>
                  <a class="header-nav__link" href="/admin/staff/list">スタッフ一覧</a>
                  <a class="header-nav__link" href="/stamp_correction_request/list">申請一覧</a>
                  <form action="/admin/logout" method="post" class="header-nav__logout">
                    @csrf
                    <button class="header-nav__link header-nav__logout-btn">ログアウト</button>
                  </form>
                @else
                  {{-- スタッフ用ナビゲーション --}}
                  <a class="header-nav__link" href="/">勤怠</a>
                  <a class="header-nav__link" href="/attendance/history">勤怠一覧</a>
                  <a class="header-nav__link" href="/timelog/application">申請</a>
                  <form action="/auth/logout" method="post" class="header-nav__logout">
                    @csrf
                    <button class="header-nav__link header-nav__logout-btn">ログアウト</button>
                  </form>
                @endif
              </div>
            @elseif (request()->path() === 'auth/login')
              <a class="header-nav__link" href="/auth/register">register</a>
            @elseif (request()->path() === 'auth/register')
              <a class="header-nav__link" href="/auth/login">login</a>
            @else
              <a class="header-nav__link" href="/auth/login">login</a>
              <a class="header-nav__link" href="/auth/register">register</a>
            @endif
        </nav>
      </div>
    </div>
  </header>

  <main>
    @yield('content')
  </main>
  
  @yield('js')
</body>

</html>