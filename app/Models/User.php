<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'cover_avatar',
        'authentication',
        'email_verified_at',
        'time_offline',
        'password',
        'is_online',
        'is_active',
        'address',
        'hometown',
        'gender',
        'birthday',
        'relationship',
        'follower',
        'friend_counts',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function findForPassport($username)
    {
        return $this->where('email', $username)->orWhere('phone', $username)->first();
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function emotions()
    {
        return $this->morphMany(Emotion::class, 'emotionable');
    }

    public function user_stories()
    {
        return $this->hasMany(UserStories::class);
    }

    public function stories()
    {
        return $this->hasMany(Story::class);
    }

    // public function reports()
    // {
    //     return $this->hasMany(Report::class);
    // }
    //Báo cáo người dùng
    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function blocks()
    {
        return $this->belongsToMany(User::class, 'blocks', 'user_block', 'user_is_blocked');
    }

    public function friends()
    {
        return $this->belongsToMany(User::class, 'friends', 'user1', 'user2');
    }
}
