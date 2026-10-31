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

    /*
    |--------------------------------------------------------------------------
    | Activation de l'analyse de fiabilité (détection doublons/spam)
    |--------------------------------------------------------------------------
    |
    | Permet d'activer ou de désactiver l'analyse IA de fiabilité des
    | signalements. Réutilise la même clé et le même modèle que l'assistant.
    |
    */

    'analyse_enabled' => filter_var(env('ASSISTANT_IA_ANALYSE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Paramètres de détection des doublons géographiques
    |--------------------------------------------------------------------------
    |
    | rayon en mètres et fenêtre temporelle en heures pour la détection.
    |
    */

    'doublons_rayon_m'        => (int) env('ASSISTANT_IA_DOUBLONS_RAYON_M', 200),
    'doublons_fenetre_heures' => (int) env('ASSISTANT_IA_DOUBLONS_FENETRE_HEURES', 48),

    /*
    |--------------------------------------------------------------------------
    | Analyse automatique des images (Vision)
    |--------------------------------------------------------------------------
    |
    | Configuration du modèle de vision pour vérifier la pertinence des
    | photos téléversées par les citoyens.
    |
    */

    'vision_enabled' => filter_var(env('ASSISTANT_IA_VISION_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'vision_model'   => env('ASSISTANT_IA_VISION_MODEL', env('ASSISTANT_IA_MODEL', 'gemini-3.5-flash-lite')),
    'vision_timeout' => (int) env('ASSISTANT_IA_VISION_TIMEOUT', 30),

];

