@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_staff_list.css') }}">
@endsection

@section('content')
<div class="staff-list-container">
    <div class="staff-list-header">
        <div class="header-bar"></div>
        <h1 class="staff-list-title">スタッフ一覧</h1>
    </div>
    
    <div class="staff-table-container">
        <table class="staff-table">
            <thead>
                <tr>
                    <th class="name-column">名前</th>
                    <th class="email-column">メールアドレス</th>
                    <th class="attendance-column">月次勤怠</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staffs as $staff)
                <tr class="staff-row">
                    <td class="name-cell">{{ $staff->name }}</td>
                    <td class="email-cell">{{ $staff->email }}</td>
                    <td class="attendance-cell">
                        <a href="{{ route('timelog.list') }}?user_id={{ $staff->id }}" class="detail-link">詳細</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="no-data">スタッフが登録されていません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

