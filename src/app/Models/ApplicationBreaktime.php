<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationBreaktime extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'start_break_time',
        'end_break_time1',
    ];

    /**
     * この休憩時間に属する申請
     */
    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}

