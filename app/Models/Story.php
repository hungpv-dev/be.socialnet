<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Story extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'file',
        'status',
        'user_count',
        'created_at'
    ];

    protected $casts = [
        'file' => 'array',
        'created_at' => 'datetime'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
    public function user_emotion() {
        return $this->hasOne(UserStories::class, 'story_id')
            ->where('user_id', auth()->id());
    }
    //Báo cáo tin
    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }
}
