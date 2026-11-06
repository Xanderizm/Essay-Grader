<?php

return [
    // open   = siapa saja boleh mendaftar
    // invite = pendaftaran perlu kode undangan (isi REGISTRATION_CODE)
    // closed = pendaftaran ditutup
    'mode' => env('REGISTRATION_MODE', 'open'),

    'code' => env('REGISTRATION_CODE'),
];
