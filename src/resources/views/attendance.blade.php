@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}?v={{ time() }}">
@endsection

@section('content')
<div class="attendance-container">
    <div class="status-label">
        @if(!$todayAttendance)
            勤務外
        @elseif($user->break_flg)
            休憩中
        @elseif(!$todayAttendance->departure_time)
            勤務中
        @else
            退勤済
        @endif
    </div>
    
    <div class="date-info">
        <h2>{{ $today->format('Y年m月d日') }}({{ $weekday }})</h2>
        <p class="current-time" id="current-time"></p>
    </div>
    
    @if(session('success'))
        <div class="success-message">
            {{ session('success') }}
        </div>
    @endif
    
    @if($errors->any())
        <div class="error-message">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
    
    <div class="attendance-button">
        @if(!$todayAttendance)
            <form action="/attendance/clock-in" method="post">
                @csrf
                <button type="submit" class="btn-clock-in">出勤</button>
            </form>
        @elseif($user->break_flg)
            <form action="/attendance/end-break" method="post">
                @csrf
                <button type="submit" class="btn-break-end">休憩戻</button>
            </form>
        @elseif(!$todayAttendance->departure_time)
            <div class="button-group">
                <form action="/attendance/clock-out" method="post" class="clock-out-form">
                    @csrf
                    <button type="submit" class="btn-clock-out">退勤</button>
                </form>
                <form action="/attendance/start-break" method="post" class="break-form">
                    @csrf
                    <button type="submit" class="btn-break-start">休憩入</button>
                </form>
            </div>
        @else
            <div class="work-complete-message">
                お疲れ様でした。
            </div>
        @endif
    </div>
</div>

@endsection

@section('js')
<script src="{{ asset('js/attendance.js') }}"></script>
@endsection
