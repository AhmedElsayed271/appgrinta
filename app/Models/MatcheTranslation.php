<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatcheTranslation extends Model
{
    use HasFactory;
    protected $table = "match_translations";
    public $timestamps = false;
    protected $fillable=['location','channel','group','matche_id','locale'];
}
