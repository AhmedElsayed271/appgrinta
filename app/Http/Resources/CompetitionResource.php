<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompetitionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if (is_null($this->resource)) {
            return [];
        }

        return [
            'id'         => (int) $this->id,
            'league_id'  => (int) $this->league_id,
            'country_id' => (int) $this->country_id,
            'parent_id'  => $this->parent_id ? (int) $this->parent_id : null,
            'parent'     => $this->when(
                                !is_null($this->parent_id) && $this->relationLoaded('parent') && !is_null($this->parent),
                                fn() => new CompetitionResource($this->parent)
                            ),
            'name'       => $this->name !== null
                                ? (string) $this->name
                                : (string) optional($this->translate('ar'))->name,
            'season'     => (string) $this->season,
            'sort'       => (int) $this->sort,
            'image_path' => (string) asset('storage/uploads/competition_images/' . $this->image),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}