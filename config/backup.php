<?php

return [
    // Jalur mysqldump. Biarkan "mysqldump" jika perintahnya dikenali di Laragon Terminal.
    'mysqldump' => env('MYSQLDUMP_PATH', 'mysqldump'),

    // Jumlah file cadangan terbaru yang disimpan
    'keep' => 14,
];