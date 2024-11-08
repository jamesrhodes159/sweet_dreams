<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                        =>  $this->id,
            'user_id'                   =>  $this->id,
            'full_name'                =>  $this->full_name,
            'email'                     =>  $this->email,
            'profile_image'             =>  $this->profile_image,
            'phone_number'              =>  $this->phone_number,
            // 'country_code'              =>  $this->country_code,
            // 'dial_code'                 =>  $this->dial_code,
            // 'isTrial'                   =>  $this->isTrial,
            // 'isSubscribed'              =>  $this->isSubscribed,
            'date_of_birth'             =>  $this->dob,
            'bio'                       =>  $this->bio,
            'language'                  =>  $this->language,
            'notification'              =>  $this->notification??null,
            'is_profile_complete'       =>  $this->is_profile_complete??0,
            'device_type'               =>  $this->device_type,
            'device_token'              =>  $this->device_token,
            'is_verified'               =>  $this->account_verified,
            'is_social'                 =>  $this->is_social,
            'social_type'               =>  $this->user_social_type,
            // 'is_deleted'                =>  $this->is_deleted,
            'legal_name'                =>  $this->legal_name,
            'driver_license'            =>  $this->driver_license
        ];
    }
}
