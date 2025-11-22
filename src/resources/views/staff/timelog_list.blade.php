@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/timelog_list.css') }}">
@endsection

@section('content')
<div class="timelog-container">
    <div class="timelog-header">
        <div class="header-bar"></div>
        <h1 class="timelog-title">
            @if(isset($targetUserId) && isset($targetUser))
                {{ $targetUser->name }}さんの勤怠
            @else
                勤怠一覧
            @endif
        </h1>
    </div>
    
    <div class="month-navigation">
        <a href="{{ route('timelog.list', array_merge(['year' => $prevMonth->year, 'month' => $prevMonth->month], isset($targetUserId) ? ['user_id' => $targetUserId] : [])) }}" class="nav-link prev-month">
            <span class="nav-arrow">←</span>
            <span class="nav-text">前月</span>
        </a>
        
        <div class="current-month">
            <span class="calendar-icon">📅</span>
            <span class="month-year">{{ $currentMonth->year }}/{{ sprintf('%02d', $currentMonth->month) }}</span>
        </div>
        
        <a href="{{ route('timelog.list', array_merge(['year' => $nextMonth->year, 'month' => $nextMonth->month], isset($targetUserId) ? ['user_id' => $targetUserId] : [])) }}" class="nav-link next-month">
            <span class="nav-text">翌月</span>
            <span class="nav-arrow">→</span>
        </a>
    </div>
    
    <div class="timelog-table-container">
        <table class="timelog-table">
            <thead>
                <tr>
                    <th class="date-column">日付</th>
                    <th class="time-column">出勤</th>
                    <th class="time-column">退勤</th>
                    <th class="time-column">休憩</th>
                    <th class="time-column">合計</th>
                    <th class="action-column">詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendanceData as $day => $data)
                <tr class="timelog-row">
                    <td class="date-cell">
                        {{ sprintf('%02d', $currentMonth->month) }}/{{ sprintf('%02d', $day) }}({{ $data['dayOfWeek'] }})
                    </td>
                    <td class="time-cell">
                        {{ $data['arrival_time'] ?? '-' }}
                    </td>
                    <td class="time-cell">
                        {{ $data['departure_time'] ?? '-' }}
                    </td>
                    <td class="time-cell">
                        {{ $data['break_time'] ?? '-' }}
                    </td>
                    <td class="time-cell">
                        {{ $data['total_time'] ?? '-' }}
                    </td>
                    <td class="action-cell">
                        <a href="{{ route('timelog.detail', array_merge(['year' => $currentMonth->year, 'month' => $currentMonth->month, 'day' => $day], isset($targetUserId) ? ['user_id' => $targetUserId] : [])) }}" class="detail-link">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    @if(isset($targetUserId) && isset($targetUser) && Auth::check() && Auth::user()->actor_id === 1)
    <div class="csv-download-container">
        <a href="{{ route('timelog.csv', array_merge(['user_id' => $targetUserId, 'year' => $currentMonth->year, 'month' => $currentMonth->month])) }}" class="btn-csv-download">CSV出力</a>
    </div>
    @endif
</div>
@endsection
