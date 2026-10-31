<?php

namespace Database\Factories;

use App\Models\Campagne;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/** @extends Factory<Campagne> */
class CampagneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organisateur_id' => User::factory()->state(['role' => 'agent-campagne']),
            'titre' => 'Prévention canicule - '.fake()->unique()->numerify('####'),
            'description' => 'Sensibilisation aux risques de la chaleur et accompagnement des habitants.',
            'objectif' => 'Informer les habitants et protéger les personnes vulnérables.',
            'lieu' => fake()->randomElement(['Tunis', 'Sfax', 'Sousse', 'Kairouan', 'Bizerte']),
            'date_debut' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
            'date_fin' => fn (array $attributes) => Carbon::parse($attributes['date_debut'])->addDays(7)->toDateString(),
            'statut' => 'publiee',
            'image' => null,
        ];
    }
}
