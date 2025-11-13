@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_application_detail.css') }}">
@endsection

@section('content')
<div class="application-detail-container">
    <div class="application-detail-header">
        <div class="header-bar"></div>
        <h1 class="application-detail-title">申請詳細</h1>
    </div>
    
    <div class="application-detail-form">
        <div class="form-row">
            <div class="form-label">名前</div>
            <div class="form-value">{{ $targetUser->name }}</div>
        </div>
        
        <div class="form-row">
            <div class="form-label">日付</div>
            <div class="form-value">
                <span class="year">{{ $date->format('Y年') }}</span>
                <span class="month-day">{{ $date->format('n月j日') }}</span>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-label">出勤・退勤</div>
            <div class="form-value">
                {{ $application->arrival_time ?? '-' }} ~ {{ $application->departure_time ?? '-' }}
            </div>
        </div>
        
        @php
            $breaktimes = $application->breaktimes ?? collect();
        @endphp
        
        @if($breaktimes->isEmpty())
            <div class="form-row">
                <div class="form-label">休憩</div>
                <div class="form-value">-</div>
            </div>
        @else
            @foreach($breaktimes as $index => $breaktime)
                <div class="form-row">
                    <div class="form-label">{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</div>
                    <div class="form-value">
                        {{ $breaktime->start_break_time ?? '-' }} ~ {{ $breaktime->end_break_time1 ?? '-' }}
                    </div>
                </div>
            @endforeach
        @endif
        
        <div class="form-row">
            <div class="form-label">備考</div>
            <div class="form-value">
                {{ $application->note ?? '-' }}
            </div>
        </div>
        
    </div>

    <div class="form-actions">
        @if($isPending)
            {{-- 申請中の場合：承認ボタンのみ --}}
            <form action="{{ route('timelog.approve') }}" method="post" class="approve-form">
                @csrf
                <input type="hidden" name="application_id" value="{{ $application->id }}">
                <input type="hidden" name="date" value="{{ $date->format('Y-m-d') }}">
                <input type="hidden" name="user_id" value="{{ $targetUser->id }}">
                <input type="hidden" name="from_application_detail" value="1">
                <button type="submit" class="btn-approve">承認</button>
            </form>
        @else
            {{-- 承認済みの場合：背景灰色、テキスト白で「承認済み」を表示 --}}
            <div class="approved-status">承認済み</div>
        @endif
    </div>
</div>
@endsection

