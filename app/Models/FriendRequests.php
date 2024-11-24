<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FriendRequests extends Model
{
    use HasFactory;
    protected $table = 'friend_requests';
    public $timestamps = false;
    protected $fillable = ['sender', 'receiver', 'created_at'];
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender');
    }
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver');
    }
}
