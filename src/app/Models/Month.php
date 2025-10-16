<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Month extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'end_date',
    ];

    /**
     * この月に属するタイム記録
     */
    public function times()
    {
        return $this->hasMany(Time::class);
    }
}
