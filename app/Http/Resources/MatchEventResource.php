<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MatchEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => (int)$this->id,
            'minute' => (int)$this->minute,
            'status' => (string)$this->status,
            'player_id' => (int)$this->player_id,
            'player' => new PlayerResource($this->whenLoaded('player')),
            'enter' => new EnterResource($this->whenLoaded('enter')),
            'team_id' => (int)$this->team_id,
            'team' => new TeamResource($this->whenLoaded('team')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
