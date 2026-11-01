<?php

namespace App\Services;

use App\Models\Mission;
use App\Models\Participation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DashboardService
{
    public function forUser(User $user): array
    {
        return match ($user->role) {
            'admin' => $this->admin(),
            'agent-campagne' => $this->agent($user),
            default => $this->citizen($user),
        };
    }

    private function admin(): array
    {
        $users = User::query();

        return [
            'title' => 'Tableau de bord administrateur',
            'description' => 'Une vue d’ensemble des comptes et des inscriptions sur HeatAlert.',
            'link' => route('admin.users.index'), 'action' => 'Gérer les utilisateurs',
            'metrics' => [
                'Utilisateurs au total' => $users->count(),
                'Comptes actifs' => (clone $users)->where('archived', false)->count(),
                'Comptes archivés' => (clone $users)->where('archived', true)->count(),
                'Inscriptions ce mois' => (clone $users)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            ],
            'charts' => [
                $this->distribution('Répartition par rôle', 'Tous les comptes, y compris les comptes archivés.', $users, 'role', [
                    'user' => 'Citoyens', 'agent-campagne' => 'Agents de campagne', 'admin' => 'Administrateurs',
                ]),
                $this->monthly('Évolution des inscriptions', $users),
                $this->distribution('État des comptes', 'Comptes actifs et archivés.', $users, 'archived', [0 => 'Actifs', 1 => 'Archivés']),
            ],
        ];
    }

    private function agent(User $user): array
    {
        $campaigns = $user->campagnes()->getQuery();
        $missions = Mission::whereHas('campagne', fn ($query) => $query->where('organisateur_id', $user->id));
        $participations = Participation::whereHas('mission.campagne', fn ($query) => $query->where('organisateur_id', $user->id));

        return [
            'title' => 'Tableau de bord campagnes',
            'description' => 'Suivez vos campagnes, leurs missions et la mobilisation des participants.',
            'link' => route('agent-campagne.campagnes'), 'action' => 'Voir mes campagnes',
            'metrics' => [
                'Mes campagnes' => $campaigns->count(),
                'Campagnes en cours' => (clone $campaigns)->where('statut', 'en_cours')->count(),
                'Missions créées' => $missions->count(),
                'Participations acceptées' => (clone $participations)->where('statut', 'acceptee')->count(),
            ],
            'charts' => [
                $this->distribution('État de mes campagnes', 'Répartition de toutes vos campagnes par statut.', $campaigns, 'statut', [
                    'brouillon' => 'Brouillon', 'publiee' => 'Publiées', 'en_cours' => 'En cours', 'terminee' => 'Terminées', 'annulee' => 'Annulées',
                ]),
                $this->monthly('Création de campagnes', $campaigns),
                $this->distribution('Mobilisation des participants', 'Inscriptions aux missions de vos campagnes.', $participations, 'statut', $this->participationLabels()),
            ],
        ];
    }

    private function citizen(User $user): array
    {
        $participations = $user->participations()->getQuery();
        $missions = Mission::whereHas('participations', fn ($query) => $query->where('user_id', $user->id)->whereIn('statut', Participation::ACTIVE_STATUTS));

        return [
            'title' => 'Mon tableau de bord',
            'description' => 'Retrouvez votre engagement et l’avancement des missions que vous avez rejointes.',
            'link' => route('user.participations'), 'action' => 'Voir mes participations',
            'metrics' => [
                'Mes inscriptions' => $participations->count(),
                'Participations acceptées' => (clone $participations)->where('statut', 'acceptee')->count(),
                'Missions à venir' => (clone $missions)->whereIn('statut', ['ouverte', 'complete'])->where('date_debut', '>', now())->count(),
                'Missions terminées' => (clone $missions)->where('statut', 'terminee')->count(),
            ],
            'charts' => [
                $this->distribution('Mes participations', 'Statut de toutes vos inscriptions aux missions.', $participations, 'statut', $this->participationLabels()),
                $this->monthly('Mon engagement au fil des mois', $participations),
                $this->distribution('Avancement de mes missions', 'Missions avec une inscription acceptée ou en attente.', $missions, 'statut', [
                    'ouverte' => 'Ouvertes', 'complete' => 'Complètes', 'en_cours' => 'En cours', 'terminee' => 'Terminées', 'annulee' => 'Annulées',
                ]),
            ],
        ];
    }

    private function participationLabels(): array
    {
        return ['en_attente' => 'En attente', 'acceptee' => 'Acceptées', 'refusee' => 'Refusées', 'annulee' => 'Annulées'];
    }

    private function distribution(string $title, string $description, Builder $query, string $column, array $labels): array
    {
        $counts = (clone $query)->select($column)->selectRaw('COUNT(*) as total')->groupBy($column)->pluck('total', $column);
        $values = [];
        foreach ($labels as $key => $label) {
            $values[] = ['label' => $label, 'value' => (int) ($counts[$key] ?? 0)];
        }

        return compact('title', 'description', 'values') + ['type' => 'donut'];
    }

    private function monthly(string $title, Builder $query): array
    {
        $values = [];
        $start = now()->startOfMonth()->subMonths(5);
        for ($i = 0; $i < 6; $i++) {
            $month = $start->copy()->addMonths($i);
            $values[] = [
                'label' => $month->locale('fr')->translatedFormat('M Y'),
                'value' => (clone $query)->whereBetween('created_at', [$month, $month->copy()->endOfMonth()])->count(),
            ];
        }

        return ['title' => $title, 'description' => 'Nouveaux enregistrements sur les six derniers mois.', 'values' => $values, 'type' => 'bar'];
    }
}
