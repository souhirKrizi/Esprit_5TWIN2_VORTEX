<?php

namespace App\Services\Prevention;

use RuntimeException;

/**
 * La description de l'équipement n'a pas de sens : aucun conseil ne peut en être déduit.
 */
class DescriptionNonSignificative extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Cette description n’a pas de sens. Décrivez le vrai problème de l’équipement pour obtenir des conseils.');
    }
}
