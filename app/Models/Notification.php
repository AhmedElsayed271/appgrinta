<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model implements TranslatableContract
{
    use HasFactory;
    use Translatable;

    protected $fillable = [
        'client_id',
        'type',
        'image',
        'data',
        'is_read',
    ];

    public $translatedAttributes = ['title', 'body'];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    const TYPES = [
        'goal',
        'match_status',
        'reminder',
        'announcement',
        'post',
        'team',
        'league',
        'system',
    ];

    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Client::class, 'client_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}