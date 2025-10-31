@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/timelog_detail.css') }}">
@endsection

@section('content')
<div class="timelog-detail-container">
    <div class="timelog-detail-header">
        <h1 class="timelog-detail-title">勤怠詳細</h1>
    </div>
    
    <div class="timelog-detail-form">
        <form action="{{ route('timelog.update') }}" method="post">
            @csrf
            <input type="hidden" name="time_id" value="{{ $attendanceRecord->id ?? '' }}">
            <input type="hidden" name="date" value="{{ $date->format('Y-m-d') }}">
            
            <div class="form-row">
                <div class="form-label">名前</div>
                <div class="form-value">{{ $targetUser->name ?? Auth::user()->name }}</div>
            </div>
            
            <div class="form-row">
                <div class="form-label">日付</div>
                <div class="form-value">
                    <span class="year">{{ $date->format('Y年') }}</span>
                    <span class="month-day">{{ $date->format('n月j日') }}</span>
                </div>
            </div>
            
            @php
                $isPending = $attendanceRecord && $attendanceRecord->application_flg;
                $isApproved = $attendanceRecord && !$attendanceRecord->application_flg;
                $hasData = $attendanceRecord !== null;
                $canEditStaff = !$isPending; // スタッフは承認待ちでない場合のみ編集可能
                $canEditAdmin = true; // 管理者は常に編集可能
                $canEdit = $isAdmin ?? false ? $canEditAdmin : $canEditStaff;
            @endphp
            
            <div class="form-row">
                <div class="form-label">出勤・退勤</div>
                <div class="form-input-group">
                    <input type="time" name="arrival_time" value="{{ $attendanceRecord->arrival_time ?? '' }}" class="time-input" {{ $canEdit ? '' : 'disabled' }}>
                    <span class="separator">~</span>
                    <input type="time" name="departure_time" value="{{ $attendanceRecord->departure_time ?? '' }}" class="time-input" {{ $canEdit ? '' : 'disabled' }}>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-label">休憩</div>
                <div class="form-input-group">
                    <input type="time" name="start_break_time1" value="{{ $attendanceRecord->start_break_time1 ?? '' }}" class="time-input" {{ $canEdit ? '' : 'disabled' }}>
                    <span class="separator">~</span>
                    <input type="time" name="end_break_time1" value="{{ $attendanceRecord->end_break_time1 ?? '' }}" class="time-input" {{ $canEdit ? '' : 'disabled' }}>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-label">休憩2</div>
                <div class="form-input-group">
                    <input type="time" name="start_break_time2" value="{{ $attendanceRecord->start_break_time2 ?? '' }}" class="time-input" {{ $canEdit ? '' : 'disabled' }}>
                    <span class="separator">~</span>
                    <input type="time" name="end_break_time2" value="{{ $attendanceRecord->end_break_time2 ?? '' }}" class="time-input" {{ $canEdit ? '' : 'disabled' }}>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-label">備考</div>
                <div class="form-textarea-group">
                    <textarea name="note" class="note-textarea" placeholder="備考を入力してください" {{ $canEdit ? '' : 'disabled' }}>{{ $attendanceRecord->note ?? '' }}</textarea>
                </div>
            </div>
            
            @if(request()->has('user_id'))
                <input type="hidden" name="user_id" value="{{ request()->get('user_id') }}">
            @endif
            
            <div class="form-actions">
                {{-- 勤怠詳細画面：管理者でもスタッフと同じ動作 --}}
                @if($isPending)
                    {{-- 申請中の場合：メッセージのみ --}}
                    <div class="approval-message">※承認待ちのため修正はできません。</div>
                @else
                    {{-- 未申請の場合：修正ボタン --}}
                    <button type="submit" class="btn-modify">修正</button>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection
