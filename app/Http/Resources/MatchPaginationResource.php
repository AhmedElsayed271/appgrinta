<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MatchPaginationResource extends JsonResource
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
            'data' => MatchResource::collection($this->resource->items()),
            'links' => [
                'first'=>$this->resource->url(1),
                'last'=>$this->resource->lastPage(),
                'prev'=>$this->resource->previousPageUrl(),
                'next'=>$this->resource->nextPageUrl(),
            ],
            'meta'=>[
                'current_page'=>$this->resource->currentPage(),
                'from'=>$this->resource->firstItem(),
                'last_page'=>$this->resource->lastPage(),
                'links'=>$this->resource->linkCollection(),
                'path'=>$this->resource->lastPage(),
                'per_page'=>$this->resource->perPage(),
                'to'=>$this->resource->lastItem(),
                'total'=>$this->resource->total(),
            ]
        ];
    }
}
