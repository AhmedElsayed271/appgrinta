<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enter extends Model
{
    use HasFactory;
    protected $fillable=[
        'player_id',
        'match_event_id'
    ];
    public function player(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Player::class,'player_id');
    }

    public function matchEvent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\MatchEvent::class,'match_event_id');
    }
}
