<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
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
            'team_id'=>(int)$this->team_id,
            'name'=>(string)$this->name!=null?(string)$this->name:(string)$this->translate('ar')->name,
            //'name' =>(string)$this->translate('en')->name,
            'name_ar'=>(string)$this->translate('ar')->name,
            'name_en'=>(string)$this->translate('en')->name,
            'image_path'=>$this->image_path,
            'created_at'=>$this->created_at,
            'updated_at'=>$this->updated_at,
        ];
    }
}
