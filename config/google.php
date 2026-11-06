<?php

return [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),

    // Alamat balik untuk menghubungkan Google Form (izin baca Form)
    'redirect_uri'  => env('GOOGLE_REDIRECT_URI', 'http://127.0.0.1:8000/google/callback'),

    // Alamat balik untuk "Masuk dengan Google" (hanya nama dan email)
    'login_redirect_uri' => env('GOOGLE_LOGIN_REDIRECT_URI', 'http://127.0.0.1:8000/auth/google/callback'),

    'scopes' => [
        'openid',
        'email',
        'https://www.googleapis.com/auth/forms.body.readonly',
        'https://www.googleapis.com/auth/forms.responses.readonly',
    ],
];
