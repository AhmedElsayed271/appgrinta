<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'=>(int)$this->id,
            'full_name'=>(string)$this->full_name,
            'email'=>(string)$this->email,
            'api_token'=>(string)$this->api_token,
            'email_verified_at'=>$this->email_verified_at,
            'verified_code'=>$this->verified_code,
            'locale'=>$this->locale,
            'timezone'=>$this->timezone,
            'created_at'=>$this->created_at,
            'updated_at'=>$this->updated_at,
        ];
    }
}
