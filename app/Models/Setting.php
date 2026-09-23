<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory,Translatable;

    public $translatedAttributes = [
        'description',
        'privacy'
    ];
    protected $fillable=[
        'name',
        'logo',
        'title_icon',
        'loader_image',
        'firebase_key',
        'football_key',
        'leagues',
        'season'
    ];

    protected $appends=['logo_path','title_icon_path','loader_image_path'];

    public function getLogoPathAttribute(): string
    {
        return asset('storage/uploads/setting_logos/'.($this->logo ??'default.png'));
    }
    public function getTitleIconPathAttribute(): string
    {
        return asset('storage/uploads/setting_logos/'.($this->title_icon ??'favicon.ico'));
    }
    public function getLoaderImagePathAttribute(): string
    {
        return asset('storage/uploads/setting_logos/'.($this->loader_image ??'loader.png'));
    }
}
