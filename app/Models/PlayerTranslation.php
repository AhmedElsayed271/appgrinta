<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayerTranslation extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable=['player_id','first_name','last_name','locale'];
}
