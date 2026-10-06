<?php

namespace App\Http\Controllers;

use App\Models\Campagne;
use App\Models\Mission;
use App\Models\Participation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserCampaignController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureUser($request);
        $campagnes = Campagne::with(['missions' => fn ($query) => $query
            ->withCount(['participations as occupied' => fn ($query) => $query->whereIn('statut', Participation::ACTIVE_STATUTS)])
            ->orderBy('date_debut')->orderBy('id')])->orderBy('date_debut')->orderBy('id')->get();

        $open = fn (Campagne $campagne) => $campagne->missions->contains(fn ($mission) => $this->isOpen($mission, $campagne) && $mission->occupied < $mission->nombre_places);
        $campaignGroups = collect([
            'Campagnes avec missions ouvertes' => $campagnes->filter($open)->groupBy('statut'),
            'Autres campagnes' => $campagnes->reject($open)->groupBy('statut'),
        ]);

        return view('campaigns.index', ['space' => 'user', 'campagnes' => $campagnes, 'campaignGroups' => $campaignGroups]);
    }

    public function missions(Request $request, Campagne $campagne): JsonResponse
    {
        $this->ensureUser($request);
        $missions = $campagne->missions()->withCount([
            'participations as occupied' => fn ($query) => $query->whereIn('statut', Participation::ACTIVE_STATUTS),
        ])->with(['participations' => fn ($query) => $query->where('user_id', $request->user()->id)->orderByDesc('id')])
            ->orderBy('date_debut')->orderBy('id')->get();
        $priorities = array_flip(['ouverte', 'en_cours', 'complete', 'terminee', 'annulee']);
        $missions = $missions->sortBy(fn ($mission) => $priorities[$mission->statut] ?? 99)->values();

        return response()->json(['missions' => $missions->map(fn ($mission) => $this->missionData($mission, $campagne))]);
    }

    public function participations(Request $request): View
    {
        $this->ensureUser($request);
        $participations = $request->user()->participations()->with('mission.campagne')->get()
            ->sortBy(fn ($participation) => $participation->mission->date_debut)->values();

        return view('user.participations.index', compact('participations'));
    }

    public function participate(Request $request, Mission $mission): JsonResponse
    {
        $this->ensureUser($request);
        $data = $request->validate(['motivation' => ['nullable', 'string', 'max:2000']]);

        $result = DB::transaction(function () use ($request, $mission, $data) {
            // Serialize registrations and mission changes to prevent duplicate or excess places.
            $campagne = Campagne::whereKey($mission->campagne_id)->lockForUpdate()->firstOrFail();
            $locked = Mission::whereKey($mission->id)->lockForUpdate()->firstOrFail();
            if ($locked->campagne_id !== $campagne->id) {
                throw ValidationException::withMessages(['mission' => 'La campagne de cette mission a changé. Rechargez la page.']);
            }
            $existing = $locked->participations()->where('user_id', $request->user()->id)
                ->whereIn('statut', Participation::ACTIVE_STATUTS)->first();
            if ($existing) {
                if ($existing->statut === 'en_attente') {
                    $existing->update(['statut' => 'acceptee']);
                }

                return ['participation' => $existing, 'created' => false];
            }
            if (! $this->isOpen($locked, $campagne)) {
                throw ValidationException::withMessages(['mission' => 'Les inscriptions à cette mission sont fermées.']);
            }
            $occupied = $locked->participations()->whereIn('statut', Participation::ACTIVE_STATUTS)->count();
            if ($occupied >= $locked->nombre_places) {
                throw ValidationException::withMessages(['mission' => 'Cette mission est complète. Il ne reste plus de place.']);
            }
            $participation = $locked->participations()->updateOrCreate(['user_id' => $request->user()->id], [
                'motivation' => $data['motivation'] ?? null, 'statut' => 'acceptee', 'presence' => 'non_renseignee',
            ]);

            return ['participation' => $participation, 'created' => true];
        });

        return response()->json([
            'message' => $result['created'] ? 'Votre participation est confirmée. Vous êtes inscrit à cette mission.' : 'Vous êtes déjà inscrit à cette mission. Votre participation est acceptée.',
            'participation' => $result['participation'],
        ], $result['created'] ? 201 : 200);
    }

    private function missionData(Mission $mission, Campagne $campagne): array
    {
        $participation = $mission->participations->first(fn ($participation) => in_array($participation->statut, Participation::ACTIVE_STATUTS))
            ?? $mission->participations->first();
        $available = max(0, $mission->nombre_places - $mission->occupied);

        return [
            'id' => $mission->id, 'campagne_id' => $mission->campagne_id, 'titre' => $mission->titre,
            'description' => $mission->description, 'lieu' => $mission->lieu,
            'date_debut' => $mission->date_debut->format('Y-m-d\TH:i'), 'date_fin' => $mission->date_fin->format('Y-m-d\TH:i'),
            'statut' => $mission->statut, 'nombre_places' => $mission->nombre_places, 'available' => $available,
            'participation_statut' => $participation?->statut,
            'can_participate' => $this->isOpen($mission, $campagne) && $available > 0
                && ! in_array($participation?->statut, Participation::ACTIVE_STATUTS),
        ];
    }

    private function isOpen(Mission $mission, Campagne $campagne): bool
    {
        // Mission datetimes are entered and displayed as local wall-clock times.
        return $mission->statut === 'ouverte' && in_array($campagne->statut, ['publiee', 'en_cours'])
            && $mission->date_debut->format('Y-m-d H:i:s') > now('Africa/Tunis')->format('Y-m-d H:i:s');
    }

    private function ensureUser(Request $request): void
    {
        abort_unless($request->user()?->role === 'user', 403);
    }
}
