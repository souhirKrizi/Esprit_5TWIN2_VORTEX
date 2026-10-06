<?php

namespace Database\Factories;

use App\Models\RessourceUtile;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Signalement> */
class SignalementFactory extends Factory
{
    protected $model = Signalement::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement([Signalement::TYPE_INCIDENT, Signalement::TYPE_RESSOURCE_OK]);

        return [
            'user_id' => User::factory(),
            'ressource_utile_id' => RessourceUtile::factory(),
            'type_signalement' => $type,
            'type_incident' => $type === Signalement::TYPE_INCIDENT ? $this->faker->randomElement(['coupure_electricite', 'point_eau_hors_service', 'centre_ferme', 'autre']) : null,
            'latitude' => $this->faker->randomFloat(7, 30, 38),
            'longitude' => $this->faker->randomFloat(7, 7, 12),
            'commentaire' => $type === Signalement::TYPE_INCIDENT ? $this->faker->sentence(12) : null,
            'statut_validation' => Signalement::STATUT_EN_ATTENTE,
            'motif_rejet' => null,
        ];
    }

    public function valide(): static
    {
        return $this->state(fn (): array => ['statut_validation' => Signalement::STATUT_VALIDE]);
    }

    public function rejete(): static
    {
        return $this->state(fn (): array => [
            'statut_validation' => Signalement::STATUT_REJETE,
            'motif_rejet' => 'Signalement non confirmé.',
        ]);
    }
}
