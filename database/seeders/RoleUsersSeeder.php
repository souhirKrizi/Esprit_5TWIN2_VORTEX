<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleUsersSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach ([
                ['name' => 'Admin Vortex', 'email' => 'admin@vortex.com', 'role' => 'admin', 'password' => 'ad123456'],
                ['name' => 'Agent Vortex', 'email' => 'agent@vortex.com', 'role' => 'agent-campagne', 'password' => 'ag123456'],
                ['name' => 'Utilisateur Vortex', 'email' => 'user@vortex.com', 'role' => 'user', 'password' => 'us123456'],
            ] as $account) {
                $user = User::firstOrNew(['email' => $account['email']]);
                $user->fill($account);
                $user->governorate = 'Tunis';
                $user->archived = false;
                $user->email_verified_at = now();
                $user->terms_accepted_at = now();
                $user->save();
            }
        });
    }
}
