<?php

return [
    'secret' => env('JWT_SECRET') ?: env('APP_KEY'),
    'algorithm' => env('JWT_ALGORITHM', 'HS256'),
    'ttl' => (int) env('JWT_TTL', 120),
    'remember_ttl' => (int) env('JWT_REMEMBER_TTL', 10080),
    'cookie' => env('JWT_COOKIE', 'school_access_token'),
];
