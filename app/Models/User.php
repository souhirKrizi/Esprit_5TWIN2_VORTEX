<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const GOUVERNORATS = [
        'Tunis', 'Ariana', 'Ben Arous', 'Manouba', 'Bizerte', 'Béja',
        'Jendouba', 'Le Kef', 'Siliana', 'Nabeul', 'Zaghouan', 'Sousse',
        'Monastir', 'Mahdia', 'Sfax', 'Kairouan', 'Kasserine', 'Sidi Bouzid',
        'Gabès', 'Médenine', 'Tataouine', 'Gafsa', 'Tozeur', 'Kébili',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'governorate',
        'password',
        'email_alerts',
        'terms_accepted_at',
        'role',
        'archived',
        'photo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function campagnes(): HasMany
    {
        return $this->hasMany(Campagne::class, 'organisateur_id');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(Participation::class);
    }

    public function spaceUrl(): string
    {
        $space = match ($this->role) {
            'admin' => 'admin',
            'agent-campagne' => 'agent-campagne',
            default => 'user',
        };

        return route('spaces.'.$space, ['user' => $this->getRouteKey()]);
    }

    public function signalements(): HasMany
    {
        return $this->hasMany(Signalement::class);
    }

    public function photoUrl(): ?string
    {
        return $this->photo ? asset('storage/'.$this->photo) : null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'email_alerts' => 'boolean',
            'terms_accepted_at' => 'datetime',
            'archived' => 'boolean',
        ];
    }
}
