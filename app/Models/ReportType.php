<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportType extends Model
{
    use HasFactory;
    protected $table = 'report_types';
    protected $fillable = ['name'];
    public $timestamps = false;
    public function reports()
    {
        return $this->hasMany(Report::class, 'report_type_id');
    }
}
