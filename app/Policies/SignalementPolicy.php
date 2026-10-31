<?php

namespace App\Policies;

use App\Models\Signalement;
use App\Models\User;

class SignalementPolicy
{
    public function view(User $user, Signalement $signalement): bool
    {
        return $this->owns($user, $signalement);
    }

    public function update(User $user, Signalement $signalement): bool
    {
        return $this->owns($user, $signalement)
            && $signalement->statut_validation === Signalement::STATUT_EN_ATTENTE;
    }

    public function delete(User $user, Signalement $signalement): bool
    {
        return $this->owns($user, $signalement)
            && $signalement->statut_validation === Signalement::STATUT_EN_ATTENTE;
    }

    public function relancerAnalyseImage(User $user, Signalement $signalement): bool
    {
        return $this->owns($user, $signalement);
    }

    private function owns(User $user, Signalement $signalement): bool
    {
        return $signalement->user_id === $user->id;
    }
}
