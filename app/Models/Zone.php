<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
{
    protected $fillable = ['code', 'nom', 'geometrie_geojson', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function relevesMeteo(): HasMany
    {
        return $this->hasMany(ReleveMeteo::class);
    }

    public function alertes(): HasMany
    {
        return $this->hasMany(Alerte::class);
    }
}