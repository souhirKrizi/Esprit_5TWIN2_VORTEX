<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Levée lorsque le service d'analyse d'images par IA est indisponible
 * (clé absente, erreur réseau, quota, format inattendu…).
 */
class AnalyseImageIndisponibleException extends RuntimeException {}

