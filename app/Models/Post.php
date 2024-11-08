<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'dream_type' , 'image', 'title', 'description' , 'post_type' , 'user_id' , 'topic'  , 'feeling', 'type'
    ];

    public function likes()
    {
        return $this->hasMany(PostLike::class,'post_id','id')->select('id','post_id','user_id','reaction');
    }
    

    public function comments()
    {
        return $this->hasMany(PostComment::class,'post_id','id')->where('parent_id',0);
    }

    public function all_comment()
    {
        return $this->hasMany(PostComment::class,'post_id','id');
    }

    public function check_like()
    {
        if (Auth::check()) {
        //there is a user logged in, now to get the id
         $user_id = Auth::user()->id;
        }
        
        return $this->hasOne(PostLike::class,'post_id','id')->where('user_id',$user_id);
       
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','id')->select('id', 'full_name' , 'profile_image');
    }

    public function check_save()
    {
        if (Auth::check()) {
        //there is a user logged in, now to get the id
         $user_id = Auth::user()->id;
        }
        
        return $this->hasOne(PostSave::class,'post_id','id')->where('user_id',$user_id);
       
    }

    public function mention()
    {
        return $this->hasMany(PostMention::class,'post_id','id')->select('id','post_id','user_id');
    }
}
