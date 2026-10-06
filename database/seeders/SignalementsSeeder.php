<?php

namespace Database\Seeders;

use App\Models\RessourceUtile;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SignalementsSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->where('role', 'user')->orderBy('id')->get();

        if ($users->isEmpty()) {
            $this->command?->warn('Aucun citoyen existant : le seeder des signalements est arrêté sans rien créer.');

            return;
        }

        DB::transaction(function () use ($users): void {
            $resources = collect([
                ['nom' => 'Point d’eau Bab El Khadra', 'type' => 'point_eau', 'gouvernorat' => 'Tunis', 'adresse' => 'Bab El Khadra, Tunis', 'latitude' => 36.8124000, 'longitude' => 10.1763000, 'statut' => 'actif'],
                ['nom' => 'Espace fraîcheur du Belvédère', 'type' => 'espace_fraicheur', 'gouvernorat' => 'Tunis', 'adresse' => 'Parc du Belvédère, Tunis', 'latitude' => 36.8197000, 'longitude' => 10.1711000, 'statut' => 'actif'],
                ['nom' => 'Centre d’accueil La Marsa', 'type' => 'centre_accueil', 'gouvernorat' => 'Tunis', 'adresse' => 'Avenue Taieb Mhiri, La Marsa', 'latitude' => 36.8782000, 'longitude' => 10.3247000, 'statut' => 'actif'],
                ['nom' => 'Point d’eau Bab Bhar', 'type' => 'point_eau', 'gouvernorat' => 'Sfax', 'adresse' => 'Place de la République, Sfax', 'latitude' => 34.7406000, 'longitude' => 10.7603000, 'statut' => 'actif'],
                ['nom' => 'Espace fraîcheur Route El Ain', 'type' => 'espace_fraicheur', 'gouvernorat' => 'Sfax', 'adresse' => 'Route El Ain, Sfax', 'latitude' => 34.7561000, 'longitude' => 10.7255000, 'statut' => 'actif'],
                ['nom' => 'Centre d’accueil Sahloul', 'type' => 'centre_accueil', 'gouvernorat' => 'Sousse', 'adresse' => 'Quartier Sahloul, Sousse', 'latitude' => 35.8327000, 'longitude' => 10.6084000, 'statut' => 'actif'],
                ['nom' => 'Point d’eau Médina de Sousse', 'type' => 'point_eau', 'gouvernorat' => 'Sousse', 'adresse' => 'Médina de Sousse', 'latitude' => 35.8256000, 'longitude' => 10.6369000, 'statut' => 'actif'],
                ['nom' => 'Espace fraîcheur Kairouan centre', 'type' => 'espace_fraicheur', 'gouvernorat' => 'Kairouan', 'adresse' => 'Avenue Ibn El Jazzar, Kairouan', 'latitude' => 35.6781000, 'longitude' => 10.0963000, 'statut' => 'actif'],
                ['nom' => 'Centre d’accueil Bab Jelladine', 'type' => 'centre_accueil', 'gouvernorat' => 'Kairouan', 'adresse' => 'Bab Jelladine, Kairouan', 'latitude' => 35.6788000, 'longitude' => 10.1035000, 'statut' => 'actif'],
                ['nom' => 'Point d’eau Jara', 'type' => 'point_eau', 'gouvernorat' => 'Gabès', 'adresse' => 'Quartier Jara, Gabès', 'latitude' => 33.8815000, 'longitude' => 10.0982000, 'statut' => 'actif'],
                ['nom' => 'Espace fraîcheur Chenini', 'type' => 'espace_fraicheur', 'gouvernorat' => 'Gabès', 'adresse' => 'Chenini Gabès', 'latitude' => 33.8859000, 'longitude' => 10.1046000, 'statut' => 'hors_service'],
                ['nom' => 'Centre d’accueil Tozeur', 'type' => 'centre_accueil', 'gouvernorat' => 'Tozeur', 'adresse' => 'Avenue Abou El Kacem Chebbi, Tozeur', 'latitude' => 33.9197000, 'longitude' => 8.1335000, 'statut' => 'actif'],
                ['nom' => 'Point d’eau Ouled el Hadef', 'type' => 'point_eau', 'gouvernorat' => 'Tozeur', 'adresse' => 'Ouled el Hadef, Tozeur', 'latitude' => 33.9226000, 'longitude' => 8.1321000, 'statut' => 'actif'],
                ['nom' => 'Espace fraîcheur Zarzouna', 'type' => 'espace_fraicheur', 'gouvernorat' => 'Bizerte', 'adresse' => 'Zarzouna, Bizerte', 'latitude' => 37.2724000, 'longitude' => 9.8688000, 'statut' => 'actif'],
                ['nom' => 'Centre d’accueil Corniche', 'type' => 'centre_accueil', 'gouvernorat' => 'Bizerte', 'adresse' => 'Corniche de Bizerte', 'latitude' => 37.2850000, 'longitude' => 9.8610000, 'statut' => 'actif'],
                ['nom' => 'Point d’eau Gafsa ville', 'type' => 'point_eau', 'gouvernorat' => 'Gafsa', 'adresse' => 'Centre-ville de Gafsa', 'latitude' => 34.4250000, 'longitude' => 8.7842000, 'statut' => 'actif'],
                ['nom' => 'Espace fraîcheur Sidi Ahmed Zarroug', 'type' => 'espace_fraicheur', 'gouvernorat' => 'Gafsa', 'adresse' => 'Sidi Ahmed Zarroug, Gafsa', 'latitude' => 34.4148000, 'longitude' => 8.7763000, 'statut' => 'actif'],
                ['nom' => 'Centre d’accueil Nabeul', 'type' => 'centre_accueil', 'gouvernorat' => 'Nabeul', 'adresse' => 'Avenue Habib Bourguiba, Nabeul', 'latitude' => 36.4513000, 'longitude' => 10.7356000, 'statut' => 'actif'],
                ['nom' => 'Point d’eau Hammamet sud', 'type' => 'point_eau', 'gouvernorat' => 'Nabeul', 'adresse' => 'Hammamet sud', 'latitude' => 36.3958000, 'longitude' => 10.6031000, 'statut' => 'actif'],
                ['nom' => 'Espace fraîcheur Médenine centre', 'type' => 'espace_fraicheur', 'gouvernorat' => 'Médenine', 'adresse' => 'Centre-ville de Médenine', 'latitude' => 33.3549000, 'longitude' => 10.5055000, 'statut' => 'actif'],
            ])->mapWithKeys(function (array $attributes): array {
                $resource = RessourceUtile::query()->updateOrCreate(
                    ['nom' => $attributes['nom'], 'gouvernorat' => $attributes['gouvernorat']],
                    $attributes,
                );

                return [$attributes['nom'] => $resource];
            });

            $definitions = [
                [
                    'resource' => 'Point d’eau Bab El Khadra',
                    'user' => 0,
                    'type_signalement' => Signalement::TYPE_INCIDENT,
                    'type_incident' => 'point_eau_hors_service',
                    'latitude' => 36.8124000,
                    'longitude' => 10.1763000,
                    'commentaire' => 'Le point d’eau ne distribue plus d’eau depuis ce matin.',
                    'statut_validation' => Signalement::STATUT_VALIDE,
                    'motif_rejet' => null,
                ],
                [
                    'resource' => 'Centre d’accueil Sahloul',
                    'user' => 1,
                    'type_signalement' => Signalement::TYPE_RESSOURCE_OK,
                    'type_incident' => null,
                    'latitude' => 35.8327000,
                    'longitude' => 10.6084000,
                    'commentaire' => null,
                    'statut_validation' => Signalement::STATUT_VALIDE,
                    'motif_rejet' => null,
                ],
                [
                    'resource' => 'Espace fraîcheur du Belvédère',
                    'user' => 0,
                    'type_signalement' => Signalement::TYPE_INCIDENT,
                    'type_incident' => 'autre',
                    'latitude' => 36.8197000,
                    'longitude' => 10.1711000,
                    'commentaire' => 'Une panne de climatisation est signalée dans l’espace.',
                    'statut_validation' => Signalement::STATUT_EN_ATTENTE,
                    'motif_rejet' => null,
                ],
                [
                    'resource' => 'Point d’eau Jara',
                    'user' => 1,
                    'type_signalement' => Signalement::TYPE_INCIDENT,
                    'type_incident' => 'point_eau_hors_service',
                    'latitude' => 33.8815000,
                    'longitude' => 10.0982000,
                    'commentaire' => 'Le robinet public est fermé et inaccessible.',
                    'statut_validation' => Signalement::STATUT_REJETE,
                    'motif_rejet' => 'La vérification sur place confirme que le point fonctionne.',
                ],
                [
                    'resource' => 'Espace fraîcheur Zarzouna',
                    'user' => 0,
                    'type_signalement' => Signalement::TYPE_RESSOURCE_OK,
                    'type_incident' => null,
                    'latitude' => 37.2724000,
                    'longitude' => 9.8688000,
                    'commentaire' => null,
                    'statut_validation' => Signalement::STATUT_EN_ATTENTE,
                    'motif_rejet' => null,
                ],
            ];

            foreach ($definitions as $definition) {
                $resource = $resources[$definition['resource']];
                $user = $users[$definition['user'] % $users->count()];
                $attributes = [
                    'user_id' => $user->id,
                    'ressource_utile_id' => $resource->id,
                    'type_signalement' => $definition['type_signalement'],
                    'latitude' => $definition['latitude'],
                    'longitude' => $definition['longitude'],
                ];
                $values = [
                    'type_incident' => $definition['type_incident'],
                    'commentaire' => $definition['commentaire'],
                    'statut_validation' => $definition['statut_validation'],
                    'motif_rejet' => $definition['motif_rejet'],
                ];

                Signalement::query()->updateOrCreate($attributes, $values);
            }
        });

        $this->command?->info('Données de démonstration des signalements créées ou mises à jour sans doublons.');
    }
}
