<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    use HasFactory;
    protected $table = 'blocks';
    public $timestamps = false;
    protected $fillable = ['user_block', 'user_is_blocked', 'created_at'];

    public function userBlock()
    {
        return $this->belongsTo(User::class, 'user_block');
    }

    public function userIsBlocked()
    {
        return $this->belongsTo(User::class, 'user_is_blocked');
    }
}
