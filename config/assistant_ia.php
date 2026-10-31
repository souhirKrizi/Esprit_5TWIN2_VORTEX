<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Activation de l'assistant IA
    |--------------------------------------------------------------------------
    |
    | Permet d'activer ou de désactiver globalement l'assistant de rédaction.
    |
    */

    'enabled' => filter_var(env('ASSISTANT_IA_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Fournisseur IA par défaut (driver)
    |--------------------------------------------------------------------------
    |
    | Le fournisseur actif utilisé pour reformuler les descriptions.
    | Exemples supportés ou préparés : 'gemini', 'anthropic'.
    |
    */

    'driver' => env('ASSISTANT_IA_DRIVER', 'gemini'),

    /*
    |--------------------------------------------------------------------------
    | Clé d'API du fournisseur
    |--------------------------------------------------------------------------
    |
    | Clé d'authentification pour le service IA, lue depuis le fichier .env.
    |
    */

    'key' => env('ASSISTANT_IA_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Modèle IA
    |--------------------------------------------------------------------------
    |
    | Nom du modèle à utiliser avec le fournisseur (ex: gemini-1.5-flash).
    |
    */

    'model' => env('ASSISTANT_IA_MODEL', 'gemini-3.5-flash-lite'),

    /*
    |--------------------------------------------------------------------------
    | Délai d'attente maximal (timeout en secondes)
    |--------------------------------------------------------------------------
    |
    | Temps maximum alloué pour la requête vers le service IA avant abandon.
    |
    */

    'timeout' => (int) env('ASSISTANT_IA_TIMEOUT', 15),

];

