<?php

namespace Database\Factories;

use App\Models\Campagne;
use App\Models\Mission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/** @extends Factory<Mission> */
class MissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'campagne_id' => Campagne::factory(),
            'titre' => fake()->randomElement(['Distribution d’eau', 'Sensibilisation de quartier', 'Visite aux personnes vulnérables']),
            'description' => 'Accueillir les habitants, rappeler les gestes de prévention et orienter les personnes ayant besoin d’aide.',
            'lieu' => fn (array $attributes) => Campagne::findOrFail($attributes['campagne_id'])->lieu,
            'date_debut' => fn (array $attributes) => Campagne::findOrFail($attributes['campagne_id'])->date_debut->copy()->setTime(9, 0),
            'date_fin' => fn (array $attributes) => Carbon::parse($attributes['date_debut'])->addHours(3),
            'nombre_places' => fake()->numberBetween(5, 30),
            'statut' => 'ouverte',
        ];
    }
}
