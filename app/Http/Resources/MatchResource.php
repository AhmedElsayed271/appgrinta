<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MatchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $timezone = request()->input('timezone') ?? optional(auth()->user())->timezone ?? config('app.timezone');
        $matchDate = $this->match_date ? $this->match_date->copy()->setTimezone($timezone) : null;

        return [
            'id'=>(int)$this->id,
            'fixture_id'=>(int)$this->fixture_id,
            'competition_id'=>(int)$this->competition_id,
            'competition'=>new CompetitionResource($this->whenLoaded('competition')),
            'week'=>(string)$this->week,
            'status'=>(string)$this->status,
            'location'=>(string)$this->location,
//            'image_path'=>asset('storage/uploads/team_images/'.($this->image)),
            'match_date'=>$matchDate,
            'home'=>new TeamResource($this->team1),
            'away'=>new TeamResource($this->team2),
            'home_goals'=>$this->home_goals,
            'away_goals'=>$this->away_goals,
            'elapsed'=>(int)$this->elapsed,
            'created_at'=>$this->created_at,
            'updated_at'=>$this->updated_at,
        ];
    }
}
