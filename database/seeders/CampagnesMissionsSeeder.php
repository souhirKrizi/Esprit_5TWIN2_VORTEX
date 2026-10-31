<?php

namespace Database\Seeders;

use App\Models\Campagne;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CampagnesMissionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $agent = User::firstOrCreate(['email' => 'agent@vortex.com'], [
                'name' => 'Agent Vortex',
                'role' => 'agent-campagne',
                'password' => 'ag123456',
                'governorate' => 'Tunis',
                'terms_accepted_at' => now(),
                'archived' => false,
            ]);

            if ($agent->role !== 'agent-campagne' || $agent->archived) {
                throw new RuntimeException('Le compte agent@vortex.com doit être un agent-campagne actif.');
            }

            foreach ([
                ['Tunis', 'publiee', 2],
                ['Sfax', 'en_cours', -2],
                ['Sousse', 'brouillon', 14],
                ['Kairouan', 'terminee', -14],
                ['Bizerte', 'annulee', 7],
            ] as [$lieu, $statut, $offset]) {
                $attributes = Campagne::factory()->make([
                    'organisateur_id' => $agent->id,
                    'titre' => 'Vortex - Prévention canicule à '.$lieu,
                    'lieu' => $lieu,
                    'date_debut' => today()->addDays($offset),
                    'date_fin' => today()->addDays($offset + 6),
                    'statut' => $statut,
                ])->getAttributes();

                $campagne = Campagne::firstOrCreate([
                    'organisateur_id' => $agent->id,
                    'titre' => $attributes['titre'],
                ], $attributes);

                foreach (['Sensibilisation de quartier', 'Distribution d’eau', 'Visite aux personnes vulnérables'] as $index => $titre) {
                    $debut = $campagne->date_debut->copy()->addDays($index)->setTime(9, 0);
                    $mission = Mission::factory()->make([
                        'campagne_id' => $campagne->id,
                        'titre' => $titre,
                        'lieu' => $lieu,
                        'date_debut' => $debut,
                        'date_fin' => $debut->copy()->addHours(3),
                        'nombre_places' => 10 + $index * 5,
                        'statut' => match ($campagne->statut) {
                            'terminee' => 'terminee',
                            'annulee' => 'annulee',
                            'en_cours' => $index < 2 ? 'terminee' : 'en_cours',
                            default => 'ouverte',
                        },
                    ]);

                    Mission::firstOrCreate([
                        'campagne_id' => $campagne->id,
                        'titre' => $titre,
                    ], $mission->getAttributes());
                }
            }
        });
    }
}
