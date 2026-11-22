@php
    $part = $part ?? 'header';

    $mlSteps = [
        ['Langkah 1 &middot; Input', 'Hubungkan <mark>Google Form</mark>', 'Tempelkan link Google Form Anda dari mode edit untuk memulai integrasi sistem.',
            '<div class="ml-hw-row"><b>Link Form</b><span class="mono">mode edit</span></div><div class="ml-hw-field">docs.google.com/forms/d/1aB&hellip;/edit<span class="ml-caret"></span></div><span class="ml-hw-btn">Hubungkan</span>'],
        ['Langkah 2 &middot; Rubrik', 'Atur <mark>Kunci &amp; Rubrik</mark>', 'Tentukan parameter penilaian dan aktifkan ujian dengan satu klik.',
            '<div class="ml-hw-row"><b>Penilaian aktif</b><span class="ml-hw-sw"></span></div><div class="ml-hw-list"><div>Pemahaman Konten <span>40%</span></div><div>Logika Argumen <span>30%</span></div><div>Struktur Tulisan <span>30%</span></div></div>'],
        ['Langkah 3 &middot; Backend', 'Penangkapan Otomatis <mark>Backend</mark>', 'System worker (schedule:work) siaga 24/7 menarik jawaban siswa secara real-time.',
            '<div class="ml-hw-row"><b>schedule:work</b><span class="mono">tiap 1 menit</span></div><div class="ml-hw-list"><div>Jawaban baru ditarik <span>24</span></div><div>Antrean penilaian <span>siaga</span></div></div>'],
        ['Langkah 4 &middot; AI', 'Pemindaian <mark>AI Cerdas</mark>', 'Modul AI memindai makna kata kunci dan menghitung relevansi esai terhadap rubrik.',
            '<div class="ml-hw-row"><b>Relevansi terhadap rubrik</b><span class="mono">92%</span></div><div class="ml-hw-list"><div>Kata kunci cocok <span>7 / 8</span></div><div>Parafrase dikenali <span>3</span></div></div>'],
        ['Langkah 5 &middot; Tinjau', 'Peninjauan &amp; <mark>Koreksi Manual</mark>', 'Hasil nilai otomatis ditampilkan transparan dan pengajar bebas menyesuaikan nilai kapan saja.',
            '<div class="ml-hw-row"><b>Nilai akhir (0-100)</b><span class="mono">dapat diedit</span></div><div class="ml-hw-list"><div>Rina A. <span>86</span></div><div>Dimas P. <span class="ml-edit">84</span></div><div>Salsa M. <span>91</span></div></div>'],
        ['Langkah 6 &middot; Hasil', 'Grafik &amp; <mark>Unduh Excel</mark>', 'Lihat statistik performa siswa via chart visual dan unduh rekapitulasi nilai ke format Excel.',
            '<div class="ml-hw-row"><b>Rekap nilai kelas</b><span class="mono">.csv</span></div><span class="ml-hw-btn lime">Unduh Excel</span>'],
    ];
    $mlCaps   = ['Microsoft Excel' => 'Export .csv', 'Akun Google' => 'OAuth langsung', 'Google Forms' => 'Mode edit link', 'Console groq API' => 'Penyedia LLM'];
    $mlTone   = ['dk', 'lm', ''];
    $mlScore  = [88, 90, 85];
@endphp

