<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = [
        'user_id',
        'title',
        'data',
        'share',
        'status',
        'is_active',
        'emoticon_count',
        'share_count',
        'comment_count',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
    
    public function post_share(){
        return $this->belongsTo(Post::class,'share_id');
    }
}
