<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', '/', 'uploads/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'https://your-frontend-domain.com'),
        'http://localhost:5173',
        'http://localhost:3000',
        'http://localhost:4173',
        'https://mamabazar.vercel.app',
        'https://mama-bazar.vercel.app',
        'https://ghorerbazar-five.vercel.app',
    ],

    'allowed_origins_patterns' => [
        '#^https?://.*\.vercel\.app$#',
        '#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#',
    ],

    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept'],

    'exposed_headers' => ['Cross-Origin-Resource-Policy'],

    'max_age' => 0,

    'supports_credentials' => true,

];
