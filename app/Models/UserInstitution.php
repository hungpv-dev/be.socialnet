<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInstitution extends Model
{
    use HasFactory;

    // Đặt tên bảng (nếu tên bảng không trùng với tên Model theo chuẩn Laravel)
    protected $table = 'user_institutions';

    // Khai báo các thuộc tính có thể được gán hàng loạt
    protected $fillable = [
        'start_date',
        'end_date',
        'status',
        'major',
        'user_id'
    ];
    public function instituteable()
    {
        return $this->morphTo();
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public $timestamps = false;
}