@once
@push('styles')
<style>
    /* ===== Landing mobile (desain Stitch): hanya tampil <= 820px ===== */
    .ml-anchor { display: block; height: 0; }
    .ml-header, .ml-sec, .ml-dock, .ml-sheet, .ml-overlay { display: none; }

    @media (max-width: 820px) {
        body:has(.ml-header) { --ml-bd: var(--ink); --ml-sh: var(--ink); overflow-x: clip; padding-bottom: calc(96px + env(safe-area-inset-bottom, 0px)); }
        html[data-theme="dark"] body:has(.ml-header) { --ml-bd: rgba(236, 236, 232, .6); --ml-sh: rgba(236, 236, 232, .28); }
        html { scroll-behavior: smooth; }
        html.ml-lock { overflow: hidden; }

        /* Header global diganti header khusus landing; versi desktop disembunyikan */
        body:has(.ml-header) .topbar { display: none; }
        #isi:has(.ml-header) .brands, #isi:has(.ml-header) .hiw, #isi:has(.ml-header) .aspek { display: none; }

        /* ---------- Header + hamburger ---------- */
        .ml-header { display: block; position: sticky; top: 0; z-index: 40; padding: 10px 0; background: color-mix(in srgb, var(--paper) 96%, transparent); }
        .ml-bar { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .ml-brand { display: inline-flex; align-items: center; gap: 9px; min-width: 0; color: var(--ink); text-decoration: none !important; }
        .ml-logo { width: 38px; height: 38px; flex-shrink: 0; display: grid; place-items: center; background: var(--lime); border: 1.5px solid var(--ml-bd); border-radius: 10px; box-shadow: 2px 2px 0 var(--ml-sh); }
        .ml-logo img { width: 22px; height: 22px; display: block; }
        .ml-brand b { font: 800 17px var(--display); letter-spacing: -.01em; white-space: nowrap; }
        .ml-brand i { font: 800 10px/1 var(--mono); font-style: normal; padding: 3px 6px; background: var(--lime); color: var(--dark-text); border: 1.5px solid var(--ml-bd); border-radius: 5px; }
        .ml-actions { display: flex; align-items: center; gap: 8px; }
        .ml-actions .theme-btn, .ml-burger { width: 42px; height: 42px; min-height: 42px; }
        .ml-burger { display: grid; place-items: center; padding: 0; background: var(--lime); color: var(--dark-text); border: 1.5px solid var(--ml-bd); border-radius: 11px; box-shadow: 2px 2px 0 var(--ml-sh); }
        .ml-burger:active { transform: translate(2px, 2px); box-shadow: none; }

        .ml-overlay { display: block; position: fixed; inset: 0; z-index: 70; background: rgba(0, 0, 0, .45); -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px); }
        .ml-sheet { display: block; position: fixed; left: 0; right: 0; bottom: 0; z-index: 71; max-width: 520px; margin: 0 auto; max-height: 86vh; max-height: 86dvh; overflow-y: auto; overscroll-behavior: contain;
            padding: 18px 16px calc(18px + env(safe-area-inset-bottom, 0px)); background: var(--paper); border: 3px solid var(--ml-bd); border-bottom: 0; border-radius: 26px 26px 0 0; }
        .ml-t-enter { transition: transform .32s cubic-bezier(.22, 1, .36, 1), opacity .2s; }
        .ml-t-leave { transition: transform .22s ease-in, opacity .2s; }
        .ml-t-from { transform: translateY(100%); opacity: .6; }
        .ml-sheet-head { display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; margin-bottom: 14px; border-bottom: 2px solid var(--ml-bd); }
        .ml-chip { display: inline-block; padding: 4px 9px; background: var(--lime); color: var(--dark-text); border: 1.5px solid var(--ml-bd); border-radius: 6px; font: 800 11px/1.1 var(--mono); text-transform: uppercase; letter-spacing: .02em; }
        .ml-x { width: 42px; height: 42px; min-height: 42px; padding: 0; display: grid; place-items: center; background: var(--surface); color: var(--ink); border: 1.5px solid var(--ml-bd); border-radius: 10px; box-shadow: 2px 2px 0 var(--ml-sh); font-size: 16px; font-weight: 800; }
        .ml-nav { display: grid; gap: 10px; }
        .ml-nav a { display: flex; align-items: center; justify-content: space-between; gap: 10px; min-height: 54px; padding: 0 14px; background: var(--surface); color: var(--ink);
            border: 1.5px solid var(--ml-bd); border-radius: 14px; box-shadow: 2px 2px 0 var(--ml-sh); font: 800 15px var(--display); text-decoration: none !important; }
        .ml-nav a > span { display: inline-flex; align-items: center; gap: 10px; }
        .ml-nav svg, .ml-dk svg { width: 20px; height: 20px; stroke: currentColor; fill: none; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }
        .ml-nav a:active { background: var(--lime); color: var(--dark-text); transform: translate(2px, 2px); box-shadow: none; }
        .ml-sheet-cta { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 16px; padding-top: 14px; border-top: 2px solid var(--ml-bd); }
        .ml-sheet-cta a { display: flex; align-items: center; justify-content: center; min-height: 52px; background: var(--surface); color: var(--ink); border: 1.5px solid var(--ml-bd); border-radius: 14px;
            box-shadow: 2px 2px 0 var(--ml-sh); font: 800 14px var(--display); text-decoration: none !important; }
        .ml-sheet-cta a.pri { background: var(--lime); color: var(--dark-text); }
        .ml-sheet-cta a.only { grid-column: 1 / -1; }
        .ml-sheet-links { display: flex; gap: 16px; margin-top: 14px; font: 700 12px var(--mono); }
        .ml-sheet-links a { color: var(--ink); text-decoration: underline; text-underline-offset: 3px; }

        /* ---------- Hero (memakai ulang .hero milik proyek) ---------- */
        #isi:has(.ml-header) .hero { display: block; padding: 6px 0 4px; }
        #isi:has(.ml-header) .hero > div:first-child::before {
            content: "\2605  AI GRADING ASSISTANT  \2605"; display: block; align-self: flex-start; width: fit-content; max-width: 100%; margin-bottom: 12px; padding: 5px 12px; transform: rotate(-1deg);
            background: var(--lime); color: var(--dark-text); border: 1.5px solid var(--ml-bd); border-radius: 8px; box-shadow: 2px 2px 0 var(--ml-sh); font: 800 11px/1 var(--mono); letter-spacing: .02em; }
        #isi:has(.ml-header) .hero-eyebrow { display: none; }
        #isi:has(.ml-header) .hero-actions .btn[href="#ml-alur"], #isi:has(.ml-header) .hero-actions .btn[href="#cara-kerja"] { display: inline-flex; align-items: center; }
        #isi:has(.ml-header) .hero h1 { font-size: clamp(30px, 9vw, 38px); line-height: 1.1; margin: 0 0 10px; }
        #isi:has(.ml-header) .hero .st-main { display: block; }
        #isi:has(.ml-header) .hero .st-w.is-hl { 
                    background: var(--lime); 
                    color: var(--dark-text); 
                    display: inline-block;
                    line-height: 1.3;
                    border: 2px solid var(--ml-bd); 
                    border-radius: 6px; 
                    padding: 0.05em 0.22em; 
                    margin: 0.08em 0.02em;
                    box-shadow: 2px 2px 0 var(--ml-sh);
                    -webkit-box-decoration-break: clone;
                    box-decoration-break: clone;
                }
        #isi:has(.ml-header) .hero .st-sub { display: block; margin-top: 12px; font: 800 16px/1.3 var(--display); }
        #isi:has(.ml-header) .hero .st-sub::before { content: ""; display: inline-block; width: 10px; height: 10px; margin-right: 8px; vertical-align: middle; border-radius: 50%; background: var(--ink); }
        #isi:has(.ml-header) .hero .lead { display: block; width: 100%; max-width: none; box-sizing: border-box; margin: 14px 0 18px; padding: 12px; background: var(--surface); border: 1.5px solid var(--ml-bd); border-radius: 14px; box-shadow: 2px 2px 0 var(--ml-sh); font-size: 12.5px; line-height: 1.55; }
        #isi:has(.ml-header) .hero-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 0 0 22px; }
        #isi:has(.ml-header) .hero-actions .btn { width: 100%; min-height: 48px; justify-content: center; text-align: center; font-size: 13px; border: 1.5px solid var(--ml-bd); border-radius: 12px; box-shadow: 3px 3px 0 var(--ml-sh); }
        #isi:has(.ml-header) .hero-actions .btn:first-child { grid-column: 1 / -1; min-height: 52px; background: #121214; color: var(--lime); box-shadow: 4px 4px 0 var(--ml-sh); }
        #isi:has(.ml-header) .hero-actions .btn:nth-child(2):last-child { grid-column: 1 / -1; }
        #isi:has(.ml-header) .hero-actions .btn[href="#ml-alur"], #isi:has(.ml-header) .hero-actions .btn[href="#cara-kerja"] { background: var(--amber); color: var(--dark-text); }
        #isi:has(.ml-header) .hero > .hm { max-width: 100%; min-width: 0; padding: 10px; overflow: hidden; background: var(--surface); border: 3px solid var(--ml-bd); border-radius: 16px; box-shadow: 4px 4px 0 var(--ml-sh); }
        #isi:has(.ml-header) .hero > .hm::before { content: "ALUR 4 TAHAP"; display: inline-block; margin: 0 0 10px; padding: 4px 9px; background: var(--lime); color: var(--dark-text); border: 1.5px solid var(--ml-bd); border-radius: 6px; font: 800 11px/1.1 var(--mono); letter-spacing: .04em; }
        #isi:has(.ml-header) .hero > .hm .hm-stage { overflow: hidden; background: var(--mist); border: 1.5px solid var(--ml-bd); border-radius: 12px; }
        #isi:has(.ml-header) .hm-legend { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 2px solid var(--ml-bd); }
        #isi:has(.ml-header) .hm-chip { width: 100%; min-height: 44px; padding: 6px 10px 6px 6px; text-align: left; border: 1.5px solid var(--ml-bd); border-radius: 10px; box-shadow: 2px 2px 0 var(--ml-sh); font: 800 11px/1.2 var(--mono); }
        #isi:has(.ml-header) .hm-chip[aria-pressed="true"] { background: var(--lime); color: #1b1b1b; }

        /* ---------- Bagian baru ---------- */
        .ml-sec { display: block; margin-top: 30px; scroll-margin-top: 76px; }
        .ml-label { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; font: 800 11px/1 var(--mono); text-transform: uppercase; letter-spacing: .04em; }
        .ml-label::before { content: ""; width: 10px; height: 10px; border-radius: 2px; background: var(--ink); }
        .ml-sec-head h2 { margin: 8px 0 6px; font: 800 24px/1.15 var(--display); letter-spacing: -.01em; }
        .ml-sec-head p { margin: 0; font-size: 13px; line-height: 1.55; color: var(--muted); }

        .ml-tools { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .ml-tool { display: flex; align-items: center; gap: 10px; min-width: 0; padding: 10px; background: var(--surface); border: 1.5px solid var(--ml-bd); border-radius: 14px; box-shadow: 2px 2px 0 var(--ml-sh); }
        .ml-tool.hl { background: var(--lime); color: var(--dark-text); }
        .ml-tool-ic { width: 34px; height: 34px; flex-shrink: 0; display: grid; place-items: center; overflow: hidden; background: #fff; border: 1.5px solid var(--ml-bd); border-radius: 9px; }
        .ml-tool-ic img { width: 22px; height: 22px; display: block; object-fit: contain; }
        .ml-tool-ic svg { width: 20px; height: 20px; fill: none; stroke: #1b1b1b; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
        .ml-tool b { display: block; font: 800 12.5px/1.2 var(--display); }
        .ml-tool small { display: block; margin-top: 2px; font: 700 10px var(--mono); color: var(--muted); }
        .ml-tool.hl small { color: var(--dark-text); opacity: .75; }

        /* Cara kerja: pager + carousel parallax card stack */
        .ml-par::-webkit-scrollbar { display: none; }
        .ml-par { display: flex; overflow-x: auto; padding: 10px 9% 22px; scroll-snap-type: x mandatory; scrollbar-width: none; overscroll-behavior-x: contain; -webkit-overflow-scrolling: touch; }
        .ml-step { flex: 0 0 82%; margin-inline: -3%; scroll-snap-align: center; padding: 16px; background: var(--surface); border: 3px solid var(--ml-bd); border-radius: 20px; box-shadow: 4px 4px 0 var(--ml-sh);
            transform: translateX(calc(var(--d, 0) * -7%)) scale(calc(1 - var(--a, 0) * .13));
            filter: blur(calc(var(--a, 0) * 2.4px)); opacity: calc(1 - var(--a, 0) * .4); will-change: transform, filter; }
        .ml-step h3 { margin: 10px 0 6px; font: 800 21px/1.15 var(--display); }
        .ml-step h3 mark { 
            display: inline-block;
            line-height: 1.3;
            padding: 0.05em 0.35em; 
            margin: 0.08em 0.02em;
            background: var(--lime); 
            color: var(--dark-text); 
            border: 1.5px solid var(--ml-bd); 
            border-radius: 6px; 
            box-shadow: 2px 2px 0 var(--ml-sh); 
            -webkit-box-decoration-break: clone; 
            box-decoration-break: clone; 
        }
        .ml-step > p { margin: 0 0 12px; font-size: 13px; line-height: 1.5; color: var(--muted); }
        .ml-hw { display: grid; gap: 8px; padding: 10px; background: var(--mist); border: 1.5px solid var(--ml-bd); border-radius: 12px; font-size: 12px; transform: translateX(calc(var(--d, 0) * 14px)); }
        .ml-hw-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; font: 800 12px var(--display); }
        .ml-hw-row .mono { padding: 2px 6px; background: var(--surface); border: 1.5px solid var(--ml-bd); border-radius: 5px; font: 700 10.5px var(--mono); }
        .ml-hw-field { display: flex; align-items: center; justify-content: space-between; gap: 6px; padding: 8px 10px; background: var(--surface); border: 1.5px solid var(--ml-bd); border-radius: 9px; font: 700 11px var(--mono); overflow: hidden; white-space: nowrap; }
        .ml-caret { width: 8px; height: 14px; flex-shrink: 0; background: var(--lime); border: 1.5px solid var(--ml-bd); animation: ml-blink 1s steps(2) infinite; }
        .ml-hw-btn { display: grid; place-items: center; min-height: 36px; background: var(--surface); border: 1.5px solid var(--ml-bd); border-radius: 9px; font: 800 12px var(--display); }
        .ml-hw-btn.lime { background: var(--lime); color: var(--dark-text); }
        .ml-hw-sw { width: 34px; height: 18px; position: relative; background: var(--lime); border: 1.5px solid var(--ml-bd); border-radius: 99px; }
        .ml-hw-sw::after { content: ""; position: absolute; top: 1.5px; right: 2px; width: 12px; height: 12px; background: #1b1b1b; border-radius: 50%; }
        .ml-hw-list { display: grid; gap: 4px; }
        .ml-hw-list > div { display: flex; justify-content: space-between; gap: 8px; padding: 5px 8px; background: var(--surface); border: 1.5px solid var(--ml-bd); border-radius: 8px; font: 700 11.5px var(--display); }
        .ml-hw-list span { font: 800 11px var(--mono); }
        .ml-edit { padding: 0 5px; background: var(--lime); color: var(--dark-text); border-radius: 4px; }
        @keyframes ml-blink { 50% { opacity: 0; } }

        /* Aspek penilaian: carousel fluid morphing + elastic snap */
        .ml-scale { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin: 12px 0 4px; font: 700 11px var(--mono); }
        .ml-ela { position: relative; display: block; width: auto; min-width: 0; overflow: hidden; margin: 0 -4px; padding: 8px 4px 16px; touch-action: pan-y pinch-zoom; cursor: grab; user-select: none; -webkit-user-select: none; -webkit-tap-highlight-color: transparent; overscroll-behavior-x: contain; outline-offset: 4px; pointer-events: auto; }
        .ml-ela.is-drag { cursor: grabbing; }
        .ml-ela-track { display: flex; width: 100%; gap: 12px; will-change: transform; pointer-events: auto; }
        .ml-ela-card { pointer-events: auto; touch-action: pan-y pinch-zoom; }
        .ml-ela-card { flex: 0 0 84%; display: flex; flex-direction: column; justify-content: space-between; gap: 16px; min-height: 250px; padding: 16px; background: var(--surface); color: var(--ink);
            border: 3px solid var(--ml-bd); border-radius: 18px; box-shadow: 4px 4px 0 var(--ml-sh); transform-origin: 50% 100%; will-change: transform; }
        .ml-ela-card.dk { background: var(--card-dark-bg); color: var(--card-dark-fg); }
        .ml-ela-card.lm { background: var(--lime); color: var(--dark-text); }
        .ml-ela-top { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .ml-no { width: 30px; height: 30px; display: grid; place-items: center; background: var(--lime); color: var(--dark-text); border: 1.5px solid var(--ml-bd); border-radius: 9px; font: 800 13px var(--mono); }
        .ml-tag { padding: 3px 8px; background: var(--lime); color: var(--dark-text); border: 1.5px solid var(--ml-bd); border-radius: 6px; font: 800 10.5px var(--mono); }
        .ml-ela-card.lm .ml-tag { background: #fff; }
        .ml-ela-card h3 { margin: 12px 0 6px; font: 800 20px/1.15 var(--display); }
        .ml-ela-card p { margin: 0; font-size: 13px; line-height: 1.5; opacity: .85; }
        .ml-score { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font: 700 11px var(--mono); }
        .ml-score b { padding: 2px 8px; background: var(--lime); color: var(--dark-text); border: 1.5px solid var(--ml-bd); border-radius: 6px; font-weight: 800; }
        .ml-rail { position: relative; height: 10px; border-radius: 99px; background: rgba(127, 127, 127, .3); }
        .ml-rail i { position: absolute; inset: 0 auto 0 0; width: var(--v); background: var(--lime); border: 1.5px solid var(--ml-bd); border-radius: inherit; }
        .ml-rail-lb { display: flex; justify-content: space-between; margin-top: 6px; font: 700 10px var(--mono); opacity: .8; }
        .ml-dots { display: flex; justify-content: center; gap: 8px; margin-top: 2px; }
        .ml-dot { position: relative; width: 12px; height: 12px; min-height: 12px; padding: 0; background: var(--mist); border: 1.5px solid var(--ml-bd); border-radius: 99px; box-shadow: none; transition: width .35s cubic-bezier(.34, 1.56, .64, 1), background .2s; }
        .ml-dot::after { content: ""; position: absolute; inset: -12px -6px; }
        .ml-dot[aria-current="true"] { width: 30px; background: var(--lime); }

        /* ---------- Band reveal (memakai ulang .band + dua robot grader-bot) ---------- */
        #isi:has(.ml-header) .band { display: block; margin: 30px 0 8px; padding: 20px; background: var(--lime); color: var(--dark-text); border: 3px solid var(--ml-bd); border-radius: 24px; box-shadow: 4px 4px 0 var(--ml-sh); overflow: hidden; text-align: left; }
        #isi:has(.ml-header) .band > div:first-child::before { content: "MULAI PENILAIAN PERTAMA"; display: inline-block; margin-bottom: 12px; padding: 4px 9px; background: #fff; color: #1b1b1b; border: 1.5px solid var(--ml-bd); border-radius: 6px; box-shadow: 2px 2px 0 var(--ml-sh); font: 800 11px/1.1 var(--mono); }
        #isi:has(.ml-header) .band h2 { margin: 0 0 8px; font: 800 24px/1.2 var(--display); color: var(--dark-text); }
        #isi:has(.ml-header) .band p { margin: 0 0 16px; max-width: none; font-size: 12.5px; line-height: 1.55; color: var(--dark-text); opacity: .85; }
        #isi:has(.ml-header) .band .btn { width: 100%; min-height: 50px; justify-content: center; text-align: center; background: #121214; color: #fff; border: 1.5px solid var(--ml-bd); border-radius: 12px; box-shadow: 4px 4px 0 var(--ml-sh); }
        #isi:has(.ml-header) .band .btn .spec-label::after { content: " \2192"; color: var(--lime); font-family: var(--mono); }
        #isi:has(.ml-header) .band > .gb { box-sizing: border-box; max-width: 300px; margin: 18px auto 0; padding: 12px; background: var(--surface); border: 1.5px solid var(--ml-bd); border-radius: 16px; box-shadow: 2px 2px 0 var(--ml-sh); }

        /* ---------- Dock bawah (zona jempol) ---------- */
        .ml-dock { display: flex; position: fixed; left: 12px; right: 12px; bottom: calc(12px + env(safe-area-inset-bottom, 0px)); z-index: 44; max-width: 480px; margin: 0 auto; align-items: center; justify-content: space-between; gap: 4px;
            padding: 8px; background: var(--paper); border: 3px solid var(--ml-bd); border-radius: 20px; box-shadow: 4px 4px 0 var(--ml-sh); }
        .ml-dk { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; min-width: 58px; min-height: 50px; padding: 4px 8px; color: var(--ink);
            border: 1.5px solid transparent; border-radius: 13px; font: 800 9.5px var(--mono); text-transform: uppercase; text-decoration: none !important; transition: background .15s, transform .12s; }
        .ml-dk svg { stroke-width: 2.3; }
        .ml-dk.on { background: var(--lime); color: var(--dark-text); border-color: var(--ml-bd); }
        .ml-dk:active { transform: scale(.94); }
        .ml-dk-cta { display: inline-flex; align-items: center; gap: 6px; min-height: 50px; margin-left: 4px; padding: 0 18px; background: #121214; color: var(--lime); border: 1.5px solid var(--ml-bd); border-radius: 13px; font: 800 13px var(--display); text-decoration: none !important; }
        /* ---------- FIX MOBILE: latar menyatu (gradient) + tidak ada tepi putih / komponen terpotong ----------
           Seluruhnya di dalam @media (max-width: 820px) dan hanya aktif bila landing mobile dipakai,
           jadi tampilan desktop tidak tersentuh. */

        /* 1) Latar full-bleed: gradient halus dari tint abu-lime di atas menuju warna halaman (tanpa hard edge) */
        body:has(.ml-header) {
            background-image:
                radial-gradient(120% 340px at 50% 0, color-mix(in srgb, var(--lime) 14%, transparent) 0%, transparent 100%),
                linear-gradient(180deg,
                    color-mix(in srgb, var(--lime) 7%, var(--paper)) 0%,
                    color-mix(in srgb, var(--lime) 4%, var(--paper)) 380px,
                    var(--paper) 760px);
            background-repeat: no-repeat;
            background-size: 100% 100%, 100% 100%;
        }

        /* 2) Kontainer/hero tidak lagi membuat kotak latar sendiri (penyebab tepi putih kaku) dan tidak memotong isi */
        #isi:has(.ml-header),
        #isi:has(.ml-header) .hero { background: transparent; background-image: none; overflow: visible; clip-path: none; }
        #isi:has(.ml-header) .hero { border-radius: 0; box-shadow: none; border-color: transparent; }

        /* 3) Header sticky: tembus pandang + blur, supaya gradient latar terlihat sampai paling atas */
        body:has(.ml-header) .ml-header { background: color-mix(in srgb, var(--paper) 55%, transparent); -webkit-backdrop-filter: blur(10px); backdrop-filter: blur(10px); }

        /* 4) Dock: teks tombol "Mulai" tetap terbaca di atas tombol gelap */
        .ml-dock a.ml-dk-cta { color: var(--lime); }

        /* Dark mode: kotak sorot judul hero disamakan dengan Light Mode */
        html[data-theme="dark"] #isi:has(.ml-header) .hero .st-w.is-hl {
            color: var(--dark-text, #1b1b1b);
            -webkit-text-stroke-color: var(--dark-text, #1b1b1b); /* kontur gelap, bukan putih */
            border-color: var(--lime);                            /* tepi menyatu dengan kotak, tidak terlihat patah */
            box-shadow: 2px 2px 0 rgba(236, 236, 232, .28);
            white-space: nowrap;                                  /* tidak terpotong di tengah kata */
            margin-block: .06em;                                  /* jarak aman jika "lebih" / "cepat." turun baris */
            -webkit-box-decoration-break: clone;
            box-decoration-break: clone;
        }

    }

    @media (prefers-reduced-motion: reduce) {
        .ml-t-enter, .ml-t-leave { transition: none; }
        .ml-step { filter: none; transform: none; opacity: 1; }
        .ml-hw { transform: none; }
        .ml-caret { animation: none; }
    }
</style>
@endpush
@endonce

{{-- ======================= HEADER + LACI HAMBURGER ======================= --}}
@if($part === 'header')
<div x-data="{ open: false }" style="display: contents"
     @keydown.escape.window="open = false"
     x-effect="document.documentElement.classList.toggle('ml-lock', open)">
    <span class="ml-anchor" id="ml-top"></span>

    <header class="ml-header">
        <div class="ml-bar">
            <a class="ml-brand" href="{{ url('/') }}" aria-label="Essay Grader, beranda">
                <span class="ml-logo"><img src="{{ asset('favicon.svg') }}" alt="" width="22" height="22"></span>
                <b>Essay Grader</b><i>AI</i>
            </a>
            <div class="ml-actions">
                @include('theme-toggle', ['tid' => null])
                <button type="button" class="ml-burger" @click="open = true" :aria-expanded="open" aria-controls="ml-sheet" aria-label="Buka menu">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
        </div>
    </header>

    <div class="ml-overlay" x-show="open" x-cloak x-transition.opacity.duration.200ms @click="open = false" aria-hidden="true"></div>

    <div class="ml-sheet" id="ml-sheet" x-show="open" x-cloak role="dialog" aria-modal="true" aria-label="Navigasi utama"
         x-transition:enter="ml-t-enter" x-transition:enter-start="ml-t-from" x-transition:enter-end=""
         x-transition:leave="ml-t-leave" x-transition:leave-start="" x-transition:leave-end="ml-t-from">
        <div class="ml-sheet-head">
            <span class="ml-chip">Navigasi utama</span>
            <button type="button" class="ml-x" @click="open = false" aria-label="Tutup menu">&#10005;</button>
        </div>
        <nav class="ml-nav" aria-label="Menu utama">
            <a href="#ml-top" @click="open = false"><span><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>Beranda</span></a>
            <a href="#ml-alur" @click="open = false"><span><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M10 8l6 4-6 4z"/></svg>Alur &amp; cara kerja</span></a>
            <a href="#ml-platform" @click="open = false"><span><svg viewBox="0 0 24 24"><path d="M9 3v4M15 3v4"/><path d="M7 7h10v4a5 5 0 0 1-10 0V7z"/><path d="M12 16v5"/></svg>Terhubung dengan</span></a>
            <a href="#ml-aspek" @click="open = false"><span><svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>Aspek penilaian</span></a>
        </nav>
        <div class="ml-sheet-cta">
            <a href="{{ route('login') }}" class="{{ $signupOpen ? '' : 'only' }}">Masuk</a>
            @if($signupOpen)<a href="{{ route('register') }}" class="pri">Daftar akun</a>@endif
        </div>
        <div class="ml-sheet-links"><a href="{{ route('privacy') }}">Kebijakan privasi</a><a href="{{ route('terms') }}">Ketentuan layanan</a></div>
    </div>
</div>
@endif

{{-- ======================= PLATFORM + CARA KERJA + ASPEK ======================= --}}
@if($part === 'sections')
<section class="ml-sec" id="ml-platform" aria-label="Terhubung dengan">
    <p class="ml-label">Terhubung dengan platform</p>
    <div class="ml-tools">
        @foreach(array_keys($mlCaps) as $nm)
            @php $t = collect($tools)->firstWhere(0, $nm); @endphp
            @if($t)
                <div class="ml-tool {{ $nm === 'Console groq API' ? 'hl' : '' }}">
                    <span class="ml-tool-ic">
                        @if(file_exists(public_path('images/' . $t[1])))
                            <img src="{{ asset('images/' . $t[1]) }}" alt="" loading="lazy" decoding="async" draggable="false">
                        @else
                            <svg viewBox="0 0 24 24">{!! $t[2] !!}</svg>
                        @endif
                    </span>
                    <span><b>{{ $nm }}</b><small>{{ $mlCaps[$nm] }}</small></span>
                </div>
            @endif
        @endforeach
    </div>
</section>

<section class="ml-sec" id="ml-alur" aria-labelledby="ml-alur-h">
    <div class="ml-sec-head">
        <span class="ml-chip">Cara kerja</span>
        <h2 id="ml-alur-h">Biarkan sistem bekerja otomatis.</h2>
        <p>Geser kartu untuk melihat enam langkah dari Google Form sampai file Excel.</p>
    </div>
    <div class="ml-par" role="group" aria-roledescription="carousel" aria-label="Langkah cara kerja">
        @foreach($mlSteps as $i => $s)
            <article class="ml-step" aria-label="Langkah {{ $i + 1 }} dari {{ count($mlSteps) }}">
                <span class="ml-chip">{!! $s[0] !!}</span>
                <h3>{!! $s[1] !!}</h3>
                <p>{{ $s[2] }}</p>
                <div class="ml-hw" aria-hidden="true">{!! $s[3] !!}</div>
            </article>
        @endforeach
    </div>
</section>

<section class="ml-sec" id="ml-aspek" aria-labelledby="ml-aspek-h">
    <div class="ml-sec-head">
        <span class="ml-chip">&#9733; Aspek penilaian</span>
        <h2 id="ml-aspek-h">Aspek penilaian</h2>
        <p>Setiap jawaban dinilai dari tiga aspek (skala 0-100) menjadi satu nilai akhir.</p>
    </div>
    <div class="ml-scale">
        @foreach(config('grading.categories', []) as $label => $min)
            <span class="badge {{ $badgeOf[$label] ?? '' }}">{{ $label }}</span>
        @endforeach
    </div>
    <div class="ml-ela" tabindex="0" role="group" aria-roledescription="carousel" aria-label="Tiga aspek penilaian. Geser atau gunakan tombol panah.">
        <div class="ml-ela-track">
            @foreach($aspects as $i => $aspect)
                <article class="ml-ela-card {{ $mlTone[$i % 3] }}" aria-label="Aspek {{ $i + 1 }} dari {{ count($aspects) }}">
                    <div>
                        <div class="ml-ela-top"><span class="ml-no" aria-hidden="true">{{ $i + 1 }}</span><span class="ml-tag">Skala 0-100</span></div>
                        <h3>{{ $aspect[0] }}</h3>
                        <p>{{ $aspect[1] }}</p>
                    </div>
                    <div aria-hidden="true">
                        <div class="ml-score"><span>Contoh skor</span><b>{{ $mlScore[$i % 3] }} / 100</b></div>
                        <div class="ml-rail"><i style="--v: {{ $mlScore[$i % 3] }}%"></i></div>
                        <div class="ml-rail-lb"><span>0</span><span>50</span><span>100</span></div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
    <div class="ml-dots" role="group" aria-label="Pilih aspek">
        @foreach($aspects as $i => $aspect)
            <button type="button" class="ml-dot" aria-label="Aspek {{ $i + 1 }}: {{ $aspect[0] }}" aria-current="{{ $i === 0 ? 'true' : 'false' }}"></button>
        @endforeach
    </div>
</section>
@endif

{{-- ======================= DOCK BAWAH + SCRIPT ======================= --}}
@if($part === 'dock')
<nav class="ml-dock" aria-label="Navigasi cepat">
    <a class="ml-dk on" href="#ml-top" data-sec="top"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>Beranda</a>
    <a class="ml-dk" href="#ml-alur" data-sec="ml-alur"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M10 8l6 4-6 4z"/></svg>Alur</a>
    <a class="ml-dk" href="#ml-aspek" data-sec="ml-aspek"><svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>Penilaian</a>
    <a class="ml-dk-cta" href="{{ $signupOpen ? route('register') : route('login') }}">{{ $signupOpen ? 'Mulai' : 'Masuk' }} </a>
</nav>

<script>
(function () {
    'use strict';
    if (!window.matchMedia('(max-width: 820px)').matches) { return; }

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var $ = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return [].slice.call((r || document).querySelectorAll(s)); };
    var passive = window.egSignal ? { passive: true, signal: egSignal() } : { passive: true };

    // "Lihat cara kerja" di hero menuju bagian cara kerja versi HP
    $$('.hero-actions a[href="#cara-kerja"]').forEach(function (a) { a.setAttribute('href', '#ml-alur'); });

    /* ---------- A. Cara kerja: parallax card stack (skala + blur + kedalaman) ---------- */
    (function () {
        var track = $('.ml-par');
        if (!track) { return; }
        var cards = $$('.ml-step', track), raf = 0, active = -1;

        function update() {
            raf = 0;
            var tr = track.getBoundingClientRect();
            var cx = tr.left + tr.width / 2;
            var best = 0, bestD = 1e9;

            cards.forEach(function (card, i) {
                var r = card.getBoundingClientRect();
                var d = ((r.left + r.width / 2) - cx) / (r.width * .86);
                var a = Math.min(Math.abs(d), 1.4);
                card.style.setProperty('--d', d.toFixed(3));
                card.style.setProperty('--a', a.toFixed(3));
                card.style.zIndex = String(10 - Math.round(a * 4));
                if (Math.abs(d) < bestD) {
                    bestD = Math.abs(d);
                    best = i;
                }
            });
            active = best;
        }

        track.addEventListener('scroll', function () {
            if (!raf) { raf = requestAnimationFrame(update); }
        }, { passive: true });
        window.addEventListener('resize', update, passive);
        update();
    })();

    /* ---------- B. Aspek penilaian: fluid morphing slide + elastic snap (pegas) ---------- */
    (function () {
        var box = $('.ml-ela'); if (!box) { return; }
        var track = $('.ml-ela-track', box), cards = $$('.ml-ela-card', track), dots = $$('.ml-dot', box.parentNode);
        var n = cards.length, idx = 0, pos = 0, vel = 0, target = 0, step = 0, drag = null, raf = 0, lastT = 0;
        var K = 210, C = 15;   // kekakuan dan redaman pegas; redaman di bawah kritis = pantulan halus

        function render() {
            if (!step) { return; }
            track.style.transform = 'translate3d(' + pos.toFixed(2) + 'px,0,0)';
            var cur = -pos / step, skew = Math.max(-7, Math.min(7, vel * -0.0025));
            cards.forEach(function (c, i) {
                var a = Math.min(Math.abs(i - cur), 1);   // 0 = aktif, 1 = jauh
                c.style.transform = 'skewX(' + (skew * (1 - a * .5)).toFixed(2) + 'deg) scale(' + (1 - a * .07).toFixed(3) + ')';
                c.style.borderRadius = (18 + a * 14).toFixed(1) + 'px';
                c.style.opacity = (1 - a * .3).toFixed(2);
            });
        }
        function measure() {
            var gap = parseFloat(getComputedStyle(track).columnGap) || 12;
            step = cards[0].offsetWidth + gap;
            pos = target = -idx * step; vel = 0;
            render();
        }
        function tick(t) {
            var dt = lastT ? Math.min((t - lastT) / 1000, 1 / 30) : 1 / 60; lastT = t;
            vel += (-K * (pos - target) - C * vel) * dt;
            pos += vel * dt;
            render();
            if (Math.abs(pos - target) < .3 && Math.abs(vel) < 4) { pos = target; vel = 0; render(); raf = 0; lastT = 0; return; }
            raf = requestAnimationFrame(tick);
        }
        function setIndex(i) {
            idx = Math.max(0, Math.min(n - 1, i)); target = -idx * step;
            dots.forEach(function (d, k) { d.setAttribute('aria-current', k === idx ? 'true' : 'false'); });
            if (reduce) { pos = target; vel = 0; render(); }
            else if (!raf) { lastT = 0; raf = requestAnimationFrame(tick); }
        }

        box.addEventListener('pointerdown', function (e) {
            if (e.pointerType === 'mouse' && e.button !== 0) { return; }
            if (raf) { cancelAnimationFrame(raf); raf = 0; lastT = 0; }
            drag = { id: e.pointerId, x: e.clientX, y: e.clientY, p: pos, lx: e.clientX, lt: e.timeStamp, lock: null };
            try { box.setPointerCapture(e.pointerId); } catch (x) {}
        });
        box.addEventListener('pointermove', function (e) {
            if (!drag || e.pointerId !== drag.id) { return; }
            var dx = e.clientX - drag.x, dy = e.clientY - drag.y;
            if (drag.lock === null && (Math.abs(dx) > 6 || Math.abs(dy) > 6)) {
                drag.lock = Math.abs(dx) > Math.abs(dy) ? 'x' : 'y';
                if (drag.lock === 'x') { try { box.setPointerCapture(e.pointerId); } catch (x) {} box.classList.add('is-drag'); }
            }
            if (drag.lock !== 'x') { return; }
            if (e.cancelable) { e.preventDefault(); }
            var min = -(n - 1) * step, p = drag.p + dx;
            if (p > 0) { p *= .35; } else if (p < min) { p = min + (p - min) * .35; }   // tepi ditahan seperti karet
            vel = vel * .6 + ((e.clientX - drag.lx) / Math.max(e.timeStamp - drag.lt, 1) * 1000) * .4;
            drag.lx = e.clientX; drag.lt = e.timeStamp;
            pos = p; render();
        });
        function end(e) {
            if (!drag || e.pointerId !== drag.id) { return; }
            var wasX = drag.lock === 'x';
            drag = null; box.classList.remove('is-drag');
            if (wasX) { setIndex(Math.round(-(pos + vel * .18) / step)); }
        }
        box.addEventListener('pointerup', end);
        box.addEventListener('pointercancel', end);
        box.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowRight') { e.preventDefault(); setIndex(idx + 1); }
            if (e.key === 'ArrowLeft') { e.preventDefault(); setIndex(idx - 1); }
        });
        dots.forEach(function (d, i) { d.addEventListener('click', function () { setIndex(i); }); });
        window.addEventListener('resize', measure, passive);
        requestAnimationFrame(measure);
    })();

    /* ---------- C. Dock bawah: tandai bagian yang sedang dilihat ---------- */
    (function () {
        var links = $$('.ml-dock a[data-sec]');
        if (!links.length || !('IntersectionObserver' in window)) { return; }
        function mark(id) { links.forEach(function (a) { a.classList.toggle('on', a.getAttribute('data-sec') === id); }); }
        var io = new IntersectionObserver(function (es) {
            es.forEach(function (e) { if (e.isIntersecting) { mark(e.target.id); } });
            if (window.scrollY < 240) { mark('top'); }
        }, { rootMargin: '-35% 0px -55% 0px' });
        ['ml-alur', 'ml-aspek'].forEach(function (id) { var s = document.getElementById(id); if (s) { io.observe(s); } });
        window.addEventListener('scroll', function () { if (window.scrollY < 240) { mark('top'); } }, passive);
    })();
})();
</script>
@endif