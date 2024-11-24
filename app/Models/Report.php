<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;
    protected $table = 'reports';
    protected $fillable = [
        'report_type_id',
        'content',
        'status',
        'user_id'
    ];
    public function reportable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function report_type()
    {
        return $this->belongsTo(ReportType::class, 'report_type_id');
    }
}
