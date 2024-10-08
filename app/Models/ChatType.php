<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatType extends Model
{
    use HasFactory;
    protected $table = 'chat_types';

    public function chat_rooms(){
        return $this->hasMany(ChatRoom::class);
    }
}
