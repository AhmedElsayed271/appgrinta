<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompitionWithCountryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // return parent::toArray($request);
        return [
            'id'=>(int)$this->id,
            'league_id'=>(int)$this->league_id,
            'country_id'=>(int)$this->country_id,
            'parent_id'=>(int)$this->parent_id,
            'parent'=>new CompetitionResource($this->parent),
            'name'=>(string)$this->name!=null?(string)$this->name:(string)$this->translate('ar')->name,
            'season'=>(string)$this->season,
            'sort'=>(int)$this->sort,
            'image_path'=>(string)asset('storage/uploads/competition_images/'.($this->image)),
            'created_at'=>$this->created_at,
            'updated_at'=>$this->updated_at,
            'country' => [
                'id'=>(int)$this->country->id,
                'name'=>(string)$this->country->name!=null?(string)$this->country->name:(string)$this->country->translate('ar')->name,
                'image_path'=>(string)asset('storage/uploads/country_images/'.($this->country->image)),
                'created_at'=>$this->country->created_at,
                'updated_at'=>$this->country->updated_at,
            ]
        ];
    }
}
