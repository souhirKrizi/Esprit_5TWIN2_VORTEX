<?php

namespace App\Http\Controllers;

use App\Models\Campagne;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminCampagneController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $this->ensureAdmin($request);

        // Administrators see every campaign, regardless of its agent or status.
        $campagnes = Campagne::with('organisateur:id,name')->latest()->orderByDesc('id')->get();
        $agents = User::where('role', 'agent-campagne')->orderBy('name')->get(['id', 'name']);

        if ($request->expectsJson()) {
            return response()->json(compact('campagnes', 'agents'));
        }

        return view('admin.campagnes.index', compact('campagnes', 'agents'));
    }

    public function show(Request $request, Campagne $campagne): JsonResponse
    {
        $this->ensureAdmin($request);

        return response()->json($campagne->load('organisateur:id,name'));
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);
        $data = $this->validated($request);
        $path = $request->hasFile('image') ? $request->file('image')->store('campagnes', 'public') : null;
        $data['image'] = $path;

        try {
            $campagne = Campagne::create($data);
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $exception;
        }

        return response()->json($campagne->load('organisateur:id,name'), 201);
    }

    public function update(Request $request, Campagne $campagne): JsonResponse
    {
        $this->ensureAdmin($request);
        $data = $this->validated($request);
        unset($data['image']);
        $oldImage = $campagne->image;
        $path = $request->hasFile('image') ? $request->file('image')->store('campagnes', 'public') : null;
        if ($path) {
            $data['image'] = $path;
        }

        try {
            $campagne->update($data);
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $exception;
        }

        if ($path && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return response()->json($campagne->load('organisateur:id,name'));
    }

    public function destroy(Request $request, Campagne $campagne): JsonResponse
    {
        $this->ensureAdmin($request);

        DB::transaction(function () use ($campagne) {
            $campagne = Campagne::whereKey($campagne->id)->lockForUpdate()->firstOrFail();
            if (DB::table('missions')->where('campagne_id', $campagne->id)->exists()) {
                throw ValidationException::withMessages([
                    'campagne' => 'Cette campagne possède des missions et ne peut pas être supprimée.',
                ]);
            }
            $campagne->delete();
        });

        if ($campagne->image) {
            Storage::disk('public')->delete($campagne->image);
        }

        return response()->json(['message' => 'La campagne a été supprimée.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'objectif' => ['required', 'string'],
            'lieu' => ['required', 'string', 'max:255'],
            'organisateur_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'agent-campagne')],
            'date_debut' => ['required', 'date_format:Y-m-d'],
            'date_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_debut'],
            'statut' => ['required', Rule::in(Campagne::STATUTS)],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ], [
            'organisateur_id.exists' => 'Choisissez un utilisateur ayant le rôle agent-campagne.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ]);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }
}
