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

    public function user(){
        return $this->belongsTo(User::class);
    }
    //Báo cáo tin
    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }
}
