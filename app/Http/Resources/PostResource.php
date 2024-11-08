<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $mentions = $this->mention->map(function ($mention) {
            $mention['user'] = $mention->user;
            return $mention;
        });

        return [
            'id'                        =>  $this->id,
            'user_id'                   =>  $this->user_id,
            'user'                      =>  $this->user,
            'dream_type'                =>  $this->dream_type,
            'title'                     =>  $this->title,
            'description'               =>  $this->description,
            'image'                     =>  $this->image,
            'post_type'                 =>  $this->post_type,
            'topic'                     =>  json_decode($this->topic),
            'feeling'                   =>  json_decode($this->feeling),
            'likes_count'               =>  $this->likes_count??0,
            'comments_count'            =>  $this->comments_count??0,
            'isLike'                    =>  $this->check_like_count??0,
            'isSave'                    =>  $this->check_save_count??0,
            'isHide'                    =>  $this->isHide??null,
            'created_at'                =>  $this->created_at,
            'likes'                      =>  $this->likes->pluck('reaction'),
            'mention'                   =>  $mentions
        ];
    }
}
