<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    private const ROLES = ['user', 'agent-campagne', 'admin'];

    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status') && $request->string('status')->value() !== 'all', function ($query) use ($request) {
                $query->where('archived', $request->string('status')->value() === 'archived');
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(Request $request, User $user): View
    {
        $this->ensureAdmin($request);

        return view('admin.users.show', compact('user'));
    }

    public function edit(Request $request, User $user): View
    {
        $this->ensureAdmin($request);

        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'governorate' => ['nullable', 'string', 'max:100'],
            'role' => ['required', 'in:'.implode(',', self::ROLES)],
        ]);

        if ($user->is($request->user()) && ($validated['role'] !== 'admin' || $user->archived)) {
            return back()->with('error', 'Vous ne pouvez pas désactiver ou rétrograder votre propre compte.');
        }

        $user->update($validated);

        return redirect()->route('admin.users.show', $user)->with('success', 'Le compte utilisateur a été mis à jour.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate(['role' => ['required', 'in:'.implode(',', self::ROLES)]]);
        if ($user->is($request->user()) && $validated['role'] !== 'admin') {
            return back()->with('error', 'Vous ne pouvez pas rétrograder votre propre compte.');
        }

        $user->update(['role' => $validated['role']]);

        return back()->with('success', 'Le rôle a été modifié.');
    }

    public function archive(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);
        abort_if($user->is($request->user()), 422, 'Vous ne pouvez pas archiver votre propre compte.');

        $user->update(['archived' => ! $user->archived]);

        return back()->with('success', $user->archived ? 'Le compte a été archivé.' : 'Le compte a été restauré.');
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }
}