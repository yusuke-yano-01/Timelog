<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    const STATUS_PENDING = 1;
    const STATUS_APPROVED = 0;

    protected $fillable = [
        'user_id',
        'time_id',
        'date',
        'arrival_time',
        'departure_time',
        'note',
        'application_flg',
    ];

    protected $casts = [
        'date' => 'date',
        'application_flg' => 'integer',
    ];

    /**
     * この申請に属するユーザー
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この申請に属するタイム記録
     */
    public function time()
    {
        return $this->belongsTo(Time::class);
    }

    /**
     * この申請の休憩時間
     */
    public function breaktimes()
    {
        return $this->hasMany(ApplicationBreaktime::class);
    }
}

