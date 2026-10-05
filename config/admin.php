<?php

return [
    // No fallback: a hardcoded default silently provisions a public admin identity.
    // Fail loudly in config instead.
    'email' => env('ADMIN_EMAIL'),
];
