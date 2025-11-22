<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Breaktime extends Model
{
    use HasFactory;

    protected $fillable = [
        'time_id',
        'start_break_time',
        'end_break_time',
    ];

    protected $casts = [
        'start_break_time' => 'string',
        'end_break_time' => 'string',
    ];

    /**
     * この休憩時間に属するタイム記録
     */
    public function time()
    {
        return $this->belongsTo(Time::class);
    }
}

