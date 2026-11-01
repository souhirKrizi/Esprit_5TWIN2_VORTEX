<?php

namespace App\Http\Controllers;

use App\Models\Campagne;
use App\Models\Mission;
use App\Services\CampagneRapportService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgentMissionController extends Controller
{
    public function exportCampagnes(Request $request): StreamedResponse
    {
        $this->ensureAgent($request);
        $campaigns = $request->user()->campagnes()->withCount('missions')->orderBy('id');

        return response()->streamDownload(function () use ($campaigns) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['ID', 'Titre', 'Description', 'Objectif', 'Lieu', 'Date de début', 'Date de fin', 'Statut', 'Nombre de missions'], ';', '"', '');
            $statuses = ['brouillon' => 'Brouillon', 'publiee' => 'Publiée', 'en_cours' => 'En cours', 'terminee' => 'Terminée', 'annulee' => 'Annulée'];

            foreach ($campaigns->lazyById(500) as $campaign) {
                $row = [
                    $campaign->id, $campaign->titre, $campaign->description, $campaign->objectif,
                    $campaign->lieu, $campaign->date_debut->format('Y-m-d'), $campaign->date_fin->format('Y-m-d'),
                    $statuses[$campaign->statut] ?? $campaign->statut, $campaign->missions_count,
                ];
                // Keep user-entered text from being interpreted as spreadsheet formulas.
                $row = array_map(static fn ($value) => is_string($value) && preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value, $row);
                fputcsv($output, $row, ';', '"', '');
            }
            fclose($output);
        }, 'mes-campagnes-'.now('Africa/Tunis')->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function rapport(Request $request, Campagne $campagne, CampagneRapportService $service)
    {
        $this->ensureAgent($request);
        $this->ownedCampaign($request, $campagne->id);
        $lock = Cache::lock('rapport-campagne-'.$campagne->id, 120);
        if (! $lock->get()) {
            return response()->json(['message' => 'Un rapport est déjà en cours de génération pour cette campagne.'], 409);
        }

        try {
            $donnees = $service->snapshot($campagne);
            try {
                $analyse = $service->generate($donnees);
            } catch (RuntimeException $exception) {
                return response()->json(['message' => $exception->getMessage()], 503);
            }
            $this->ownedCampaign($request, $campagne->id);
            $options = new Options;
            $options->set('isRemoteEnabled', false);
            $options->set('isPhpEnabled', false);
            $options->set('isJavascriptEnabled', false);
            $options->set('defaultFont', 'DejaVu Sans');
            $pdf = new Dompdf($options);
            $pdf->setPaper('A4');
            $pdf->loadHtml(view('agent.campagnes.rapport', [
                'donnees' => $donnees,
                'analyse' => $analyse,
                'sections' => CampagneRapportService::SECTIONS,
                'genereLe' => now('Africa/Tunis'),
                'modele' => config('services.gemini_rapport.model'),
                'logo' => 'data:image/jpeg;base64,'.base64_encode(file_get_contents(public_path('assets/images/Logo_HeatAlert_avec_flamme_integree.jpg'))),
            ])->render());
            $pdf->render();
            $pdf->getCanvas()->page_text(42, 807, 'HeatAlert | Rapport de campagne | Page {PAGE_NUM} / {PAGE_COUNT}', 'DejaVu Sans', 8, [0.35, 0.39, 0.45]);

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="HeatAlert-campagne-'.$campagne->id.'-rapport.pdf"',
                'Cache-Control' => 'private, no-store',
            ]);
        } finally {
            $lock->release();
        }
    }

    public function participations(Request $request): View
    {
        $this->ensureAgent($request);
        $campagnes = $request->user()->campagnes()->with([
            'missions' => fn ($query) => $query->orderBy('date_debut')->orderBy('id'),
            'missions.participations' => fn ($query) => $query->orderBy('id'),
            'missions.participations.user:id,name,email',
        ])->orderByDesc('date_debut')->orderByDesc('id')->paginate(10);

        return view('agent.participations.index', ['space' => 'agent-campagne', 'campagnes' => $campagnes]);
    }

    public function index(Request $request): View
    {
        $this->ensureAgent($request);
        $campagnes = $request->user()->campagnes()->with([
            'missions' => fn ($query) => $query->orderBy('date_debut')->orderBy('id'),
        ])->orderBy('date_debut')->orderBy('id')->get();

        $today = now('Africa/Tunis')->toDateString();
        $upcoming = fn (Campagne $campagne) => $campagne->date_debut->toDateString() > $today
            && ! in_array($campagne->statut, ['terminee', 'annulee']);
        $campaignGroups = collect([
            'À venir' => $campagnes->filter($upcoming)->groupBy('statut'),
            'Autres campagnes' => $campagnes->reject($upcoming)->groupBy('statut'),
        ]);
        $totalMissions = $campagnes->sum(fn ($campagne) => $campagne->missions->count());
        $mode = $request->routeIs('agent-campagne.campagnes') ? 'campagnes' : 'missions';

        return view('agent.campagnes.index', compact('campagnes', 'campaignGroups', 'totalMissions', 'mode'));
    }

    public function show(Request $request, Mission $mission): JsonResponse
    {
        $this->ensureAgent($request);
        $this->ownedCampaign($request, $mission->campagne_id);
        $participants = DB::table('participations')->join('users', 'users.id', '=', 'participations.user_id')
            ->where('mission_id', $mission->id)->orderBy('participations.id')
            ->get(['users.name', 'participations.motivation', 'participations.statut', 'participations.presence']);

        return response()->json(['mission' => array_replace($mission->toArray(), [
            'date_debut' => $mission->date_debut->format('Y-m-d\TH:i'),
            'date_fin' => $mission->date_fin->format('Y-m-d\TH:i'),
        ]), 'participants' => $participants]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAgent($request);
        $data = $this->validated($request);
        $mission = DB::transaction(function () use ($request, $data) {
            $campagne = $this->ownedCampaign($request, $data['campagne_id'], true);
            $this->validateCampaignDates($data, $campagne);

            return $campagne->missions()->create($data);
        });
        $request->session()->flash('success', 'La mission a été créée.');

        return response()->json($mission, 201);
    }

    public function update(Request $request, Mission $mission): JsonResponse
    {
        $this->ensureAgent($request);
        $this->ownedCampaign($request, $mission->campagne_id);
        $data = $this->validated($request);
        DB::transaction(function () use ($request, $mission, $data) {
            $this->ownedCampaign($request, $mission->campagne_id, true);
            $campagne = $this->ownedCampaign($request, $data['campagne_id'], true);
            $locked = Mission::whereKey($mission->id)->lockForUpdate()->firstOrFail();
            $this->ownedCampaign($request, $locked->campagne_id);
            $this->validateCampaignDates($data, $campagne);
            $occupied = DB::table('participations')->where('mission_id', $locked->id)
                ->whereIn('statut', ['en_attente', 'acceptee'])->count();
            if ($data['nombre_places'] < $occupied) {
                throw ValidationException::withMessages([
                    'nombre_places' => 'Le nombre de places ne peut pas être inférieur aux inscriptions actives ('.$occupied.').',
                ]);
            }
            $locked->update($data);
        });
        $request->session()->flash('success', 'La mission a été modifiée.');

        return response()->json($mission->fresh());
    }

    public function destroy(Request $request, Mission $mission): JsonResponse
    {
        $this->ensureAgent($request);
        DB::transaction(function () use ($request, $mission) {
            $this->ownedCampaign($request, $mission->campagne_id, true);
            $locked = Mission::whereKey($mission->id)->lockForUpdate()->firstOrFail();
            $this->ownedCampaign($request, $locked->campagne_id);
            DB::table('participations')->where('mission_id', $locked->id)->delete();
            $locked->delete();
        });
        $request->session()->flash('success', 'La mission a été supprimée.');

        return response()->json(['message' => 'La mission a été supprimée.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'campagne_id' => ['required', 'integer', Rule::exists('campagnes', 'id')->where('organisateur_id', $request->user()->id)],
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'lieu' => ['required', 'string', 'max:255'],
            'date_debut' => ['required', 'date_format:Y-m-d\TH:i'],
            'date_fin' => ['required', 'date_format:Y-m-d\TH:i', 'after:date_debut'],
            'nombre_places' => ['required', 'integer', 'min:1', 'max:100000'],
            'statut' => ['required', Rule::in(Mission::STATUTS)],
        ], [
            'campagne_id.exists' => 'Choisissez une campagne qui vous est affectée.',
            'date_fin.after' => 'La fin doit être postérieure au début de la mission.',
        ]);
    }

    private function validateCampaignDates(array $data, Campagne $campagne): void
    {
        $errors = [];
        foreach (['date_debut', 'date_fin'] as $field) {
            $date = substr($data[$field], 0, 10);
            if ($date < $campagne->date_debut->toDateString() || $date > $campagne->date_fin->toDateString()) {
                $errors[$field] = 'Les dates de la mission doivent être comprises dans la période de la campagne.';
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function ownedCampaign(Request $request, int $id, bool $lock = false): Campagne
    {
        return $request->user()->campagnes()->when($lock, fn ($query) => $query->lockForUpdate())->findOrFail($id);
    }

    private function ensureAgent(Request $request): void
    {
        abort_unless($request->user()?->role === 'agent-campagne', 403);
    }
}
