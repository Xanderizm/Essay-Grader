<?php

return [
    // Ujian yang tidak aktif dan tanpa aktivitas lebih dari N hari dihapus otomatis.
    // Kosongkan RETENTION_DAYS di .env untuk menonaktifkan.
    'days' => env('RETENTION_DAYS'),
];