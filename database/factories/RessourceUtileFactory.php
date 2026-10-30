<?php

namespace Database\Factories;

use App\Models\RessourceUtile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RessourceUtile> */
class RessourceUtileFactory extends Factory
{
    protected $model = RessourceUtile::class;

    public function definition(): array
    {
        $types = ['point_eau', 'espace_fraicheur', 'centre_accueil'];

        return [
            'nom' => 'Ressource utile '.$this->faker->unique()->numberBetween(1, 9999),
            'type' => $this->faker->randomElement($types),
            'gouvernorat' => $this->faker->randomElement(['Tunis', 'Sfax', 'Sousse', 'Kairouan', 'Gabès', 'Tozeur', 'Bizerte', 'Gafsa']),
            'adresse' => $this->faker->streetAddress(),
            'latitude' => $this->faker->randomFloat(7, 30, 38),
            'longitude' => $this->faker->randomFloat(7, 7, 12),
            'statut' => $this->faker->randomElement(['actif', 'hors_service']),
        ];
    }
}
