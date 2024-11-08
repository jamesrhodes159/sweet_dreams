<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Follower extends Model
{
    use HasFactory;
    
    public function user()
    {
        return  $this->belongsTo(User::class,'user_id')->select('id','full_name','profile_image');
    }


    public function follower()
    {
        return  $this->belongsTo(User::class,'follower_id')->select('id','full_name','profile_image');
    }
    
}
