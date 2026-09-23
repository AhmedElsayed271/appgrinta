<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchEventTranslation extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable=['name','description','match_event_id','locale'];
}
