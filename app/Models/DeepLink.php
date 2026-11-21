<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeepLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'target_type',
        'target_id',
        'screen',
        'clicks_count',
        'last_clicked_at',
    ];

    protected $casts = [
        'target_id'      => 'integer',
        'clicks_count'   => 'integer',
        'last_clicked_at' => 'datetime',
    ];
}
