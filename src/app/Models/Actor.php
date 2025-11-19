<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Actor extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
    ];

    /**
     * このアクターに属するユーザー
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
