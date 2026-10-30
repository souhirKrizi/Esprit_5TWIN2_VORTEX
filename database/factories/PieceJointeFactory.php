<?php

namespace Database\Factories;

use App\Models\PieceJointe;
use App\Models\Signalement;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PieceJointe> */
class PieceJointeFactory extends Factory
{
    protected $model = PieceJointe::class;

    public function definition(): array
    {
        return [
            'signalement_id' => Signalement::factory(),
            'chemin_fichier' => 'signalements/'.$this->faker->uuid().'.jpg',
            'nom_original' => 'photo.jpg',
            'type_mime' => 'image/jpeg',
            'taille' => $this->faker->numberBetween(10000, 500000),
        ];
    }
}
