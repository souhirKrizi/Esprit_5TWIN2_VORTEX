<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Levée lorsque le service d'analyse IA de fiabilité est indisponible
 * (erreur réseau, quota, réponse invalide…).
 */
class AnalyseIaIndisponibleException extends RuntimeException {}

