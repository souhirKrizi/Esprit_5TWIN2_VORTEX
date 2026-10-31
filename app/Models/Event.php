<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'events';

    protected $fillable = [
        'title',
        'date_event',
        'governorate',
        'location',
        'description',
        'type',
        'status',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'date_event' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];
}
