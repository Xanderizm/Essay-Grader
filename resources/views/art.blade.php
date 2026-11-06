{{--
    Dipanggil: @include('art', ['name' => 'hero', 'alt' => 'Deskripsi gambar'])
    Mencari public/images/{name}.svg|webp|png|jpg. Jika belum ada, memakai ilustrasi cadangan di bawah.
    Alt kosong berarti gambar hanya hiasan dan disembunyikan dari pembaca layar.
--}}
@php
    $alt   = $alt ?? '';
    $class = $class ?? '';
    $file  = null;

    foreach (['svg', 'webp', 'png', 'jpg'] as $ext) {
        if (is_file(public_path('images/' . $name . '.' . $ext))) {
            $file = $name . '.' . $ext;
            break;
        }
    }
@endphp
@if($file)
    <img src="{{ asset('images/' . $file) }}" class="art {{ $class }}" alt="{{ $alt }}" loading="lazy" decoding="async">
@else
    <svg class="art {{ $class }}" viewBox="0 0 240 200" fill="none" stroke="#1b1b1b" stroke-width="1.5"
         @if($alt !== '') role="img" aria-label="{{ $alt }}" @else aria-hidden="true" @endif>
        <ellipse cx="120" cy="104" rx="104" ry="38" transform="rotate(-18 120 104)" opacity=".55"/>
        <ellipse cx="120" cy="104" rx="86" ry="29" transform="rotate(-18 120 104)" opacity=".35"/>
        <rect x="76" y="38" width="88" height="118" rx="12" fill="#fff"/>
        <rect x="86" y="52" width="40" height="9" rx="4.5" fill="#c4f55d" stroke="none"/>
        <path d="M88 78h64M88 92h64M88 106h42" stroke-linecap="round"/>
        <circle cx="150" cy="132" r="17" fill="#c4f55d"/>
        <path d="M142 132l6 6 10-12" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M201 50l4 10 10 4-10 4-4 10-4-10-10-4 10-4z" fill="#1b1b1b" stroke="none"/>
        <path d="M44 120l3 7 7 3-7 3-3 7-3-7-7-3 7-3z" fill="#c4f55d"/>
        <circle cx="44" cy="58" r="7" fill="#1b1b1b" stroke="none"/>
        <circle cx="198" cy="152" r="5" fill="#c4f55d"/>
    </svg>
@endif
