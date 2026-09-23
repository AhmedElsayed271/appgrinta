<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function tokensAr()
    {
        return self::where('is_active', 1)->where('locale','ar')->pluck('fb_token')->toArray();
    }

    public static function tokens()
    {
        return self::where('is_active', 1)->pluck('fb_token')->toArray();
    }

    public static function tokensEn()
    {
        return self::where('is_active', 1)->where('locale','en')->pluck('fb_token')->toArray();
    }
}
