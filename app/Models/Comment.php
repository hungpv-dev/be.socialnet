<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Comment extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'post_id',
        'content',
        'parent_id'
    ];

    protected $casts = [
        'content' => 'array',
    ];

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }
    
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function emotions(){
        return $this->morphMany(Emotion::class, 'emotionable');
    }
    public function user_emotion() {
        return $this->morphOne(Emotion::class, 'emotionable')
            ->where('user_id', auth()->id());
    }
    public function children()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }
    //Báo cáo bình luận
    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }
}
