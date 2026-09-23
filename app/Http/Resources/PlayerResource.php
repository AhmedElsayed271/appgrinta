<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlayerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id'=>(int)$this->id,
            'player_id'=>(int)$this->player_id,
            'team_id'=>(int)$this->team_id,
            'name'=>$this->first_name ??(string)$this->translate('ar')->first_name . ' ' . $this->last_name ??(string)$this->translate('ar')->last_name,
            'first_name'=>(string)$this->first_name!=null?(string)$this->first_name:(string)$this->translate('ar')->first_name,
            'last_name'=>(string)$this->last_name!=null?(string)$this->last_name:(string)$this->translate('ar')->last_name,
            'position'=>(string)$this->position,
            'current_value'=>(string)$this->current_value,
            'twitter_account'=>(string)$this->twitter_account,
            'image_path'=>$this->image_path,
            'created_at'=>$this->created_at,
            'updated_at'=>$this->updated_at,
        ];
    }
}
