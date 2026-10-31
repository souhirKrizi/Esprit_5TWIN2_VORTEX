<?php

namespace App\Http\Controllers\Canicule;

use App\Models\ReleveMeteo;
use App\Models\Zone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class AdminReleveMeteoController extends AdminCaniculeController
{
    public function index(Request $request): View
    {
        $zones = Zone::where('active', true)->orderBy('nom')->get();
        $selectedZone = $request->filled('zone_id')
            ? $zones->firstWhere('id', (int) $request->input('zone_id'))
            : $zones->first();

        return view('admin.canicule.releves.index', [
            'releves' => ReleveMeteo::with('zone')->latest('date_mesure')->paginate(15),
            'zones' => $zones,
            'selectedZone' => $selectedZone,
            'weather' => $selectedZone ? $this->weatherForZone($selectedZone) : null,
        ]);
    }

    public function create(): View
    {
        return view('admin.canicule.releves.form', ['releve' => new ReleveMeteo(['type' => 'observation']), 'zones' => Zone::where('active', true)->orderBy('nom')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        ReleveMeteo::create($this->validated($request));

        return redirect()->route('admin.canicule.releves.index')->with('success', 'Relevé météo ajouté.');
    }

    public function edit(ReleveMeteo $releve): View
    {
        return view('admin.canicule.releves.form', ['releve' => $releve, 'zones' => Zone::where('active', true)->orWhere('id', $releve->zone_id)->orderBy('nom')->get()]);
    }

    public function update(Request $request, ReleveMeteo $releve): RedirectResponse
    {
        $releve->update($this->validated($request));

        return redirect()->route('admin.canicule.releves.index')->with('success', 'Relevé météo modifié.');
    }

    public function destroy(ReleveMeteo $releve): RedirectResponse
    {
        $releve->delete();

        return redirect()->route('admin.canicule.releves.index')->with('success', 'Relevé météo supprimé.');
    }

    private function weatherForZone(Zone $zone): ?array
    {
        $apiKey = config('services.openweather.key');

        if (blank($apiKey)) {
            return null;
        }

        $response = Http::get('https://api.openweathermap.org/data/2.5/weather', [
            'q' => $zone->nom.',TN',
            'units' => 'metric',
            'lang' => 'fr',
            'appid' => $apiKey,
        ]);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();
        $main = $data['main'] ?? [];
        $wind = $data['wind'] ?? [];
        $weather = $data['weather'][0] ?? [];

        return [
            'temperature' => (float) ($main['temp'] ?? 0),
            'humidity' => (int) ($main['humidity'] ?? 0),
            'wind_speed' => (float) ($wind['speed'] ?? 0),
            'condition' => (string) ($weather['description'] ?? 'Données indisponibles'),
            'sunrise' => data_get($data, 'sys.sunrise'),
            'sunset' => data_get($data, 'sys.sunset'),
            'updated_at' => data_get($data, 'dt'),
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'type' => ['required', 'in:observation,prevision'],
            'date_mesure' => ['required', 'date'],
            'temperature' => ['required', 'numeric', 'between:-30,60'],
            'humidite' => ['required', 'integer', 'between:0,100'],
            'vitesse_vent' => ['nullable', 'numeric', 'between:0,300'],
            'indice_uv' => ['nullable', 'numeric', 'between:0,30'],
            'source' => ['required', 'string', 'max:100'],
        ]);
    }
}