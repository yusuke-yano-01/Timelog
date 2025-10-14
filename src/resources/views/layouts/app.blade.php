<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>FashionablyLate</title>
  <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
  <link rel="stylesheet" href="{{ asset('css/common.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  @yield('css')
</head>

<body>
  <header class="header">
    <div class="header__inner">
      <div class="header-utilities">
        <a class="header__logo" href="/">
          <img src="{{ asset('assets/images/logo.svg') }}" alt="フリマアプリ" class="header__logo-image">
        </a>
        <nav class="header-nav">
            @if (Auth::check())
              <div class="header-nav__user">
                <a class="header-nav__link" href="/mypage">マイページ</a>
                <form action="/auth/logout" method="post" class="header-nav__logout">
                  @csrf
                  <button class="header-nav__link header-nav__logout-btn">ログアウト</button>
                </form>
                <a class="header-nav__link header-nav__product-btn" href="/productform">出品</a>
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
</body>

</html>