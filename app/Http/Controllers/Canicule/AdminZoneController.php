<?php

namespace App\Http\Controllers\Canicule;

use App\Models\Zone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminZoneController extends AdminCaniculeController
{
    public function index(): View
    {
        return view('admin.canicule.zones.index', ['zones' => Zone::withCount(['alertes', 'relevesMeteo'])->orderBy('nom')->paginate(15)]);
    }

    public function edit(Zone $zone): View
    {
        return view('admin.canicule.zones.form', compact('zone'));
    }

    public function update(Request $request, Zone $zone): RedirectResponse
    {
        $zone->update($request->validate(['active' => ['required', 'boolean']]));

        return redirect()->route('admin.canicule.zones.index')->with('success', 'Zone modifiée.');
    }

}