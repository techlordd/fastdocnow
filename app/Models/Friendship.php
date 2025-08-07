<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Friendship extends Pivot
{
    use HasFactory;

    protected $table = 'friendships';

    public $incrementing = true;

    protected $fillable = [
        'user_id',
        'friend_id',
        'created_at',
        'updated_at',
    ];
}
