<?php

return [

    // Bobot tiap parameter. Sama rata secara bawaan.
    // Contoh bobot berbeda: content 0.5, logic 0.25, structure 0.25
    'weights' => [
        'content'   => 1,   // Pemahaman Konten
        'logic'     => 1,   // Logika Argumen
        'structure' => 1,   // Struktur Tulisan
    ],

    // Batas nilai akhir minimal untuk tiap kategori. Urutkan dari yang tertinggi.
    'categories' => [
        'Sangat Baik' => 85,
        'Baik'        => 70,
        'Cukup'       => 55,
        'Kurang'      => 0,
    ],

];