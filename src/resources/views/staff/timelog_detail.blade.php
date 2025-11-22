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
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        
        <form action="{{ route('timelog.update') }}" method="post">
            @csrf
            <input type="hidden" name="time_id" value="{{ $attendanceRecord->id ?? '' }}">
            <input type="hidden" name="date" value="{{ $date->format('Y-m-d') }}">
            
            <div class="form-section">
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
                    // 申請状態を確認（Applicationsテーブルから取得）
                    $isPending = $application && $application->application_flg === 1;
                    $isApproved = $application && $application->application_flg === 0;
                    $hasData = $attendanceRecord !== null;
                    $canEditStaff = !$isPending; // スタッフは承認待ちでない場合のみ編集可能
                    $canEditAdmin = true; // 管理者は常に編集可能
                    $canEdit = $isAdmin ?? false ? $canEditAdmin : $canEditStaff;

                    // 休憩時間を取得（全ての休憩時間を処理）
                    $breaktimeRecords = collect();

                    if (old('breaktimes')) {
                        $breaktimeRecords = collect(old('breaktimes'))->map(function ($break) {
                            return [
                                'start_break_time' => $break['start'] ?? null,
                                'end_break_time' => $break['end'] ?? null,
                            ];
                        });
                    } else {
                        if ($isPending && $application) {
                            $breaktimeRecords = $application->breaktimes->map(function ($breaktime) {
                                return [
                                    'start_break_time' => $breaktime->start_break_time,
                                    'end_break_time' => $breaktime->end_break_time,
                                ];
                            });
                        } elseif ($attendanceRecord) {
                            $breaktimeRecords = $attendanceRecord->breaktimes->map(function ($breaktime) {
                                return [
                                    'start_break_time' => $breaktime->start_break_time,
                                    'end_break_time' => $breaktime->end_break_time,
                                ];
                            });
                        }
                    }

                    $breaktimeRecords = $breaktimeRecords->sortBy('start_break_time')->values();
                    $firstBreakRecord = $breaktimeRecords->first();
                    $remainingBreakRecords = $breaktimeRecords->slice(1)->values();
                    if (!$firstBreakRecord) {
                        $firstBreakRecord = [
                            'start_break_time' => null,
                            'end_break_time' => null,
                        ];
                    }
                @endphp
                
                <div class="form-row">
                    <div class="form-label">出勤・退勤</div>
                    @if($isPending)
                        <div class="form-value">
                            {{ ($application && $application->arrival_time) ? $application->arrival_time : '-' }} ~ {{ ($application && $application->departure_time) ? $application->departure_time : '-' }}
                        </div>
                    @else
                        <div class="form-input-group">
                            <input type="time" name="arrival_time" value="{{ old('arrival_time', $attendanceRecord->arrival_time ?? '') }}" class="time-input" {{ $canEdit ? '' : 'disabled' }}>
                            <span class="separator">~</span>
                            <input type="time" name="departure_time" value="{{ old('departure_time', $attendanceRecord->departure_time ?? '') }}" class="time-input" {{ $canEdit ? '' : 'disabled' }}>
                        </div>
                    @endif
                </div>
                
                <div id="break-rows-wrapper" class="break-rows-wrapper">
                    @if($isPending)
                        @php
                            $displayBreaks = $breaktimeRecords;
                        @endphp
                        @if($displayBreaks->isEmpty())
                            <div class="form-row form-row-breaks">
                                <div class="form-label">休憩</div>
                                <div class="form-value form-value-break">
                                    <div class="break-readonly">
                                        <span class="break-readonly-time">-</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            @foreach($displayBreaks as $index => $breaktime)
                                <div class="form-row form-row-breaks">
                                    <div class="form-label">
                                        {{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}
                                    </div>
                                    <div class="form-value form-value-break">
                                        <div class="break-readonly">
                                            <span class="break-readonly-time">
                                                {{ $breaktime['start_break_time'] ?? '-' }} ~ {{ $breaktime['end_break_time'] ?? '-' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    @else
                        @php
                            $editableFirstBreak = [
                                'start_break_time' => $firstBreakRecord['start_break_time'] ?? null,
                                'end_break_time' => $firstBreakRecord['end_break_time'] ?? null,
                            ];
                            $editableAdditionalBreaks = $remainingBreakRecords;
                        @endphp
                        <div class="form-row form-row-breaks primary-break-row" data-break-index="0">
                            <div class="form-label">休憩</div>
                            <div class="form-value form-value-break">
                                <div class="break-row-inputs">
                                    <input
                                        type="time"
                                        name="breaktimes[0][start]"
                                        value="{{ old('breaktimes.0.start', $editableFirstBreak['start_break_time'] ?? '') }}"
                                        class="time-input"
                                        data-type="start"
                                        {{ $canEdit ? '' : 'disabled' }}
                                    >
                                    <span class="separator">~</span>
                                    <input
                                        type="time"
                                        name="breaktimes[0][end]"
                                        value="{{ old('breaktimes.0.end', $editableFirstBreak['end_break_time'] ?? '') }}"
                                        class="time-input"
                                        data-type="end"
                                        {{ $canEdit ? '' : 'disabled' }}
                                    >
                                </div>
                                @if($canEdit)
                                    <button type="button" id="add-break-time" class="btn-add-break" aria-label="休憩を追加">＋</button>
                                @endif
                            </div>
                        </div>
                        <div id="additional-break-rows" class="additional-break-rows">
                            @foreach($editableAdditionalBreaks as $index => $breaktime)
                                <div class="form-row form-row-breaks extra-break-row" data-break-index="{{ $index + 1 }}">
                                    <div class="form-label break-row-label">休憩{{ $index + 2 }}</div>
                                    <div class="form-value form-value-break">
                                        <div class="break-row-inputs">
                                            <input
                                                type="time"
                                                name="breaktimes[{{ $index + 1 }}][start]"
                                                value="{{ old('breaktimes.'.($index + 1).'.start', $breaktime['start_break_time'] ?? '') }}"
                                                class="time-input"
                                                data-type="start"
                                                {{ $canEdit ? '' : 'disabled' }}
                                            >
                                            <span class="separator">~</span>
                                            <input
                                                type="time"
                                                name="breaktimes[{{ $index + 1 }}][end]"
                                                value="{{ old('breaktimes.'.($index + 1).'.end', $breaktime['end_break_time'] ?? '') }}"
                                                class="time-input"
                                                data-type="end"
                                                {{ $canEdit ? '' : 'disabled' }}
                                            >
                                        </div>
                                        @if($canEdit)
                                            <button type="button" class="btn-remove-break" aria-label="休憩を削除">×</button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                
                <div class="form-row">
                    <div class="form-label">備考</div>
                    @if($isPending)
                        <div class="form-value form-value-note">
                            {{ ($application && $application->note) ? $application->note : '-' }}
                        </div>
                    @else
                        <div class="form-textarea-group">
                            <textarea name="note" class="note-textarea" placeholder="備考を入力してください" {{ $canEdit ? '' : 'disabled' }}>{{ old('note', $attendanceRecord->note ?? '') }}</textarea>
                        </div>
                    @endif
                </div>
            </div>
            
            {{-- 管理者がスタッフの勤怠を編集する場合、対象スタッフのIDを送信 --}}
            @if(isset($targetUser) && $targetUser->id !== Auth::user()->id)
                <input type="hidden" name="user_id" value="{{ $targetUser->id }}">
            @elseif(request()->has('user_id'))
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

@section('js')
@parent
@if(!$isPending && ($canEdit ?? false))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseRow = document.querySelector('.primary-break-row');
    const additionalContainer = document.getElementById('additional-break-rows');
    const addButton = document.getElementById('add-break-time');

    if (!baseRow || !additionalContainer || !addButton) {
        return;
    }

    const updateRowAttributes = () => {
        const baseStartInput = baseRow.querySelector('input[data-type="start"]');
        const baseEndInput = baseRow.querySelector('input[data-type="end"]');

        if (baseStartInput) {
            baseStartInput.name = 'breaktimes[0][start]';
        }
        if (baseEndInput) {
            baseEndInput.name = 'breaktimes[0][end]';
        }

        const extraRows = additionalContainer.querySelectorAll('.extra-break-row');

        extraRows.forEach((row, index) => {
            row.dataset.breakIndex = index + 1;
            const label = row.querySelector('.break-row-label');
            if (label) {
                label.textContent = `休憩${index + 2}`;
            }

            const startInput = row.querySelector('input[data-type="start"]');
            const endInput = row.querySelector('input[data-type="end"]');

            if (startInput) {
                startInput.name = `breaktimes[${index + 1}][start]`;
            }

            if (endInput) {
                endInput.name = `breaktimes[${index + 1}][end]`;
            }
        });
    };

    const createRow = (startValue = '', endValue = '') => {
        const row = document.createElement('div');
        row.className = 'form-row form-row-breaks extra-break-row';

        const label = document.createElement('div');
        label.className = 'form-label break-row-label';
        row.appendChild(label);

        const valueWrapper = document.createElement('div');
        valueWrapper.className = 'form-value form-value-break';

        const inputsWrapper = document.createElement('div');
        inputsWrapper.className = 'break-row-inputs';

        const startInput = document.createElement('input');
        startInput.type = 'time';
        startInput.className = 'time-input';
        startInput.dataset.type = 'start';
        startInput.value = startValue || '';
        inputsWrapper.appendChild(startInput);

        const separator = document.createElement('span');
        separator.className = 'separator';
        separator.textContent = '~';
        inputsWrapper.appendChild(separator);

        const endInput = document.createElement('input');
        endInput.type = 'time';
        endInput.className = 'time-input';
        endInput.dataset.type = 'end';
        endInput.value = endValue || '';
        inputsWrapper.appendChild(endInput);

        valueWrapper.appendChild(inputsWrapper);

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'btn-remove-break';
        removeButton.setAttribute('aria-label', '休憩を削除');
        removeButton.textContent = '×';
        valueWrapper.appendChild(removeButton);

        row.appendChild(valueWrapper);

        additionalContainer.appendChild(row);
        updateRowAttributes();
    };

    addButton.addEventListener('click', () => {
        createRow();
    });

    additionalContainer.addEventListener('click', (event) => {
        const target = event.target.closest('.btn-remove-break');
        if (!target) {
            return;
        }

        const row = target.closest('.extra-break-row');
        if (!row) {
            return;
        }

        row.remove();
        updateRowAttributes();
    });

    updateRowAttributes();
});
</script>
@endif
@endsection
