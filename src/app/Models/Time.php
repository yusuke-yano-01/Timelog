<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Time extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'month_id',
        'date',
        'arrival_time',
        'departure_time',
        'start_break_time1',
        'end_break_time1',
        'start_break_time2',
        'end_break_time2',
        'note',
        'application_flg',
    ];

    protected $casts = [
        'date' => 'date',
        'application_flg' => 'boolean',
    ];

    /**
     * このタイム記録に属するユーザー
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * このタイム記録に属する月
     */
    public function month()
    {
        return $this->belongsTo(Month::class);
    }
}
