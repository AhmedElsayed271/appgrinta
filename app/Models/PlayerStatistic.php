<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayerStatistic extends Model
{
    use HasFactory;
    protected $fillable = [
        'player_id',
        'competition_id',
        'yellow_card',
        'red_card',
        'goal',
        'assist',
    ];

    public function player(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Player::class,'player_id');
    }

    public function competition(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Competition::class,'competition_id');
    }
}
