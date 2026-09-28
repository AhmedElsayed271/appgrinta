<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            'id'         => (int)$this->id,
            'title'      => (string)$this->title != null ? (string)$this->title : (string)$this->translate('ar')->title,
            'body'       => (string)$this->body != null ? (string)$this->body : (string)$this->translate('ar')->body,
            'image'      => $this->image,
            'type'       => $this->type,
            'data'       => $this->data ? json_decode($this->data, true) : null,
            'is_read'    => (bool)$this->is_read,
            'created_at' => $this->created_at,
        ];
    }
}