<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
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
            'name'=>(string)$this->name!=null?(string)$this->name:(string)$this->translate('ar')->name,
            'image_path'=>(string)asset('storage/uploads/country_images/'.($this->image)),
            'created_at'=>$this->created_at,
            'updated_at'=>$this->updated_at,
        ];
    }
}
