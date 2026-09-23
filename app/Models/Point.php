<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Point extends Model
{
    use HasFactory;
    protected $fillable=[
        'win',
        'draw',
        'loss',
        'goals_for',
        'goals_against',
        'team_id',
        'competition_id',
        'match_id',
    ];
}
