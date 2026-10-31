<?php

return [
    // Génération des conseils pour les équipements sensibles (la clé reste dans le fichier .env).
    'groq' => [
        'cle' => env('GROQ_API_KEY'),
        'modele' => env('GROQ_MODEL', 'openai/gpt-oss-120b'),
        'url' => 'https://api.groq.com/openai/v1/chat/completions',
    ],
];
