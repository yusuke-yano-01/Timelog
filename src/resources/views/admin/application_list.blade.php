@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_application_list.css') }}">
@endsection

@section('content')
<div class="application-container">
    <div class="application-header">
        <div class="header-bar"></div>
        <h1 class="application-title">申請一覧</h1>
    </div>
    
    <div class="application-tabs">
        <a href="{{ route('admin.application.list', ['status' => 'pending']) }}" 
           class="tab-link {{ $status === 'pending' ? 'active' : '' }}">
            承認待ち
        </a>
        <a href="{{ route('admin.application.list', ['status' => 'approved']) }}" 
           class="tab-link {{ $status === 'approved' ? 'active' : '' }}">
            承認済み
        </a>
    </div>
    
    <div class="application-table-container">
        <table class="application-table">
            <thead>
                <tr>
                    <th class="status-column">状態</th>
                    <th class="name-column">名前</th>
                    <th class="date-column">対象日時</th>
                    <th class="reason-column">申請理由</th>
                    <th class="application-date-column">申請日時</th>
                    <th class="action-column">詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $application)
                <tr class="application-row">
                    <td class="status-cell">
                        <span class="status-badge {{ $application->application_flg === 1 ? 'pending' : 'approved' }}">
                            {{ $application->application_flg === 1 ? '承認待ち' : '承認済み' }}
                        </span>
                    </td>
                    <td class="name-cell">{{ $application->user->name }}</td>
                    <td class="date-cell">{{ $application->date->format('Y/m/d') }}</td>
                    <td class="reason-cell">{{ $application->note ?? '-' }}</td>
                    <td class="application-date-cell">{{ $application->updated_at->format('Y/m/d') }}</td>
                    <td class="action-cell">
                        <a href="{{ route('admin.application.detail', [
                            'id' => $application->user_id,
                            'year' => $application->date->year,
                            'month' => $application->date->month,
                            'day' => $application->date->day
                        ]) }}" class="detail-link">詳細</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="no-data">申請データがありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

