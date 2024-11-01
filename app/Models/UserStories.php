<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStories extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'story_id',
        'emoji',
        'seen'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function story(){
        return $this->belongsTo(Story::class,'story_id');
    }
}
