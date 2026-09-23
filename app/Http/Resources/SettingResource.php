<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
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
            'description' => (string)$this->description != null ? (string)$this->description : (string)$this->translate('ar')->description,
            'privacy' => (string)$this->privacy != null ? (string)$this->privacy : (string)$this->translate('ar')->privacy,
        ];
    }
}
