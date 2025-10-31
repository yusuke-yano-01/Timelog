@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_attendance_list.css') }}">
@endsection

@section('content')
<div class="admin-attendance-container">
    <div class="admin-attendance-header">
        <div class="header-bar"></div>
        <h1 class="admin-attendance-title">{{ $targetDate->format('Y年n月j日') }}の勤怠</h1>
    </div>
    
    <div class="date-navigation">
        <a href="{{ route('admin.attendance.list', ['date' => $prevDate->format('Y-m-d')]) }}" class="nav-link prev-day">
            <span class="nav-arrow">←</span>
            <span class="nav-text">前日</span>
        </a>
        
        <div class="current-date">
            <span class="calendar-icon">📅</span>
            <span class="date-display">{{ $targetDate->format('Y/m/d') }}</span>
        </div>
        
        <a href="{{ route('admin.attendance.list', ['date' => $nextDate->format('Y-m-d')]) }}" class="nav-link next-day">
            <span class="nav-text">翌日</span>
            <span class="nav-arrow">→</span>
        </a>
    </div>
    
    <div class="attendance-table-container">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th class="name-column">名前</th>
                    <th class="time-column">出勤</th>
                    <th class="time-column">退勤</th>
                    <th class="time-column">休憩</th>
                    <th class="time-column">合計</th>
                    <th class="action-column">詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendanceRecords as $record)
                <tr class="attendance-row">
                    <td class="name-cell">{{ $record['staff']->name }}</td>
                    <td class="time-cell">
                        {{ $record['attendance']->arrival_time ?? '-' }}
                    </td>
                    <td class="time-cell">
                        {{ $record['attendance']->departure_time ?? '-' }}
                    </td>
                    <td class="time-cell">
                        {{ $record['break_time'] ?? '-' }}
                    </td>
                    <td class="time-cell">
                        {{ $record['total_time'] ?? '-' }}
                    </td>
                    <td class="action-cell">
                        <a href="{{ route('admin.attendance.detail', [
                            'id' => $record['staff']->id,
                            'year' => $targetDate->year,
                            'month' => $targetDate->month,
                            'day' => $targetDate->day
                        ]) }}" class="detail-link">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

