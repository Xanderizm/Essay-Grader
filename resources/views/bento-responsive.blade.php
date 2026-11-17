{{--
    Responsivitas bersama untuk Statistik User (.ux) dan Statistik Admin (.adm).
    Dipakai: @include('bento-responsive') setelah @include('bento-glass').
    Desktop (>= 768px): grid bento 10 kolom. Di bawah 768px: semua grid menjadi 1 kolom.
--}}
<style>
    /* ---- Berlaku di semua ukuran: cegah apa pun meluber keluar kartu ---- */
    .ux-grid > *, .ux-stack > *, .adm-grid > *, .adm-cols3 > * { min-width: 0; max-width: 100%; }
    .ux .ux-card, .adm .adm-card { max-width: 100%; }
    .ux, .adm { max-width: 100%; }
    .ux svg, .adm svg { max-width: 100%; }
    .ux-kv > div, .adm-kv > div, .adm-row { gap: 10px; }
    .ux-kv dt, .adm-kv dt, .adm-row .k { min-width: 0; overflow-wrap: anywhere; }
    .ux-kv dd, .adm-kv dd, .adm-row b { text-align: right; }

    /* Tabel panjang: gulir horizontal di dalam kartu, tidak merusak tata letak */
    .ux-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; margin-top: 16px; max-width: 100%; }
    .ux-scroll .ux-table { margin-top: 0; min-width: 460px; }

    /* ---- Mobile & tablet portrait: 1 kolom penuh ---- */
    @media (max-width: 767px) {
        .ux { padding: 10px; border-radius: 18px; }
        .adm { padding: 8px; border-radius: 22px; }
        .ux-grid, .ux-stack, .adm-grid, .adm-cols3 { gap: 14px; }

        /* Perbaikan kartu saling menimpa: di 1 kolom, tinggi kartu mengikuti isinya (bukan dibagi rata) */
        body .ux .ux-grid .ux-stack { display: flex; flex-direction: column; height: auto; }
        body .ux .ux-grid .ux-stack > .ux-card,
        body .ux .ux-grid > .ux-card { flex: 0 0 auto; height: auto; min-height: 0; overflow: hidden; }
        body .ux .ux-grid .ux-card > .ux-frame,
        body .ux .ux-grid .ux-card > .ux-dist { margin-top: 14px; }
        body .ux .ux-grid .ux-card > .ux-meter,
        body .ux .ux-grid .ux-card > .ux-seg2 { margin-top: 14px; }

        .ux .ux-card { padding: 16px; }
        .adm .adm-card { padding: 14px; }
        body .ux .ux-grid .ux-card, body .adm .adm-card { border-radius: 22px; }

        .ux .ux-big { font-size: clamp(26px, 9vw, 34px); }
        .ux-sub { line-height: 1.5; }
        .ux-legend { gap: 4px 10px; }
        .ux-bars { height: 96px; }
        .ux-grow .ux-bars { min-height: 110px; }
        .ux-dist { gap: 6px; height: 130px; }
        .ux-dist > div { font-size: 10px; }
        .ux-dist b { font-size: 11.5px; }

        /* Admin */
        .adm-hd > div:last-child:not(:first-child) { text-align: left !important; }
        .adm-bar { flex-direction: column; align-items: stretch; }
        .adm-btns { justify-content: stretch; }
        .adm-btns form { flex: 1 1 30%; }
        .adm-btns .btn-pill { width: 100%; }
        .adm-stack { height: 160px; }
        .adm-kc .r2 { margin-top: 18px; }
        .adm-pills { max-width: 100%; }
        .adm-pills button { padding: 6px 9px; }
        .adm-act li { gap: 10px; }
    }

    @media (max-width: 380px) {
        .ux .ux-card, .adm .adm-card { padding: 12px; }
        .adm-kc .r1, .adm-kc .r3 { font-size: 9.5px; }
    }
</style>
