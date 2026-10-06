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

    public function spaceUrl(): string
    {
        $space = match ($this->role) {
            'admin' => 'admin',
            'agent-campagne' => 'agent-campagne',
            default => 'user',
        };

        return route('spaces.'.$space, ['user' => $this->getRouteKey()]);
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
