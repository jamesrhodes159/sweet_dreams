<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'otp',
        'full_name',
        'email',
        'user_type',
        'phone_number',
        'dob',
        'password',
        'profile_image',
        'bio',
        'language',
        'legal_name',
        'driver_license',
        'is_verified',
        'customer_id',
        'account_number',
        "account_verified",
        'email_verified_at',
        'device_type',
        'device_token',
        'is_social',
        'is_forgot',
        'is_signup',
        'user_social_token',
        'user_social_type',
        'is_profile_complete',
        'notification',
        'is_blocked',
        'is_active'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    public function earned_points()
    {
        return $this->belongsTo(User::class,'user_id')->select('user_points');
    }
}
