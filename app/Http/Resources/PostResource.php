<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\PaginatedResourceResponse;
use Illuminate\Pagination\AbstractPaginator;

class PostResource extends JsonResource
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
            'name'=>(string)$this->name!=null?(string)$this->name:(string)$this->translate('ar')->name,
            'description'=>(string)$this->description!=null?(string)$this->description:(string)$this->translate('ar')->description,
            'image_path'=>(string)$this->image_path,
            'featured'=>  $this->featured,
            'youtube_link'=> $this->youtube_link,
            'created_at'=>$this->created_at,
            'updated_at'=>$this->updated_at,
            'category'=> new CategoryResource($this->category),
            'user'=> new UserResource($this->user),
            // 'reactions'=> $this->reactionSummary($request) // [reactions-off]
        ];
    }

    protected function reactionSummary($request): array
    {
        $reactions = $this->reactions ?? collect();
        $counts = ['like' => 0, 'love' => 0, 'haha' => 0, 'wow' => 0, 'sad' => 0, 'angry' => 0];

        foreach ($reactions as $reaction) {
            $counts[$reaction->type] = ($counts[$reaction->type] ?? 0) + 1;
        }

        $clientId = $request->user()?->getAuthIdentifier();
        $myReaction = $reactions->firstWhere('client_id', $clientId);

        return [
            'count' => $reactions->count(),
            'like' => $counts['like'],
            'love' => $counts['love'],
            'haha' => $counts['haha'],
            'wow' => $counts['wow'],
            'sad' => $counts['sad'],
            'angry' => $counts['angry'],
            'my_reaction' => $myReaction?->type ?? null,
        ];
    }
}
