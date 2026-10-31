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

    public function suspect(): static
    {
        return $this->state(fn (): array => [
            'score_suspicion'   => 75,
            'niveau_suspicion'  => Signalement::NIVEAU_ELEVE,
            'motifs_suspicion'  => [
                'URL, e-mail ou numéro de téléphone dans la description',
                'Texte répétitif ou ratio de mots uniques insuffisant',
            ],
            'analyse_ia_statut' => Signalement::ANALYSE_ANALYSE,
            'analyse_at'        => now(),
        ]);
    }

    public function doublonDe(Signalement $original): static
    {
        return $this->state(fn (): array => [
            'doublon_de_id'     => $original->id,
            'type_signalement'  => $original->type_signalement,
            'type_incident'     => $original->type_incident,
            'latitude'          => (float) $original->latitude + 0.0005,
            'longitude'         => (float) $original->longitude + 0.0005,
            'score_suspicion'   => 45,
            'niveau_suspicion'  => Signalement::NIVEAU_MOYEN,
            'motifs_suspicion'  => [
                '1 signalement(s) similaire(s) à moins de 200m (signalé par 1 citoyen(s) différent(s))',
            ],
            'analyse_ia_statut' => Signalement::ANALYSE_ANALYSE,
            'analyse_at'        => now(),
        ]);
    }

    public function faible(): static
    {
        return $this->state(fn (): array => [
            'score_suspicion'   => 10,
            'niveau_suspicion'  => Signalement::NIVEAU_FAIBLE,
            'motifs_suspicion'  => [],
            'analyse_ia_statut' => Signalement::ANALYSE_ANALYSE,
            'analyse_at'        => now(),
        ]);
    }
}
