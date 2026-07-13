<?php

return [
    'guest_cookie' => env('QUESTINVITE_GUEST_COOKIE', 'questinvite_guest'),
    'guest_session_days' => (int) env('QUESTINVITE_GUEST_SESSION_DAYS', 90),
    'default_access_expiry_days' => (int) env('QUESTINVITE_DEFAULT_ACCESS_EXPIRY_DAYS', 30),
    'attempt_limit' => (int) env('QUESTINVITE_ATTEMPT_LIMIT', 5),
    'attempt_window_minutes' => (int) env('QUESTINVITE_ATTEMPT_WINDOW_MINUTES', 15),
    'lock_minutes' => (int) env('QUESTINVITE_LOCK_MINUTES', 15),
    'cover_disk' => env('QUESTINVITE_COVER_DISK', env('FILESYSTEM_DISK', 'local')),
    'allow_demo_seeding' => (bool) env('ALLOW_DEMO_SEEDING', false),
];
