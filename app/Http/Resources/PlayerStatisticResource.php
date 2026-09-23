<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlayerStatisticResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => (int)$this->id,
            'player_id' => (int)$this->player_id,
            'competition_id' => (int)$this->competition_id,
            'competition'=>new CompetitionResource($this->whenLoaded('competition')),
            'player'=>new PlayerResource($this->whenLoaded('player')),
            'yellow_card' => (int)$this->yellow_card,
            'red_card' => (int)$this->red_card,
            'goal' => (int)$this->goal,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
