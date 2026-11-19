@extends('layout')
@section('title', 'Penilai Esai Otomatis')

@push('styles')
<style>
    /* =========================================================
       Dasar halaman landing
       ========================================================= */
    html { scroll-padding-top: 104px; }
    #cara-kerja, #aspek { scroll-margin-top: 104px; }
    .sr-only { position: absolute; width: 1px; height: 1px; margin: -1px; padding: 0; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0; }

    /* Muncul halus saat digulir (hanya aktif bila JS jalan) */
    .js .reveal { opacity: 0; translate: 0 18px; }
    .js .reveal.in { opacity: 1; translate: 0 0; }
    .js .reveal { transition: opacity .6s ease var(--d, 0s), translate .6s ease var(--d, 0s); }

    /* =========================================================
       1. Hero + judul Stroke Text + latar iridescence
       ========================================================= */
    .hero { position: relative; isolation: isolate; display: grid; grid-template-columns: 1.05fr .95fr; gap: 28px; align-items: start; padding: 48px 0 30px; }
    .hero .lead { max-width: 48ch; }
    .hero-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 22px; }

    /* Latar iridescence satu halaman penuh. Kanvas menempel (sticky) setinggi layar di dalam pembungkus
       setinggi seluruh konten, sehingga murah dirender. Tepi atas/bawah memudar agar tidak menabrak topbar dan footer. */
    #isi { position: relative; isolation: isolate; }
    .page-bg { position: absolute; inset: 0 -36px; z-index: -1; pointer-events: none;
        -webkit-mask-image: linear-gradient(to bottom, transparent 0, #000 260px, #000 calc(100% - 180px), transparent 100%);
                mask-image: linear-gradient(to bottom, transparent 0, #000 260px, #000 calc(100% - 180px), transparent 100%); }
    .page-bg canvas { position: absolute; inset: 0; display: block; width: 100%; height: 100%; opacity: .8; }
    .page-bg.no-gl { background:
        radial-gradient(40% 30% at 18% 18%, color-mix(in srgb, var(--lime) 20%, transparent), transparent 70%),
        radial-gradient(45% 30% at 82% 50%, color-mix(in srgb, var(--sky) 40%, transparent), transparent 70%),
        linear-gradient(180deg, var(--mist), transparent); }
    .page-bg.no-gl canvas { display: none; }

    /* Stroke Text: garis huruf tergambar dari kiri ke kanan, lalu terisi penuh dari bawah */
    .hero h1 { font-size: clamp(34px, 5.4vw, 58px); line-height: 1.04; letter-spacing: -.02em; margin-bottom: .45em; }
    .st-main, .st-sub { display: block; }
    .st-sub { font-size: .46em; line-height: 1.25; margin-top: .55em; letter-spacing: -.01em; }
    .st-w {
        --fill: var(--ink);
        display: inline-block; color: transparent;
        -webkit-text-stroke: .028em var(--ink);
        background: linear-gradient(var(--fill), var(--fill)) 0 100% / 100% 0% no-repeat;
        -webkit-background-clip: text; background-clip: text;
        clip-path: inset(-14% 101% -14% 0);
        animation: st-draw .8s cubic-bezier(.65, 0, .35, 1) forwards, st-flood .6s ease-out forwards;
        animation-delay: calc(var(--i) * .08s + .1s), calc(var(--i) * .08s + .75s);
    }
    .st-w.is-hl { --fill: var(--lime); }
    .st-reset .st-w { animation: none; }
    .st-sub .st-w { -webkit-text-stroke-width: .04em; }
    @media (min-width: 821px) {
    .st-w {
        padding-bottom: .24em;
        margin-bottom: -.24em;
        }
    }
    @keyframes st-draw  { to { clip-path: inset(-14% -4% -14% 0); } }
    @keyframes st-flood { to { background-size: 100% 100%; } }
    .hero .lead, .hero-actions { animation: rise .7s ease both; animation-delay: 1.1s; }
    .hero-actions { animation-delay: 1.25s; }
    @keyframes rise { from { opacity: 0; transform: translateY(14px); } }

    /* =========================================================
       4. Tombol Specular (pantulan cahaya mengikuti kursor, tanpa outline hijau)
       ========================================================= */
    .spec { position: relative; overflow: hidden; isolation: isolate; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .28); }
    .spec.btn-outline { box-shadow: inset 0 -2px 0 rgba(27, 27, 27, .07); }
    .spec-label { position: relative; z-index: 2; display: inline-flex; align-items: center; gap: 8px; }
    .spec::before { content: ""; position: absolute; inset: 0; z-index: 1; pointer-events: none; opacity: 0; transition: opacity .25s;
        background: radial-gradient(96px circle at var(--bx, 50%) var(--by, 0%), rgba(255, 255, 255, .42), rgba(255, 255, 255, .08) 45%, transparent 72%); }
    .spec.btn-outline::before { background: radial-gradient(96px circle at var(--bx, 50%) var(--by, 0%), rgba(255, 255, 255, .18), rgba(130, 140, 160, .14) 50%, transparent 74%); }
    .spec::after { content: ""; position: absolute; top: -25%; bottom: -25%; left: -60%; width: 38%; z-index: 1; pointer-events: none; opacity: 0;
        background: linear-gradient(105deg, transparent, rgba(255, 255, 255, .45), transparent); transform: skewX(-18deg); }
    .spec.btn-outline::after { background: linear-gradient(105deg, transparent, rgba(255, 255, 255, .22), transparent); }
    .spec:hover::before, .spec:focus-visible::before { opacity: 1; }
    .spec:hover::after { animation: spec-sweep .85s ease; }
    .spec:hover { box-shadow: 0 8px 20px rgba(0, 0, 0, .18), inset 0 1px 0 rgba(255, 255, 255, .28); }
    .spec:focus-visible, .hiw-scroller:focus-visible { outline: 2px solid color-mix(in srgb, var(--ink) 45%, transparent); outline-offset: 2px; }
    @keyframes spec-sweep { 0% { left: -60%; opacity: 1; } 100% { left: 130%; opacity: 1; } }

    /* =========================================================
       2. Logo cloud (marquee + tepi blur progresif)
       ========================================================= */
    .brands { display: grid; grid-template-columns: auto 1fr; align-items: center; gap: 28px; margin-top: 8px; padding: 20px 0;
        border-block: 1.5px solid color-mix(in srgb, var(--ink) 14%, transparent); }
    .brands-label { margin: 0; padding-right: 28px; font: 400 12.5px var(--mono); color: var(--muted); white-space: nowrap;
        border-right: 1.5px solid color-mix(in srgb, var(--ink) 14%, transparent); }
    .cloud { position: relative; overflow: hidden; }
    .cloud::before, .cloud::after { content: ""; position: absolute; top: 0; bottom: 0; width: 84px; z-index: 2; pointer-events: none;
        -webkit-backdrop-filter: blur(5px); backdrop-filter: blur(5px);
        background: linear-gradient(to right, var(--paper), transparent);
        -webkit-mask-image: linear-gradient(to right, #000 25%, transparent); mask-image: linear-gradient(to right, #000 25%, transparent); }
    .cloud::before { left: 0; }
    .cloud::after { right: 0; transform: scaleX(-1); }
    .cloud-track { display: flex; width: max-content; animation: marquee 30s linear infinite; }
    .cloud:hover .cloud-track { animation-play-state: paused; }
    .cloud-set { display: flex; align-items: center; gap: 56px; padding-right: 56px; }
    @keyframes marquee { to { transform: translateX(-50%); } }
    .logo { display: inline-flex; align-items: center; gap: 10px; white-space: nowrap; font: 700 21px var(--display); letter-spacing: -.01em;
        color: color-mix(in srgb, var(--ink) 58%, transparent); transition: color .25s; }
    .logo:hover { color: var(--ink); }
    .logo-mark { display: grid; place-items: center; width: 36px; height: 36px; border-radius: 11px; border: 1.5px solid currentColor; transition: background .25s, color .25s, border-color .25s, transform .25s; }
    .logo-mark svg { width: 22px; height: 22px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
    /* Logo SVG sendiri: abu-abu redup, berwarna penuh saat disorot. Mode gelap: warna asli di atas ubin putih */
    .logo-img { display: block; height: 32px; width: auto; max-width: 150px; object-fit: contain; filter: grayscale(1); opacity: .72; transition: filter .25s, opacity .25s, transform .25s; }
    html[data-theme="dark"] .logo-img { filter: none; opacity: 1; }
    .logo:hover .logo-img { filter: none; opacity: 1; transform: translateY(-2px) rotate(-3deg); }
    .logo:hover .logo-mark { background: var(--lime); color: #1b1b1b; border-color: #1b1b1b; transform: rotate(-6deg); }

    /* =========================================================
       3a. Spotlight card (cahaya mengikuti kursor)
       ========================================================= */
    .spot { position: relative; isolation: isolate; overflow: hidden; }
    .spot::before { content: ""; position: absolute; inset: 0; z-index: -1; border-radius: inherit; pointer-events: none; opacity: 0; transition: opacity .3s;
        background: radial-gradient(280px circle at var(--mx, 50%) var(--my, 50%), rgba(196, 245, 93, .55), transparent 70%); }
    .spot::after { content: ""; position: absolute; inset: 0; border-radius: inherit; padding: 2px; pointer-events: none; opacity: 0; transition: opacity .3s;
        background: radial-gradient(200px circle at var(--mx, 50%) var(--my, 50%), var(--lime), transparent 70%);
        -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0); -webkit-mask-composite: xor;
                mask: linear-gradient(#000 0 0) content-box exclude, linear-gradient(#000 0 0); }
    .spot:hover::before, .spot:hover::after, .spot:focus-within::before, .spot:focus-within::after { opacity: 1; }
    .card-dark.spot::before { background: radial-gradient(280px circle at var(--mx, 50%) var(--my, 50%), rgba(196, 245, 93, .26), transparent 70%); }
    .sw-lime.spot::before { background: radial-gradient(280px circle at var(--mx, 50%) var(--my, 50%), rgba(255, 255, 255, .6), transparent 70%); }
    .sw-lime.spot::after { background: radial-gradient(200px circle at var(--mx, 50%) var(--my, 50%), #fff, transparent 70%); }

    /* =========================================================
       Cara kerja: panggung isometrik 3D (kamera menyusuri jalur dokumen)
       Geser / swipe horizontal untuk berpindah tahap, atau biarkan berjalan otomatis.
       ========================================================= */
    .hiw { --o: var(--ink); --plat: var(--mist); --sh-a: #ffffff; --lm: var(--lime); }
    html[data-theme="dark"] .hiw { --plat: #2a2a27; }

    .hiw-stage { position: relative; height: clamp(600px, 82vh, 720px); margin-top: -50px; user-select: none; -webkit-user-select: none; }

    /* Adegan: menyatu dengan latar halaman, tepinya memudar (tanpa kotak/bingkai) */
    .hiw-scene { position: absolute; inset: 0; overflow: hidden; pointer-events: none;
        -webkit-mask-image: linear-gradient(to right, transparent 12%, #000 50%), linear-gradient(to bottom, transparent, #000 12%, #000 86%, transparent);
        -webkit-mask-composite: source-in;
                mask-image: linear-gradient(to right, transparent 12%, #000 50%), linear-gradient(to bottom, transparent, #000 12%, #000 86%, transparent);
                mask-composite: intersect; }
    .hiw-world { position: absolute; left: 0; top: 0; transform-style: preserve-3d; transform-origin: 0 0; will-change: transform; }
    .hiw-world .o3, .hiw-world .bx { position: absolute; transform-style: preserve-3d; }
    .hiw-world .floor { position: absolute; background-image:
        linear-gradient(color-mix(in srgb, var(--ink) 7%, transparent) 2px, transparent 2px),
        linear-gradient(90deg, color-mix(in srgb, var(--ink) 7%, transparent) 2px, transparent 2px);
        background-size: 110px 110px; }
    .hiw-world .pathsvg { position: absolute; overflow: visible; }

    /* Kotak 3D: tiga sisi terlihat (atas, depan, kiri) */
    .bx > i { position: absolute; display: block; box-sizing: border-box; border: 2px solid var(--o); font-style: normal; }
    .bx > .t { background: var(--c, #fff); }
    .bx > .f { background: color-mix(in srgb, var(--c, #fff) 70%, #000); }
    .bx > .l { background: color-mix(in srgb, var(--c, #fff) 84%, #000); }
    .bx.sh-soft > .t { box-shadow: 46px 46px 60px rgba(27, 27, 27, .16); }
    .dl { position: absolute; border-radius: 50%; border: 2px solid var(--o); box-sizing: border-box; }

    /* Papan berdiri (selalu menghadap kamera) */
    .bbi { position: absolute; transform-origin: 50% 100%; transform: rotateZ(45deg) rotateX(-90deg) scaleY(1.15); box-sizing: border-box; font: 700 15px/1.25 var(--display); color: #1b1b1b; }
    .bb-card { background: #fff; border: 2.5px solid #1b1b1b; border-radius: 14px; box-shadow: 5px 5px 0 var(--lime); padding: 12px 14px; }
    .bb-dark { background: #1b1b1b; color: #ececE8; border-color: #1b1b1b; box-shadow: 5px 5px 0 var(--lime); }
    .bb-mono { font: 400 13px/1.5 var(--mono); }
    .bbi small { display: block; font: 400 11.5px/1.3 var(--mono); opacity: .7; }
    .bbi svg { display: block; width: 100%; height: 100%; overflow: visible; }
    .bb-tag { position: absolute; left: 50%; bottom: 2px; translate: -50% 0; padding: 2px 10px; border-radius: 99px; background: #1b1b1b; color: var(--lime); font: 700 12px var(--mono); white-space: nowrap; }

    /* Lembar dokumen yang bergerak */
    .sheet { left: 0; top: 0; }
    .sheet .sh { position: absolute; display: block; border-radius: 10px; background: rgba(27, 27, 27, .22); transform: translateZ(calc(var(--z, 30px) * -1 + 1px)); }
    .sheet .sf { position: absolute; box-sizing: border-box; background: #fff; border: 2.5px solid #1b1b1b; border-radius: 8px; overflow: hidden; color: #1b1b1b; font: 700 14px/1 var(--display); }
    .sheet .k { position: absolute; inset: 0; padding: 9px 10px; display: none; flex-direction: column; gap: 7px; }
    .sheet[data-k="form"] .k-form, .sheet[data-k="ans"] .k-ans, .sheet[data-k="ess"] .k-ess, .sheet[data-k="scr"] .k-scr { display: flex; }
    .sheet .k u { display: block; height: 5px; border-radius: 3px; background: #c9c9c4; text-decoration: none; }
    .sheet .k-form .fh { display: flex; align-items: center; gap: 6px; height: 20px; margin: -9px -10px 2px; padding: 0 10px; background: #1b1b1b; color: var(--lime); font: 700 10px var(--mono); }
    .sheet .k-form .fh::before { content: ""; width: 9px; height: 9px; background: var(--lime); border-radius: 2px; }
    .sheet .k-form p { display: flex; align-items: center; gap: 6px; margin: 0; }
    .sheet .k-form p i { flex: none; width: 10px; height: 10px; border: 2px solid #1b1b1b; border-radius: 3px; }
    .sheet .k-form p u { flex: 1; }
    .sheet .k-form s { margin-top: auto; height: 14px; width: 54%; border-radius: 4px; background: var(--lime); border: 2px solid #1b1b1b; }
    .sheet .k-ans em { font: 700 10px var(--mono); font-style: normal; color: #1b1b1b; }
    .sheet .k-ans em::after { content: ""; display: block; height: 2px; margin-top: 4px; background: #1b1b1b; }
    .sheet .k-ess u:nth-child(3), .sheet .k-ess u:nth-child(5), .sheet .k-ess u:nth-child(7) { background: var(--lime); box-shadow: 0 0 0 2px var(--lime); }
    .sheet .k-ess em { font: 700 10px var(--mono); font-style: normal; }
    .sheet .k-scr { align-items: flex-start; justify-content: center; gap: 4px; }
    .sheet .k-scr strong { font: 800 42px/1 var(--display); letter-spacing: -.04em; }
    .sheet .k-scr small { font: 700 11px var(--mono); }
    .sheet .k-scr u { background: var(--lime); width: 80%; height: 7px; border: 1.5px solid #1b1b1b; }
    .sheet .k-scr u:nth-of-type(2) { width: 62%; }
    .sheet .k-scr u:nth-of-type(3) { width: 92%; }
    .sheet.flip .sf { animation: sheet-flip .5s ease; }
    @keyframes sheet-flip { 40% { transform: scale(1.12) rotate(-2deg); } }

    /* Kursor penarik (tahap 1) */
    .cursor-bb { opacity: 0; transition: opacity .3s; }
    .cursor-bb.on { opacity: 1; }
    .cursor-bb .bbi { font: 700 12px var(--mono); }
    .cursor-bb .pill { position: absolute; left: 34px; top: 30px; padding: 3px 9px; border-radius: 99px; background: var(--lime); border: 2px solid #1b1b1b; white-space: nowrap; }

    /* Animasi objek */
    @keyframes spin { to { rotate: 360deg; } }
    @keyframes spin-r { to { rotate: -360deg; } }
    @keyframes bob { 50% { translate: 0 -10px; } }
    @keyframes blink { 50% { opacity: 0; } }
    @keyframes pulse-lime { 0%, 100% { box-shadow: 0 0 0 0 rgba(196, 245, 93, .85); } 60% { box-shadow: 0 0 0 16px rgba(196, 245, 93, 0); } }
    @keyframes scan-y { 0% { translate: 0 -95px; } 50% { translate: 0 95px; } 100% { translate: 0 -95px; } }
    @keyframes knob-a { 0%, 100% { left: 18%; } 50% { left: 62%; } }
    @keyframes knob-b { 0%, 100% { left: 70%; } 50% { left: 30%; } }
    @keyframes knob-c { 0%, 100% { left: 40%; } 50% { left: 80%; } }
    @keyframes grow { from { height: 0; } }
    @keyframes pop { from { scale: .6; opacity: 0; } }
    @keyframes edit-a { 0%, 45% { opacity: 1; } 55%, 100% { opacity: 0; } }
    @keyframes edit-b { 0%, 45% { opacity: 0; } 55%, 100% { opacity: 1; } }
    @keyframes laser-glow { 50% { opacity: .55; } }
    .bobi-float { animation: bob 2.6s ease-in-out infinite; }
    .gear { transform-origin: 50% 50%; transform-box: fill-box; animation: spin 6s linear infinite; }
    .gear.rev { animation: spin-r 4.2s linear infinite; }
    .caret { display: inline-block; width: 7px; height: 14px; margin-left: 2px; vertical-align: -2px; background: var(--lime); animation: blink 1s steps(2) infinite; }

    /* Meter/slider rubrik (tahap 2) */
    .rails { display: grid; gap: 6px; padding: 7px; height: 100%; align-content: center; }
    .rail { position: relative; height: 8px; border-radius: 4px; background: #c9c9c4; border: 1.5px solid #1b1b1b; }
    .rail i { position: absolute; top: -5px; width: 14px; height: 14px; border-radius: 50%; background: var(--lime); border: 2px solid #1b1b1b; animation: knob-a 4s ease-in-out infinite; }
    .rail:nth-child(2) i { animation-name: knob-b; animation-duration: 5s; }
    .rail:nth-child(3) i { animation-name: knob-c; animation-duration: 4.6s; }
    .wt { display: grid; gap: 8px; margin-top: 8px; }
    .wt div { display: grid; grid-template-columns: 62px 1fr 30px; align-items: center; gap: 8px; font: 700 12px var(--mono); }
    .wt span { display: block; height: 9px; border-radius: 5px; border: 1.5px solid #1b1b1b; background: #ececE8; overflow: hidden; }
    .wt span i { display: block; height: 100%; background: var(--lime); border-right: 1.5px solid #1b1b1b; }

    /* Tabel koreksi (tahap 5) */
    .tbl { width: 100%; border-collapse: collapse; font: 700 13px var(--display); }
    .tbl th { text-align: left; font: 400 11px var(--mono); opacity: .6; padding: 0 0 5px; }
    .tbl td { padding: 5px 0; border-top: 1.5px solid #1b1b1b; }
    .tbl td:last-child, .tbl th:last-child { text-align: right; }
    .tbl .ed { position: relative; display: inline-block; min-width: 46px; padding: 2px 8px; border-radius: 6px; background: var(--lime); border: 2px solid #1b1b1b; text-align: center; }
    .tbl .ed b { font-weight: 700; }
    .tbl .ed b + b { position: absolute; inset: 2px 0 0; opacity: 0; }
    .hiw[data-s="4"] .tbl .ed b:first-child { animation: edit-a 3.2s infinite; }
    .hiw[data-s="4"] .tbl .ed b + b { animation: edit-b 3.2s infinite; }

    /* Grafik monitor (tahap 6) */
    .mon { display: flex; flex-direction: column; height: 100%; background: #fff; color: #1b1b1b; }
    .mon-h { display: flex; align-items: center; gap: 5px; padding: 6px 9px; background: #1b1b1b; color: var(--lime); font: 700 10px var(--mono); }
    .mon-h i { width: 8px; height: 8px; border-radius: 50%; background: var(--lime); }
    .mon-h i:nth-child(2) { background: #fff; } .mon-h i:nth-child(3) { background: #8b8b84; }
    .mon-h span { margin-left: 6px; }
    .mon-b { flex: 1; display: flex; align-items: flex-end; gap: 9px; padding: 10px 14px 0; border-bottom: 2px solid #1b1b1b; margin: 0 10px; }
    .mon-b i { flex: 1; height: var(--h); background: var(--lime); border: 2px solid #1b1b1b; border-bottom: 0; border-radius: 4px 4px 0 0; }
    .mon-b i:nth-child(3) { background: #1b1b1b; }
    .hiw[data-s="5"] .mon-b i { animation: grow 1.1s cubic-bezier(.2, .8, .2, 1) backwards; }
    .hiw[data-s="5"] .mon-b i:nth-child(2) { animation-delay: .1s; } .hiw[data-s="5"] .mon-b i:nth-child(3) { animation-delay: .2s; }
    .hiw[data-s="5"] .mon-b i:nth-child(4) { animation-delay: .3s; } .hiw[data-s="5"] .mon-b i:nth-child(5) { animation-delay: .4s; }
    .mon-f { display: flex; justify-content: space-between; padding: 5px 12px 6px; font: 700 10px var(--mono); }
    .xl { display: grid; place-items: center; height: 100%; font: 800 21px var(--display); letter-spacing: -.02em; background: var(--lime); color: #1b1b1b;
        background-image: linear-gradient(#1b1b1b 2px, transparent 2px), linear-gradient(90deg, #1b1b1b 2px, transparent 2px); background-size: 100% 25%, 33.33% 100%; background-color: var(--lime); }
    .xl b { padding: 1px 8px; background: #fff; border: 2px solid #1b1b1b; border-radius: 6px; }
    .dl-bb { display: grid; place-items: center; width: 100%; height: 100%; border-radius: 50%; background: #1b1b1b; border: 3px solid #1b1b1b; animation: bob 1.3s ease-in-out infinite; }
    .dl-bb svg { width: 56%; height: 56%; fill: none; stroke: var(--lime); stroke-width: 3; stroke-linecap: round; stroke-linejoin: round; }

    /* Kilau laser pemindai (tahap 4) */
    .laser > i { border: 0; background: linear-gradient(to bottom, rgba(196, 245, 93, .75), rgba(196, 245, 93, .15)); }
    .laser .t { background: var(--lime); box-shadow: 0 0 18px 6px rgba(196, 245, 93, .8); }
    .laser { animation: scan-y 4.2s ease-in-out infinite; }
    .laser .t { animation: laser-glow 1.2s ease-in-out infinite; }
    .chip { display: inline-block; margin: 0 6px 6px 0; padding: 4px 10px; border-radius: 99px; border: 2px solid #1b1b1b; background: #fff; font: 700 12.5px var(--mono); white-space: nowrap; }
    .chip.hl { background: var(--lime); }
    .hiw[data-s="3"] .chip { animation: pop .45s cubic-bezier(.3, 1.6, .5, 1) backwards; }
    .hiw[data-s="3"] .chip:nth-child(2) { animation-delay: .25s; } .hiw[data-s="3"] .chip:nth-child(3) { animation-delay: .5s; } .hiw[data-s="3"] .chip:nth-child(4) { animation-delay: .75s; }
    .meter { height: 12px; margin: 8px 0 3px; border: 2px solid #1b1b1b; border-radius: 7px; background: #ececE8; overflow: hidden; }
    .meter i { display: block; height: 100%; width: 92%; background: var(--lime); border-right: 2px solid #1b1b1b; }
    .hiw[data-s="3"] .meter i { animation: meter-fill 1.6s ease-out backwards .3s; }
    @keyframes meter-fill { from { width: 0; } }
    .btn-3d { background: var(--lime); }
    .hiw[data-s="1"] .btn-3d { animation: pulse-lime 1.6s infinite; }

    /* Panel teks kiri */
    .hiw-copy { position: absolute; left: 0; top: 46%; width: min(41%, 440px); translate: 0 -50%; display: grid; z-index: 3; pointer-events: none; }
    .hiw-step { grid-area: 1 / 1; opacity: 0; visibility: hidden; translate: 0 16px; transition: opacity .45s ease, translate .55s cubic-bezier(.2, .8, .2, 1), visibility 0s .5s; }
    .hiw-step.on { opacity: 1; visibility: visible; translate: 0 0; transition-delay: .12s, .12s, 0s; }
    .hiw-eye { margin: 0 0 14px; font: 400 12px var(--mono); letter-spacing: .1em; text-transform: uppercase; color: var(--muted); }
    .hiw-step h3 { margin: 0 0 14px; font: 800 clamp(32px, 4.1vw, 52px)/1.02 var(--display); letter-spacing: -.035em; }
    .hiw-step h3 mark { 
        color: #1b1b1b; 
        background: var(--lime); 
        display: inline-block; 
        line-height: 1.3; 
        padding: 0.05em 0.22em; 
        margin: 0.08em 0.02em; 
        border-radius: 0.2em; 
    }
    .hiw-step > p { margin: 0 0 18px; max-width: 40ch; font-size: 15.5px; line-height: 1.55; color: var(--muted); }
    .hw { width: min(100%, 330px); padding: 13px 15px; border-radius: 16px; background: var(--paper); border: 1.5px solid var(--ink); box-shadow: 4px 4px 0 var(--lime); font-size: 12.5px; color: var(--ink); }
    .hw-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .hw .mono { font: 400 11.5px var(--mono); color: var(--muted); }
    .hw-field { display: flex; align-items: center; gap: 8px; padding: 8px 10px; margin-top: 8px; border-radius: 10px; border: 1.5px solid color-mix(in srgb, var(--ink) 30%, transparent); font: 400 12px var(--mono); white-space: nowrap; overflow: hidden; }
    .hw-btn { display: inline-flex; align-items: center; padding: 7px 14px; margin-top: 10px; border-radius: 99px; background: var(--ink); color: var(--paper); font-weight: 700; font-size: 12.5px; }
    .hw-btn.lime { background: var(--lime); color: #1b1b1b; border: 1.5px solid #1b1b1b; }
    .hw-sw { display: inline-block; position: relative; width: 38px; height: 22px; border-radius: 99px; background: var(--lime); border: 1.5px solid #1b1b1b; }
    .hw-sw::after { content: ""; position: absolute; right: 2px; top: 2px; width: 14px; height: 14px; border-radius: 50%; background: #1b1b1b; }
    .hw-dot { display: inline-block; width: 9px; height: 9px; margin-right: 6px; border-radius: 50%; background: var(--lime); border: 1.5px solid #1b1b1b; animation: pulse-lime 1.6s infinite; }
    .hw-list { display: grid; gap: 6px; margin-top: 8px; }
    .hw-list div { display: flex; justify-content: space-between; padding-top: 6px; border-top: 1px solid color-mix(in srgb, var(--ink) 18%, transparent); font-weight: 700; }
    .hw-list b.edit { padding: 0 7px; border-radius: 6px; background: var(--lime); color: #1b1b1b; }

    /* Penunjuk progres (segmen 01-06) + petunjuk geser */
    .hiw-scroller { position: absolute; inset: 0 0 56px 0; z-index: 4; overflow-x: auto; overflow-y: hidden; overscroll-behavior-x: contain; scrollbar-width: none; cursor: grab; touch-action: pan-x pan-y; -webkit-overflow-scrolling: touch; outline-offset: -3px; }
    .hiw-scroller::-webkit-scrollbar { display: none; }
    .hiw-scroller:active { cursor: grabbing; }
    .hiw-scroller:focus-visible { outline: 3px solid var(--lime); }
    .hiw-track { height: 1px; width: 600%; }
    .hiw-prog { position: absolute; left: 0; right: 0; bottom: 0; z-index: 5; display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; }
    .hiw-seg { all: unset; box-sizing: border-box; cursor: pointer; display: block; padding: 6px 0 4px; font: 400 11.5px/1.3 var(--mono); color: var(--muted); transition: color .25s; }
    .hiw-seg:focus-visible { outline: 2px solid var(--ink); outline-offset: 2px; }
    .hiw-seg span { display: block; font-size: 10.5px; opacity: .75; }
    .hiw-seg em { display: block; height: 3px; margin-top: 8px; border-radius: 2px; background: color-mix(in srgb, var(--ink) 16%, transparent); overflow: hidden; font-style: normal; }
    .hiw-seg em i { display: block; height: 100%; background: var(--ink); transform-origin: 0 50%; transform: scaleX(0); }
    .hiw-seg.on { color: var(--ink); font-weight: 700; }
    .hiw-hint { position: absolute; right: 0; top: 14px; z-index: 5; display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 99px; border: 1.5px solid var(--ink); background: var(--paper); font: 700 12px var(--mono); transition: opacity .5s; pointer-events: none; }
    .hiw-hint svg { width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; animation: hint-x 1.4s ease-in-out infinite; }
    .hiw-hint.gone { opacity: 0; }
    @keyframes hint-x { 50% { translate: 5px 0; } }

    /* Tanpa JS: tampil sebagai daftar langkah biasa */
    .hiw:not(.is-js) .hiw-scene, .hiw:not(.is-js) .hiw-scroller, .hiw:not(.is-js) .hiw-prog, .hiw:not(.is-js) .hiw-hint { display: none; }
    .hiw:not(.is-js) .hiw-stage { height: auto; }
    .hiw:not(.is-js) .hiw-copy { position: static; width: auto; translate: none; gap: 28px; }
    .hiw:not(.is-js) .hiw-step { grid-area: auto; opacity: 1; visibility: visible; translate: none; }

    /* Mode ringan (perangkat lambat): buang efek termahal, tampilan tetap sama */
    .hiw.is-lite .hiw-world .floor, .hiw.is-lite .sheet .sh { display: none; }
    .hiw.is-lite .hiw-world .bx.sh-soft > .t { box-shadow: none; }
    .hiw.is-lite .hiw-scene { -webkit-mask-image: linear-gradient(to right, transparent 12%, #000 50%); mask-image: linear-gradient(to right, transparent 12%, #000 50%); -webkit-mask-composite: initial; mask-composite: initial; }
    .hiw.is-lite .gear { animation-duration: 9s; }
    .hiw.is-lite .hiw-world .laser { animation-duration: 6s; }

    .hiw.is-paused .hiw-world *, .hiw.is-paused .hiw-hint svg { animation-play-state: paused !important; }

    @media (max-width: 820px) {
        .hiw-stage { height: 640px; }
        .hiw-scene { inset: 0 0 auto 0; height: 380px; -webkit-mask-image: linear-gradient(to bottom, transparent, #000 10%, #000 88%, transparent); mask-image: linear-gradient(to bottom, transparent, #000 10%, #000 88%, transparent); }
        .hiw-copy { top: 392px; width: 100%; translate: none; }
        .hiw-step h3 { font-size: clamp(27px, 7.4vw, 34px); margin-bottom: 10px; }
        .hiw-step > p { margin-bottom: 0; font-size: 14.5px; }
        .hiw-eye { margin-bottom: 9px; }
        .hw { display: none; }
        .hiw-prog { gap: 6px; }
        .hiw-seg { font-size: 10px; }
        .hiw-seg span { display: none; }
        .hiw-hint { top: 6px; }
    }
    @media (prefers-reduced-motion: reduce) {
        .hiw-world *, .hiw-hint svg { animation: none !important; }
        .hiw-step { transition: none; }
    }

    /* =========================================================
       Band ajakan
       ========================================================= */
    /* Glassmorphism: kaca buram tembus pandang di atas latar iridescence, dengan semburat lime dari palet proyek */
    .band { position: relative; isolation: isolate; overflow: hidden; display: grid; grid-template-columns: 1.2fr .8fr; gap: 20px; align-items: center; margin-top: 44px; border-radius: 20px; padding: 30px 34px;
        background:
            radial-gradient(60% 95% at 94% 8%, color-mix(in srgb, var(--lime) 34%, transparent), transparent 72%),
            linear-gradient(135deg, color-mix(in srgb, var(--paper) 62%, transparent), color-mix(in srgb, var(--paper) 26%, transparent));
        -webkit-backdrop-filter: blur(18px) saturate(1.5); backdrop-filter: blur(18px) saturate(1.5);
        border: 1.5px solid color-mix(in srgb, var(--paper) 72%, transparent);
        box-shadow: 0 10px 34px color-mix(in srgb, var(--ink) 12%, transparent), inset 0 1px 0 rgba(255, 255, 255, .65), inset 0 -1px 0 rgba(255, 255, 255, .18); }
    /* Kilau diagonal tipis di sudut kiri atas, khas permukaan kaca */
    .band::before { content: ""; position: absolute; inset: 0; z-index: -1; border-radius: inherit; pointer-events: none;
        background: linear-gradient(115deg, rgba(255, 255, 255, .5) 0%, rgba(255, 255, 255, .12) 26%, transparent 46%); }
    html[data-theme="dark"] .band { border-color: rgba(236, 236, 232, .2);
        box-shadow: 0 10px 34px rgba(0, 0, 0, .45), inset 0 1px 0 rgba(255, 255, 255, .14), inset 0 -1px 0 rgba(255, 255, 255, .05); }
    html[data-theme="dark"] .band::before { background: linear-gradient(115deg, rgba(255, 255, 255, .12) 0%, rgba(255, 255, 255, .03) 26%, transparent 46%); }
    @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
        .band { background: linear-gradient(135deg, color-mix(in srgb, var(--paper) 92%, transparent), color-mix(in srgb, var(--mist) 92%, transparent)); }
    }
    .band p { max-width: 52ch; }

    /* =========================================================
       3b. Aspek penilaian: Card Swap (tumpukan kartu bergantian)
       ========================================================= */
    .aspek { display: grid; grid-template-columns: .85fr 1.15fr; gap: 36px; align-items: center; margin-top: 44px; }
    .aspek .sec-head { margin: 0 0 20px; flex-direction: column; gap: 12px; }
    .scale { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-top: 4px; font-size: 12.5px; }

    .swap { --dx: 46px; --dy: 40px; --h: 262px; position: relative; height: calc(var(--h) + var(--dy) * 2); perspective: 1100px; }
    .sw { --slot: 0; position: absolute; left: 0; bottom: 0; width: calc(100% - var(--dx) * 2); height: var(--h); z-index: calc(10 - var(--slot));
        display: flex; flex-direction: column; justify-content: space-between; cursor: pointer; will-change: transform;
        transform: translate3d(calc(var(--slot) * var(--dx)), calc(var(--slot) * var(--dy) * -1), calc(var(--slot) * -70px));
        transition: transform .8s cubic-bezier(.5, 0, .2, 1), opacity .5s ease; }
    .sw:focus-visible { outline: 3px solid var(--lime); outline-offset: 3px; }
    .sw:nth-child(2) { --slot: 1; }
    .sw:nth-child(3) { --slot: 2; }
    .sw.is-drop { opacity: 0; transform: translate3d(0, 125%, 0) rotate(-3deg); transition: transform .55s cubic-bezier(.6, 0, .9, .4), opacity .5s ease .08s; }
    .sw-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .sw-no { display: inline-grid; place-items: center; width: 34px; height: 34px; border-radius: 50%; border: 1.5px solid currentColor; font: 700 13px var(--display); }
    .sw h3 { font-size: 24px; margin: 14px 0 8px; }
    .sw p { margin: 0; max-width: 46ch; }
    .sw-lime { background: var(--lime); color: #1b1b1b; }
    .sw-lime .muted { color: #3b3b37; }
    .sw-rail { position: relative; height: 12px; margin-top: 14px; border-bottom: 1.5px solid currentColor;
        background: repeating-linear-gradient(to right, currentColor 0 1.5px, transparent 1.5px 25%); background-size: calc(100% - 1.5px) 7px; background-repeat: repeat-x; background-position: 0 100%; }
    .sw-rail-labels { display: flex; justify-content: space-between; margin-top: 4px; font: 700 11px var(--mono); }
    .sw .lime-tag { font: 700 12px var(--mono); color: var(--lime); }
    .sw-lime .lime-tag, .sw:not(.card-dark) .lime-tag { color: inherit; }

    /* =========================================================
       Mode gelap khusus landing (token warna global ada di layout)
       ========================================================= */
    html[data-theme="dark"] .spec::before { background: radial-gradient(96px circle at var(--bx, 50%) var(--by, 0%), rgba(196, 245, 93, .8), rgba(196, 245, 93, .2) 50%, transparent 74%); }
    html[data-theme="dark"] .spec::after { background: linear-gradient(105deg, transparent, rgba(196, 245, 93, .75), transparent); }
    html[data-theme="dark"] .sw-lime { background: var(--lime); color: #1b1b1b; border-color: #1b1b1b; box-shadow: 0 4px 0 rgba(0, 0, 0, .5); }
    @media (min-width: 821px) {
        .hero .st-w.is-hl {
            -webkit-text-stroke-color: transparent;
        }
    }
    html:not([data-theme="dark"]) .hero .st-w.is-hl {
        filter: drop-shadow(0 1px 0 rgba(27, 27, 27, .35));
    }

    /* =========================================================
       Responsif
       ========================================================= */
    @media (max-width: 820px) {
        .hero, .band, .aspek { grid-template-columns: 1fr; }
        .page-bg { inset: 0 -16px; }
        .band { padding: 24px 20px; }
        .brands { grid-template-columns: 1fr; gap: 14px; }
        .brands-label { border-right: 0; padding-right: 0; }
        .swap { --dx: 22px; --dy: 26px; --h: 286px; }
        .sw h3 { font-size: 21px; }
        html { scroll-padding-top: 96px; }
    }

    /* =========================================================
       Reduced motion: tampilkan keadaan akhir tanpa animasi
       ========================================================= */
    @media (prefers-reduced-motion: reduce) {
        .st-w { animation: none; clip-path: none; background-size: 100% 100%; }
        .hero .lead, .hero-actions, .hero-eyebrow, .cv { animation: none; }
        .cv-float, .cv-seam-pulse { animation: none; }
        .js .reveal { opacity: 1; translate: 0 0; transition: none; }
        .cloud-track { animation: none; }
        .cloud-set[aria-hidden="true"] { display: none; }
        .spec:hover::after { animation: none; }
        .sw, .sw.is-drop { transition: none; }
    }
    /* ... kode lama */

    /* =========================================================
       Mikrodinamika: melayang, hover, kedalaman saat digulir
       (hanya desktop dengan kursor; mati di layar sentuh/mobile dan saat reduced-motion)
       ========================================================= */
    .sw { transition: transform .8s cubic-bezier(.5, 0, .2, 1), opacity .5s ease, translate .3s ease, filter .3s ease; }
    .sw-no { transition: scale .3s ease, rotate .3s ease; }
    .logo { transition: color .25s, scale .3s cubic-bezier(.3, 1.4, .5, 1); }
    .hiw-seg b { display: inline-block; transition: scale .3s cubic-bezier(.3, 1.4, .5, 1); }
    .scale .badge { transition: scale .3s ease; }

    @media (hover: hover) and (pointer: fine) {
        .sw:hover, .sw:focus-visible { translate: 0 -6px; filter: drop-shadow(0 12px 22px color-mix(in srgb, var(--lime) 38%, transparent)); }
        .sw-lime:hover, .sw-lime:focus-visible { filter: drop-shadow(0 12px 22px rgba(27, 27, 27, .22)); }
        .sw:hover .sw-no, .sw:focus-visible .sw-no { scale: 1.15; rotate: -8deg; }
        .logo:hover { scale: 1.07; }
        .hiw-seg:hover b, .hiw-seg:focus-visible b { scale: 1.2; }
        .scale .badge:hover { scale: 1.08; }
    }
    @media (prefers-reduced-motion: reduce) {
        .sw, .sw-no, .logo, .hiw-seg b, .scale .badge { transition: none; }
    }

    /* =========================================================
       Mobile (<= 820px, saat menu hamburger aktif). Desktop tidak terpengaruh.
       ========================================================= */
    .m-cta { display: none; }

    @media (max-width: 820px) {
        /* Tanpa scroll horizontal */
        html, body { overflow-x: clip; max-width: 100%; }
        #isi { overflow-x: clip; }
        .page-bg { inset: 0; }
        .hero > *, .aspek > *, .band > *, .brands > * { min-width: 0; }

        /* Hero: ringkas, CTA penuh lebar, mesin 3D disembunyikan */
        .hero { gap: 0; padding: 2px 0 10px; }
        .cv { display: none; }
        .hero-eyebrow { font-size: 10.5px; letter-spacing: .14em; margin-bottom: 14px; }
        .hero-bg { left: 0; right: 0; }
        .hero h1 { font-size: clamp(28px, 8.6vw, 36px); line-height: 1.08; margin-bottom: .35em; }
        .st-sub { font-size: .5em; line-height: 1.35; margin-top: .55em; }
        .hero .lead { font-size: 14.5px; line-height: 1.55; margin: 0; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .hero-actions { flex-direction: column; gap: 10px; margin-top: 18px; }
        .hero-actions .btn { width: 100%; min-height: 52px; font-size: 14px; }
        .hero-actions .btn[href="#cara-kerja"] { display: none; }

        /* Showcase brand */
        .brands { gap: 8px; padding: 12px 0; margin-top: 6px; }
        .brands-label { font-size: 11.5px; text-align: center; white-space: normal; }
        .cloud::before, .cloud::after { width: 36px; }
        .cloud-set { gap: 30px; padding-right: 30px; }
        .logo { font-size: 15px; gap: 8px; }
        .logo-img { height: 22px; max-width: 100px; }
        .logo-mark { width: 26px; height: 26px; border-radius: 8px; }
        .logo-mark svg { width: 16px; height: 16px; }

        /* Cara kerja: daftar langkah statis (adegan 3D dimatikan di JS) */
        .hiw .sec-head { margin-bottom: 12px; }
        .hiw:not(.is-js) .hiw-copy { gap: 12px; }
        .hiw:not(.is-js) .hiw-step { padding: 16px; border: 1.5px solid var(--ink); border-radius: 14px; background: var(--surface); }
        .hiw:not(.is-js) .hiw-step h3 { font-size: 21px; line-height: 1.15; margin: 0 0 8px; }
        .hiw:not(.is-js) .hiw-step > p { margin: 0; font-size: 14px; line-height: 1.5; max-width: none; }
        .hiw:not(.is-js) .hiw-eye { font-size: 11px; margin: 0 0 6px; }

        /* Aspek penilaian: kartu geser (swipe) menggantikan tumpukan 3D */
        .aspek { gap: 14px; margin-top: 28px; }
        .scale { gap: 6px; font-size: 12px; }
        .swap { display: flex; height: auto !important; gap: 12px; margin: 0 -14px; padding: 4px 14px 14px; overflow-x: auto;
            scroll-snap-type: x mandatory; scroll-padding-inline: 14px; perspective: none; scrollbar-width: none; -webkit-overflow-scrolling: touch; }
        .swap::-webkit-scrollbar { display: none; }
        .sw, .sw.is-drop { position: relative !important; inset: auto !important; flex: 0 0 84%; width: auto !important; height: auto !important; min-height: 220px;
            transform: none !important; translate: none !important; filter: none !important; opacity: 1 !important; z-index: auto !important;
            scroll-snap-align: start; transition: none !important; will-change: auto; }
        .sw h3 { font-size: 20px; margin: 12px 0 6px; }
        .sw p { font-size: 13.5px; line-height: 1.5; }

        /* Brand reveal: teks dan tombol penuh, robot disembunyikan */
        .band { margin-top: 28px; padding: 22px 18px; gap: 14px; border-radius: 18px; text-align: center; }
        .band > :nth-child(n+2) { display: none; }
        .band h2 { font-size: 22px; line-height: 1.2; }
        .band p { font-size: 14px; max-width: none; }
        .band .btn { width: 100%; min-height: 52px; justify-content: center; text-align: center; }

        /* Bilah aksi bawah (zona jempol): muncul setelah tombol hero tergulir keluar layar */
        .m-cta { display: flex; position: fixed; left: 0; right: 0; bottom: 0; z-index: 44; gap: 10px;
            padding: 10px 14px calc(10px + env(safe-area-inset-bottom, 0px));
            background: color-mix(in srgb, var(--paper) 94%, transparent);
            -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px);
            border-top: 1.5px solid var(--ink); transition: transform .28s ease, opacity .28s ease; }
        .m-cta.is-off { transform: translateY(110%); opacity: 0; pointer-events: none; }
        .m-cta .btn { flex: 1; min-height: 50px; font-size: 14px; }
        .shell:has(.m-cta) .foot { padding-bottom: calc(84px + env(safe-area-inset-bottom, 0px)); }
    }

    @media (max-width: 400px) {
        .hero h1 { font-size: 28px; }
        .sw { flex-basis: 88%; }
    }

    /* =========================================================
       Hero baru: layout Syntra (HeroCopy + CoreVisual + HeroFloor)
       Navbar, judul Stroke Text, dan tombol tetap milik Essay Grader.
       ========================================================= */
    .hero-wrap { position: relative; }

    /* Latar hero: polos (warna halaman), menutup latar kursor lama hanya di area hero,
       lalu memudar halus ke latar beranimasi section di bawahnya. */
    .hero-bg { position: absolute; top: 0; bottom: -180px; left: -36px; right: -36px; z-index: -1; pointer-events: none; overflow: hidden;
        background: var(--hero-paper, #ffffff);
        -webkit-mask-image: linear-gradient(to bottom, #000 0, #000 calc(100% - 180px), transparent 100%);
                mask-image: linear-gradient(to bottom, #000 0, #000 calc(100% - 180px), transparent 100%); }

    /* Lantai isometrik: abu-abu tipis + salinan lime yang terlihat lewat jendela bulat di bawah kursor */
    .hero-floor { position: absolute; inset: 0; z-index: 0; overflow: hidden; pointer-events: none;
        -webkit-mask-image: linear-gradient(to bottom, transparent 0, rgba(0,0,0,.18) 36px, rgba(0,0,0,.6) 84px, #000 150px);
                mask-image: linear-gradient(to bottom, transparent 0, rgba(0,0,0,.18) 36px, rgba(0,0,0,.6) 84px, #000 150px); }
    .hero-floor .fl, .hero-floor .fl-lit {
        --fl-area: radial-gradient(ellipse 75% 80% at 72% 60%, #000 20%, transparent 100%);
        --fl-edges: linear-gradient(to left, transparent 0%, #000 22%), linear-gradient(to top, transparent 0%, #000 28%);
        -webkit-mask-image: var(--fl-area), var(--fl-edges); mask-image: var(--fl-area), var(--fl-edges);
        -webkit-mask-composite: source-in; mask-composite: intersect; }
    .hero-floor .fl { position: absolute; inset: 0; width: 100%; height: 100%; opacity: .4; color: color-mix(in srgb, var(--ink) 26%, transparent); }
    .hero-floor .fl-spot { position: absolute; top: 0; left: 0; opacity: 0; transition: opacity .25s ease; will-change: transform, opacity;
        -webkit-mask-image: radial-gradient(closest-side, #000 0%, transparent 100%); mask-image: radial-gradient(closest-side, #000 0%, transparent 100%); }
    .hero-floor .fl-lit { position: absolute; top: 0; left: 0; will-change: transform; color: color-mix(in srgb, var(--lime) 88%, #2a3a00); }
    .hero-floor .fl-lit svg { display: block; width: 100%; height: 100%; }
    .hero-floor[data-spot="on"] .fl-spot { opacity: var(--spot-o, 1); }

    @media (min-width: 821px) {
        section.hero { grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr); gap: 24px; align-items: center; padding: 0 0 8px; }
    }
    .hero-copy { display: flex; flex-direction: column; justify-content: center; min-width: 0; }
    .hero-eyebrow { display: flex; align-items: center; gap: 12px; margin: 0 0 22px; font: 600 12px var(--mono, ui-monospace, monospace); letter-spacing: .2em; text-transform: uppercase;
        color: color-mix(in srgb, var(--ink) 62%, transparent); animation: rise .7s ease both; animation-delay: .05s; }
    .hero-eyebrow .dot { flex: none; width: 9px; height: 9px; border-radius: 50%; background: var(--lime);
        box-shadow: 0 0 0 1.5px color-mix(in srgb, var(--ink) 55%, transparent), 0 0 0 5px color-mix(in srgb, var(--lime) 40%, transparent), 0 0 14px 2px var(--lime); }

    /* Ilustrasi inti 3D */
    .cv { --cv-grey: color-mix(in srgb, var(--ink) 58%, transparent); --cv-accent-ink: color-mix(in srgb, var(--lime) 50%, var(--ink));
        position: relative; width: 100%; max-width: 760px; aspect-ratio: 1 / 1; margin: -9% 0 -11% 0; container-type: inline-size; overflow: clip;
        animation: cv-enter 1.1s cubic-bezier(.22, 1, .36, 1) .25s backwards; }
    @keyframes cv-enter { from { opacity: 0; scale: .97; } }
    .cv-in { position: absolute; inset: 0; transform: translateY(5%); }
    .cv-layer { position: absolute; inset: 0; pointer-events: none; }
    .cv-layer svg { position: absolute; inset: 0; width: 100%; height: 100%; overflow: visible; }
    .cv-halo { position: absolute; inset: 6%; border-radius: 50%; pointer-events: none; background: radial-gradient(closest-side, color-mix(in srgb, var(--surface, #fff) 90%, transparent), transparent); }
    .cv-fade { position: absolute; inset: 0; -webkit-mask-image: linear-gradient(to right, transparent 0%, #000 14%); mask-image: linear-gradient(to right, transparent 0%, #000 14%); }
    .cv-persp { position: absolute; inset: 0; pointer-events: none; perspective: 1400px; }
    .cv-rot { position: absolute; inset: 0; }
    .cv-float { position: absolute; inset: 0; animation: cv-float 6.5s ease-in-out infinite alternate; }
    @keyframes cv-float { to { transform: translateY(-6px); } }
    .cv-seam-pulse { animation: cv-seam 4.8s ease-in-out infinite alternate; }
    @keyframes cv-seam { from { opacity: .78; } to { opacity: 1; } }
    .cv-ink { color: color-mix(in srgb, var(--ink) 20%, transparent); }
    .cv-conn { position: absolute; inset: 0; pointer-events: none; }
    .cv-conn svg { position: absolute; inset: 0; width: 100%; height: 100%; overflow: visible; pointer-events: none; }
    .cv-conn .lit-line, .cv-conn .halo { transition: opacity .25s ease; }

    .cv-hint { position: absolute; top: 84%; left: 50%; transform: translateX(-50%); margin: 0; display: flex; align-items: center; gap: 6px; font-size: 12.5px; white-space: nowrap; pointer-events: none; color: var(--cv-grey); }
    .cv-hint svg { width: 14px; height: 14px; color: var(--cv-accent-ink); }
    .cv-hint .tap { display: none; }
    @media (hover: none) { .cv-hint .hov { display: none; } .cv-hint .tap { display: inline; } }
    .cv-hit { position: absolute; top: 35%; left: 22%; width: 56%; height: 44%; border: 0; padding: 0; background: none; cursor: default; border-radius: 3rem; }
    .cv-hit,
    .cv-hit:hover, .cv-hit:focus, .cv-hit:focus-visible, .cv-hit:active,
    .hero-wrap .cv button, .hero-wrap .cv button:hover, .hero-wrap .cv button:focus,
    .hero-wrap .cv button:focus-visible, .hero-wrap .cv button:active {
        outline: none !important;
        box-shadow: none !important;
        border: 0 !important;
        background: none !important;
        filter: none !important;
        transform: none !important;
        translate: none !important;
        -webkit-tap-highlight-color: transparent;
    }
    .cv-hit::before, .cv-hit::after { content: none !important; }

    /* Kartu tahap: kaca terang yang menutupi garis di belakang teks */
    .cv-cards { position: absolute; inset: 0; margin: 0; padding: 0; list-style: none; pointer-events: none; font-size: clamp(10px, 1.7cqw, 15px); }
    .cv-cards > li { position: absolute; transform: translateY(-50%); }
    .cv-card { opacity: 0; padding: .85em .9em; border: none; border-radius: 1.3em;
    background: color-mix(in srgb, var(--surface, #fff) 86%, transparent); color: var(--ink);
    box-shadow: 0 1px 2px rgb(60 50 40 / .05), 0 14px 34px -18px rgb(60 50 40 / .35), inset 0 1px 0 rgb(255 255 255 / .35);
    -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px); transition: box-shadow .3s ease; }
    .cv.is-open .cv-card { pointer-events: auto; }
    .cv-card.is-lit { border: none;
        box-shadow: 0 1px 2px rgb(60 50 40 / .05), 0 14px 34px -18px rgb(60 50 40 / .35), inset 0 1px 0 rgb(255 255 255 / .35); }
    .cv-cb { display: grid; grid-template-columns: 1.35em minmax(0, 1fr) auto; column-gap: .5em; align-items: start; }
    .cv-no { grid-column: 1; grid-row: 1; display: flex; align-items: center; height: 1.64em; font-size: .8em; font-weight: 500; color: var(--cv-grey); font-variant-numeric: tabular-nums; }
    .cv-ttl { grid-column: 2; grid-row: 1; display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; column-gap: .6em; }
    .cv-ttl p { margin: 0; font-size: 1.05em; line-height: 1.25; font-weight: 600; letter-spacing: -.015em; white-space: nowrap; color: var(--ink); }
    .cv-main-tag { flex: none; font-size: .72em; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--cv-accent-ink); }
    .cv-ic { grid-column: 1; grid-row: 2; margin-top: .4em; display: flex; align-items: center; height: 1.276em; }
    .cv svg.ln { fill: none; stroke: currentColor; stroke-width: 1.7; stroke-linecap: round; stroke-linejoin: round; }
    .cv-ap { display: inline-flex; color: var(--cv-accent-ink); }
    .cv-ic svg { width: 1.25em; height: 1.25em; color: var(--cv-accent-ink); }
    .cv-it { grid-column: 2; grid-row: 2; margin-top: .4em; min-width: 0; }
    .cv-it p { margin: 0; font-size: .88em; line-height: 1.45; color: var(--cv-grey); }
    .cv-main .cv-ttl, .cv-main .cv-it { grid-column: 2 / span 2; }
    .cv-apps { display: flex; align-items: center; flex: none; gap: .3em; }
    .cv-apps svg { width: 1.45em; height: 1.45em; }
    .cv-side { grid-column: 3; grid-row: 1 / span 2; align-self: center; }
    .cv-main .cv-apps { gap: .45em; margin-top: .75em; justify-content: flex-end; }
    .cv-tile { display: grid; place-items: center; width: 2.15em; height: 2.15em; border-radius: .6em; border: 1px solid rgb(0 0 0 / .1); background: #fff; color: #171717; box-shadow: 0 1px 2px rgb(60 50 40 / .06); }
    .cv-tile svg { width: 1.2em; height: 1.2em; }
    @container (max-width: 640px) { .cv-apps { display: none; } }

    /* =========================================================
       Hero: tanpa outline/garis tepi saat hover, fokus, atau aktif
       ========================================================= */
    .hero-wrap .hero *:hover, .hero-wrap .hero *:focus, .hero-wrap .hero *:focus-visible, .hero-wrap .hero *:active,
    .hero-wrap .cv *:hover, .hero-wrap .cv *:focus, .hero-wrap .cv *:focus-visible, .hero-wrap .cv *:active { outline: none !important; outline-offset: 0 !important; -webkit-tap-highlight-color: transparent; }
    .hero-wrap .cv .cv-card, .hero-wrap .cv .cv-card:hover, .hero-wrap .cv .cv-card.is-lit { border: none; outline: none;
        box-shadow: 0 1px 2px rgb(60 50 40 / .05), 0 14px 34px -18px rgb(60 50 40 / .35), inset 0 1px 0 rgb(255 255 255 / .35); }
    .hero-wrap .cv .cv-conn .lit-line, .hero-wrap .cv .cv-ai-rim, .hero-wrap .cv .cv-ai-halo { display: none; }
    .hero-wrap .cv svg, .hero-wrap .cv .cv-layer, .hero-wrap .cv .cv-hit { outline: none; }
    .hero-actions .spec:hover, .hero-actions .spec:focus, .hero-actions .spec:focus-visible, .hero-actions .spec:active { outline: none; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .28); }
    .hero-actions .spec.btn-outline:hover, .hero-actions .spec.btn-outline:focus, .hero-actions .spec.btn-outline:focus-visible, .hero-actions .spec.btn-outline:active { box-shadow: inset 0 -2px 0 rgba(27, 27, 27, .07); }

    /* =========================================================
       Ilustrasi 3D: mode terang = komponen dibalik menjadi hitam
       (mode gelap tidak diubah; aksen lime tetap)
       ========================================================= */
    html:not([data-theme="dark"]) .cv #cv-side stop:nth-child(1) { stop-color: #31312e; }
    html:not([data-theme="dark"]) .cv #cv-side stop:nth-child(2) { stop-color: #292927; }
    html:not([data-theme="dark"]) .cv #cv-side stop:nth-child(3) { stop-color: #161615; }
    html:not([data-theme="dark"]) .cv #cv-side stop:nth-child(4) { stop-color: #222220; }
    html:not([data-theme="dark"]) .cv #cv-side-shade stop { stop-color: #000; }
    html:not([data-theme="dark"]) .cv #cv-bevel stop:nth-child(1) { stop-color: #3b3b38; }
    html:not([data-theme="dark"]) .cv #cv-bevel stop:nth-child(2) { stop-color: #1f1f1d; }
    html:not([data-theme="dark"]) .cv #cv-top stop:nth-child(1) { stop-color: #2b2b29; }
    html:not([data-theme="dark"]) .cv #cv-top stop:nth-child(2) { stop-color: #1a1a19; }
    html:not([data-theme="dark"]) .cv #cv-inlay stop:nth-child(1) { stop-color: #1b1b19; }
    html:not([data-theme="dark"]) .cv #cv-inlay stop:nth-child(2) { stop-color: #302f2c; }
    html:not([data-theme="dark"]) .cv #cv-slot stop:nth-child(1) { stop-color: #b9b4a8; }
    html:not([data-theme="dark"]) .cv #cv-slot stop:nth-child(2) { stop-color: #8a867b; }
    html:not([data-theme="dark"]) .cv #cv-hole stop:nth-child(1) { stop-color: #0c0c0b; }
    html:not([data-theme="dark"]) .cv #cv-hole stop:nth-child(2) { stop-color: #2b2b28; }
    html:not([data-theme="dark"]) .cv #cv-glass stop { stop-color: #1b1b1b; }
    html:not([data-theme="dark"]) .cv .cv-s-body { stroke: #4b4b45; }
    html:not([data-theme="dark"]) .cv .cv-s-bevel { stroke: #55554f; }
    html:not([data-theme="dark"]) .cv .cv-s-top { stroke: #6b6b63; }
    html:not([data-theme="dark"]) .cv .cv-s-groove { stroke: #3c3c37; }
    html:not([data-theme="dark"]) .cv .cv-s-panel { stroke: #6b6b62; }
    html:not([data-theme="dark"]) .cv .cv-s-fast { stroke: #4d4d47; }
    html:not([data-theme="dark"]) .cv .cv-s-inl { stroke: #45453f; }
    html:not([data-theme="dark"]) .cv .cv-s-inl2 { stroke: #6b6b63; }
    html:not([data-theme="dark"]) .cv .cv-s-glass { stroke: #3a3a36; }
    html:not([data-theme="dark"]) .cv .cv-nd { fill: #1b1b1b; stroke: #55554f; }
    html:not([data-theme="dark"]) .cv .cv-n-sh { fill: #000; }
    html:not([data-theme="dark"]) .cv .cv-n-lb { fill: #77776e; }
</style>
@endpush

@section('content')
@php
    $mode = config('registration.mode', 'open');
    $signupOpen = $mode === 'open' || ($mode === 'invite' && filled(config('registration.code')));
    $steps = [
        ['Hubungkan Google', 'Beri izin baca saja, lalu tempel link Form dari mode edit.', 'step-1'],
        ['Form dibaca otomatis', 'AI memisahkan kolom identitas dari pertanyaan esai. Anda bisa mengoreksinya.', 'step-2'],
        ['Isi rubrik', 'Tulis rubrik (opsional) tiap pertanyaan, lalu aktifkan penilaian.', 'step-3'],
        ['Nilai dan unduh', 'Jawaban ditarik tiap menit, dinilai, dan siap diunduh ke Excel.', 'step-4'],
    ];
    $aspects = [
        ['Pemahaman Konten', 'Isi jawaban dicocokkan dengan kunci berdasarkan makna, bukan kesamaan kata. Parafrase yang benar tetap dihargai.'],
        ['Logika Argumen', 'Alur penalaran dan hubungan sebab-akibat dalam jawaban, disesuaikan dengan tuntutan pertanyaan.'],
        ['Struktur Tulisan', 'Keteraturan dan kejelasan penyampaian. Typo ringan tidak banyak mengurangi nilai.'],
    ];
    // Lama tampil tiap kartu aspek = estimasi waktu baca judul + deskripsi (sekitar 360 kata/menit) + 1 detik untuk transisi.
    // Dibatasi 3,2 - 5,5 detik, jadi aspek yang teksnya lebih panjang tampil sedikit lebih lama.
    $aspectMs = array_map(function ($a) {
        $words = count(preg_split('/\s+/u', trim($a[0] . ' ' . $a[1])));
        return (int) max(3200, min(5500, round($words / 6 * 1000 + 1000)));
    }, $aspects);
    $swapStyle = ['card card-dark', 'card sw-lime', 'card'];
    $badgeOf = ['Sangat Baik' => 'ok', 'Baik' => 'info', 'Cukup' => 'warn', 'Kurang' => 'bad'];

    // Judul hero dipecah per kata agar tiap kata bisa digambar bergantian (Stroke Text)
    $titleSegments = [['Nilai esai Google Form', ''], ['lebih cepat.', 'is-hl']];
    $titleSub = 'Keputusan akhir tetap di tangan Anda.';
    $w = 0;
@endphp
@include('landing-mobile', ['part' => 'header'])
<div class="page-bg" aria-hidden="true"><canvas></canvas></div>

@php
    // Lima tahap alur kerja pada ilustrasi inti 3D (urut dari bawah ke atas).
    $coreStages = [
        ['title' => 'Input Formulir', 'items' => ['Google Forms', 'Link Responden', 'Data Jawaban'], 'apps' => ['forms', 'link', 'google'], 'icon' => 'form', 'side' => 'right'],
        ['title' => 'Rubrik & Konteks', 'items' => ['Kriteria Nilai', 'Bobot Soal', 'Kunci Jawaban'], 'apps' => ['list', 'sliders', 'key'], 'icon' => 'pen', 'side' => 'left'],
        ['title' => 'AI Grader Engine', 'items' => ['Analisis Konten', 'Logika', 'Struktur Tulisan'], 'apps' => ['gemini', 'ollama', 'groq'], 'side' => 'right', 'main' => true, 'tag' => 'Main layer'],
        ['title' => 'Review & Koreksi', 'items' => ['Tinjau Skor', 'Penyesuaian Manual', 'Catatan'], 'apps' => ['eye', 'pencil', 'note'], 'icon' => 'check', 'side' => 'left'],
        ['title' => 'Hasil & Rekap', 'items' => ['Google Sheets', 'Laporan Nilai', 'Ekspor Data'], 'apps' => ['sheets', 'excel', 'chart'], 'icon' => 'chart', 'side' => 'right'],
    ];
    $startUrl = $signupOpen ? route('register') : route('login');
@endphp
<div class="hero-wrap">
    {{-- Latar hero polos + lantai isometrik yang mengikuti kursor; memudar ke latar beranimasi di bawahnya --}}
    <div class="hero-bg" aria-hidden="true"><div class="hero-floor"></div></div>

    <section class="hero">
        <div class="hero-copy">
            <p class="hero-eyebrow"><span class="dot" aria-hidden="true"></span>Platform penilaian otomatis berbasis AI</p>
        <h1>
            <span class="st-main">
                @foreach($titleSegments as [$text, $cls])
                    @foreach(explode(' ', $text) as $word)
                        <span class="st-w {{ $cls }}" style="--i:{{ $w++ }}">{{ $word }}</span>
                    @endforeach
                @endforeach
            </span>
            <span class="st-sub">
                @foreach(explode(' ', $titleSub) as $word)
                    <span class="st-w" style="--i:{{ $w++ }}">{{ $word }}</span>
                @endforeach
            </span>
        </h1>
        <p class="lead">Tempel link Form, isi rubrik, dan AI menilai pemahaman konten, logika, dan struktur tulisan tiap responden. Anda tinggal meninjau dan mengoreksi.</p>
            <div class="hero-actions">
                <a class="btn spec" href="{{ $startUrl }}" data-rocket><span class="spec-label">Mulai</span></a>
                <a class="btn btn-outline spec" href="#cara-kerja"><span class="spec-label">Lihat cara kerja</span></a>
            </div>
        </div>
        {{-- Ilustrasi inti 3D: lima plat = lima tahap alur kerja (dirakit oleh skrip hero di bawah) --}}
        <div class="cv" id="cv"></div>
    </section>
</div>

@php
    // Logo showcase: nama app, berkas SVG di public/images/, dan ikon cadangan (dipakai bila berkasnya belum ada).
    // Ganti nama berkas di kolom ke-2 sesuai nama SVG yang Anda simpan (huruf besar/kecil harus sama di server Linux).
    $showNames = true;   // false bila SVG Anda sudah berupa wordmark yang memuat tulisan nama app
    $tools = [
        ['Akun Google', 'akun_google.svg', '<path d="M9 3v4M15 3v4"/><path d="M7 7h10v4a5 5 0 0 1-10 0V7z"/><path d="M12 16v5"/>'],
        ['Google Forms', 'google_forms.svg', '<rect x="5" y="3" width="14" height="18" rx="2.5"/><path d="M5 8h14"/><rect x="8" y="11" width="2.6" height="2.6" rx=".7"/><path d="M12.8 12.3H16"/><rect x="8" y="16" width="2.6" height="2.6" rx=".7"/><path d="M12.8 17.3H16"/>'],
        ['Console groq API', 'groq_ai.svg', '<path d="M12 3l6 9-6 9-6-9z"/><path d="M6 12h12"/><ellipse cx="12" cy="12" rx="10" ry="3.4" transform="rotate(-24 12 12)"/>'],
        ['Microsoft Excel', 'microsoft_excel.svg', '<rect x="3.5" y="3.5" width="17" height="17" rx="3"/><path d="M3.5 9.5h17M3.5 15h17M9.5 9.5v11M15.5 9.5v11"/>'],
    ];
@endphp

<div class="brands reveal">
    <p class="brands-label">Terhubung dengan</p>
    <div class="cloud">
        <span class="sr-only">{{ implode(', ', array_column($tools, 0)) }}</span>
        <div class="cloud-track" aria-hidden="true">
            @for($set = 0; $set < 2; $set++)
                <div class="cloud-set" @if($set) aria-hidden="true" @endif>
                    @for($round = 0; $round < 2; $round++)
                        @foreach($tools as [$toolName, $toolFile, $toolIcon])
                            <span class="logo">
                                @if(file_exists(public_path('images/' . $toolFile)))
                                    <img class="logo-img" src="{{ asset('images/' . $toolFile) }}" alt="" loading="lazy" decoding="async" draggable="false">
                                @else
                                    <span class="logo-mark"><svg viewBox="0 0 24 24">{!! $toolIcon !!}</svg></span>
                                @endif
                                @if($showNames){{ $toolName }}@endif
                            </span>
                        @endforeach
                    @endfor
                </div>
            @endfor
        </div>
    </div>
</div>

@include('landing-mobile', ['part' => 'sections'])

<section id="cara-kerja" class="hiw" data-s="0" aria-labelledby="hiw-judul">
    <div class="sec-head">
        <h2 class="tag" id="hiw-judul">Cara kerja</h2>
        <p>Biarkan sistem bekerja otomatis.</p>
    </div>

    <div class="hiw-stage" data-hiw>
        {{-- Adegan 3D isometrik dibangun oleh JavaScript di bawah (dekoratif, disembunyikan dari pembaca layar) --}}
        <div class="hiw-scene" aria-hidden="true"><div class="hiw-world"></div></div>

        <div class="hiw-copy" aria-live="polite">
            <article class="hiw-step on">
                <p class="hiw-eye">Langkah 1 &middot; Input</p>
                <h3>Hubungkan <mark>Google Form</mark></h3>
                <p>Tempelkan link Google Form Anda dari mode edit untuk memulai integrasi sistem.</p>
                <div class="hw">
                    <div class="hw-row"><b>Link Form</b><span class="mono">mode edit</span></div>
                    <div class="hw-field">docs.google.com/forms/d/1aB&hellip;/edit<span class="caret"></span></div>
                    <span class="hw-btn">Hubungkan</span>
                </div>
            </article>

            <article class="hiw-step">
                <p class="hiw-eye">Langkah 2 &middot; Rubrik</p>
                <h3>Atur <mark>Kunci &amp; Rubrik</mark></h3>
                <p>Tentukan parameter penilaian dan aktifkan ujian dengan satu klik.</p>
                <div class="hw">
                    <div class="hw-row"><b>Penilaian aktif</b><span class="hw-sw"></span></div>
                    <div class="hw-list"><div>Pemahaman Konten <span>40%</span></div><div>Logika Argumen <span>30%</span></div><div>Struktur Tulisan <span>30%</span></div></div>
                </div>
            </article>

            <article class="hiw-step">
                <p class="hiw-eye">Langkah 3 &middot; Backend</p>
                <h3>Penangkapan Otomatis <mark>Backend</mark></h3>
                <p>System worker (schedule:work) siaga 24/7 menarik jawaban siswa secara real-time.</p>
                <div class="hw">
                    <div class="hw-row"><b><span class="hw-dot"></span>schedule:work</b><span class="mono">tiap 1 menit</span></div>
                    <div class="hw-list"><div>Jawaban baru ditarik <span>24</span></div><div>Antrean penilaian <span>siaga</span></div></div>
                </div>
            </article>

            <article class="hiw-step">
                <p class="hiw-eye">Langkah 4 &middot; AI</p>
                <h3>Pemindaian <mark>AI Cerdas</mark></h3>
                <p>Modul AI memindai makna kata kunci dan menghitung relevansi esai terhadap rubrik.</p>
                <div class="hw">
                    <div class="hw-row"><b>Relevansi terhadap rubrik</b><span class="mono">92%</span></div>
                    <div class="hw-list"><div>Kata kunci cocok <span>7 / 8</span></div><div>Parafrase dikenali <span>3</span></div></div>
                </div>
            </article>

            <article class="hiw-step">
                <p class="hiw-eye">Langkah 5 &middot; Tinjau</p>
                <h3>Peninjauan &amp; <mark>Koreksi Manual</mark></h3>
                <p>Hasil nilai otomatis ditampilkan transparan dan pengajar bebas menyesuaikan nilai kapan saja.</p>
                <div class="hw">
                    <div class="hw-row"><b>Nilai akhir (0-100)</b><span class="mono">dapat diedit</span></div>
                    <div class="hw-list"><div>Rina A. <span>86</span></div><div>Dimas P. <b class="edit">84</b></div><div>Salsa M. <span>91</span></div></div>
                </div>
            </article>

            <article class="hiw-step">
                <p class="hiw-eye">Langkah 6 &middot; Hasil</p>
                <h3>Grafik &amp; <mark>Unduh Excel</mark></h3>
                <p>Lihat statistik performa siswa via chart visual dan unduh rekapitulasi nilai ke format Excel.</p>
                <div class="hw">
                    <div class="hw-row"><b>Rekap nilai kelas</b><span class="mono">.csv</span></div>
                    <span class="hw-btn lime">Unduh Excel</span>
                </div>
            </article>
        </div>

        <div class="hiw-hint" aria-hidden="true"><span>Geser</span><svg viewBox="0 0 24 24"><path d="M4 12h16M14 6l6 6-6 6"/></svg></div>

        <div class="hiw-scroller" tabindex="0" role="group" aria-label="Geser ke kiri atau kanan untuk berpindah tahap cara kerja"><div class="hiw-track"></div></div>

        <nav class="hiw-prog" aria-label="Tahap cara kerja">
            <button type="button" class="hiw-seg on"><b>01</b><span>Form</span><em><i></i></em></button>
            <button type="button" class="hiw-seg"><b>02</b><span>Rubrik</span><em><i></i></em></button>
            <button type="button" class="hiw-seg"><b>03</b><span>Worker</span><em><i></i></em></button>
            <button type="button" class="hiw-seg"><b>04</b><span>Pemindai AI</span><em><i></i></em></button>
            <button type="button" class="hiw-seg"><b>05</b><span>Tinjau</span><em><i></i></em></button>
            <button type="button" class="hiw-seg"><b>06</b><span>Excel</span><em><i></i></em></button>
        </nav>
    </div>
</section>
<script>
(function () {
    'use strict';
    var sec = document.getElementById('cara-kerja');
    var stage = sec && sec.querySelector('[data-hiw]');
    if (!stage) { return; }
    if (window.matchMedia('(max-width: 820px)').matches) { return; }   // HP: daftar langkah statis, tanpa adegan 3D
    var world = stage.querySelector('.hiw-world'), scene = stage.querySelector('.hiw-scene');
    var scroller = stage.querySelector('.hiw-scroller'), hint = stage.querySelector('.hiw-hint');
    var steps = [].slice.call(stage.querySelectorAll('.hiw-step'));
    var segs = [].slice.call(stage.querySelectorAll('.hiw-seg'));
    var fills = segs.map(function (s) { return s.querySelector('i'); });
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function egSignal() { return window.egSignal ? window.egSignal() : undefined; }
    var N = 6;
    sec.classList.add('is-js');

    /* ---------- "Lihat cara kerja": gulir tepat ke panggung (tepat di bawah navbar), bukan ke awal section ---------- */
    function navOffset() {
        var c = document.querySelectorAll('header, nav, .navbar, .topbar'), i, cs, r;
        for (i = 0; i < c.length; i++) {
            cs = getComputedStyle(c[i]);
            if (cs.position === 'fixed' || cs.position === 'sticky') { r = c[i].getBoundingClientRect(); if (r.height) { return r.bottom; } }
        }
        return 112;
    }
    function goToStage(smooth) {
        var GAP = 10; // jarak label "LANGKAH 1" dari bawah navbar (px): besar = halaman berhenti lebih atas, kecil = lebih ke bawah
        var first = steps[0] && (steps[0].firstElementChild || steps[0]);
        var anchor = first || stage;
        var top = anchor.getBoundingClientRect().top + window.pageYOffset - navOffset() - GAP;
        window.scrollTo({ top: Math.max(0, top), behavior: (smooth && !reduce) ? 'smooth' : 'auto' });
    }
    [].slice.call(document.querySelectorAll('a[href="#cara-kerja"]')).forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            goToStage(true);
            try { history.replaceState(null, '', '#cara-kerja'); } catch (err) { /* abaikan */ }
        });
    });
    if (location.hash === '#cara-kerja') { window.addEventListener('load', function () { setTimeout(function () { goToStage(false); }, 60); }); }

    /* ---------- util ---------- */
    function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }
    function smooth(k) { k = clamp(k, 0, 1); return k * k * (3 - 2 * k); }
    function easeIO(k) { k = clamp(k, 0, 1); return k < .5 ? 4 * k * k * k : 1 - Math.pow(-2 * k + 2, 3) / 2; }
    function easeOut(k) { k = clamp(k, 0, 1); return 1 - Math.pow(1 - k, 3); }

    /* ---------- jalur dokumen (polyline dengan sudut membulat, diukur per panjang busur) ---------- */
    var CORNERS = [[-420, 0], [640, 0], [640, 800], [1520, 800], [1520, 1600], [2400, 1600], [2400, 2400]];
    var STN = [
        { x: 200,  y: 0,    dir: 'x' },
        { x: 640,  y: 400,  dir: 'y' },
        { x: 1080, y: 800,  dir: 'x' },
        { x: 1520, y: 1200, dir: 'y' },
        { x: 1960, y: 1600, dir: 'x' },
        { x: 2400, y: 2000, dir: 'y' }
    ];
    var pts = [], cl = [0];
    (function () {
        var R = 120, i, k, a, b, c, v1, v2, p1, p2, last;
        function unit(p, q) { var dx = q[0] - p[0], dy = q[1] - p[1], l = Math.hypot(dx, dy); return [dx / l, dy / l]; }
        function lineTo(q) {
            var p = pts[pts.length - 1], l = Math.hypot(q[0] - p[0], q[1] - p[1]), n = Math.max(1, Math.round(l / 14));
            for (var j = 1; j <= n; j++) { pts.push([p[0] + (q[0] - p[0]) * j / n, p[1] + (q[1] - p[1]) * j / n]); }
        }
        pts.push(CORNERS[0]);
        for (i = 1; i < CORNERS.length - 1; i++) {
            a = CORNERS[i - 1]; b = CORNERS[i]; c = CORNERS[i + 1];
            v1 = unit(a, b); v2 = unit(b, c);
            p1 = [b[0] - v1[0] * R, b[1] - v1[1] * R]; p2 = [b[0] + v2[0] * R, b[1] + v2[1] * R];
            lineTo(p1);
            for (k = 1; k <= 16; k++) {
                var t = k / 16, u = 1 - t;
                pts.push([u * u * p1[0] + 2 * u * t * b[0] + t * t * p2[0], u * u * p1[1] + 2 * u * t * b[1] + t * t * p2[1]]);
            }
        }
        lineTo(CORNERS[CORNERS.length - 1]);
        for (i = 1; i < pts.length; i++) { cl.push(cl[i - 1] + Math.hypot(pts[i][0] - pts[i - 1][0], pts[i][1] - pts[i - 1][1])); }
    })();
    var TOTAL = cl[cl.length - 1];
    function at(L) {
        L = clamp(L, 0, TOTAL);
        var lo = 0, hi = cl.length - 1, m;
        while (hi - lo > 1) { m = (lo + hi) >> 1; if (cl[m] <= L) { lo = m; } else { hi = m; } }
        var f = (L - cl[lo]) / ((cl[hi] - cl[lo]) || 1), p = pts[lo], q = pts[hi];
        return { x: p[0] + (q[0] - p[0]) * f, y: p[1] + (q[1] - p[1]) * f, a: Math.atan2(q[1] - p[1], q[0] - p[0]) * 180 / Math.PI };
    }
    var stL = STN.map(function (s) {
        var best = 0, bd = 1e9;
        for (var i = 0; i < pts.length; i++) { var d = Math.hypot(pts[i][0] - s.x, pts[i][1] - s.y); if (d < bd) { bd = d; best = i; } }
        return cl[best];
    });

    /* ---------- pembangun objek 3D ---------- */
    function box(o) {
        var w = o.w, d = o.d, h = o.h;
        return '<div class="bx ' + (o.cls || '') + '" style="left:' + o.x + 'px;top:' + o.y + 'px;transform:translateZ(' + (o.z || 0) + 'px);--c:' + (o.c || '#fff') + '">' +
            '<i class="t" style="left:0;top:0;width:' + w + 'px;height:' + d + 'px;transform:translateZ(' + h + 'px)">' + (o.top || '') + '</i>' +
            '<i class="f" style="left:0;top:' + (d - h) + 'px;width:' + w + 'px;height:' + h + 'px;transform-origin:50% 100%;transform:rotateX(-90deg)">' + (o.front || '') + '</i>' +
            '<i class="l" style="left:0;top:0;width:' + h + 'px;height:' + d + 'px;transform-origin:0 0;transform:rotateY(-90deg)"></i></div>';
    }
    function disc(o) {
        var s = '', k, top;
        for (k = 0; k <= o.h; k += 3) {
            top = k + 3 > o.h;
            s += '<div class="dl" style="left:' + (o.x - o.r) + 'px;top:' + (o.y - o.r) + 'px;width:' + (2 * o.r) + 'px;height:' + (2 * o.r) + 'px;transform:translateZ(' + k + 'px);background:' + (top ? o.c : 'color-mix(in srgb,' + o.c + ' 66%,#000)') + '">' + (top ? (o.html || '') : '') + '</div>';
        }
        return '<div class="o3 ' + (o.cls || '') + '" style="left:0;top:0;transform:translateZ(' + (o.z || 0) + 'px)">' + s + '</div>';
    }
    function bb(o) {
        return '<div class="o3 ' + (o.wrap || '') + '" style="left:' + o.x + 'px;top:' + o.y + 'px;transform:translateZ(' + (o.z || 0) + 'px)">' +
            '<div class="bbi ' + (o.cls || '') + '" style="width:' + o.w + 'px;height:' + o.h + 'px;left:' + (-o.w / 2) + 'px;top:' + (-o.h) + 'px">' + o.html + '</div></div>';
    }
    function arch(o) {
        var a = o.a, t = o.t || 26, h = o.h, c = o.c, al = o.along || 34, s;
        if (o.dir === 'x') {
            s = box({ x: -al / 2, y: -a - t, w: al, d: t, h: h, c: c }) + box({ x: -al / 2, y: a, w: al, d: t, h: h, c: c }) +
                box({ x: -al / 2, y: -a - t, w: al, d: 2 * a + 2 * t, h: t, z: h, c: c, top: o.top, front: o.front });
        } else {
            s = box({ x: -a - t, y: -al / 2, w: t, d: al, h: h, c: c }) + box({ x: a, y: -al / 2, w: t, d: al, h: h, c: c }) +
                box({ x: -a - t, y: -al / 2, w: 2 * a + 2 * t, d: al, h: t, z: h, c: c, top: o.top, front: o.front });
        }
        return s;
    }
    function gearSVG(cx, cy, ro, ri, teeth, cls, fill) {
        var d = '', i, a0, a1, step = Math.PI * 2 / teeth;
        for (i = 0; i < teeth; i++) {
            a0 = i * step;
            [[-.28, ri], [-.16, ro], [.16, ro], [.28, ri]].forEach(function (q, j) {
                var a = a0 + q[0] * step * 1.6;
                d += (i === 0 && j === 0 ? 'M' : 'L') + (cx + Math.cos(a) * q[1]).toFixed(1) + ' ' + (cy + Math.sin(a) * q[1]).toFixed(1);
            });
        }
        return '<g class="gear ' + (cls || '') + '"><path d="' + d + 'Z" fill="' + fill + '" stroke="#1b1b1b" stroke-width="3.5" stroke-linejoin="round"/><circle cx="' + cx + '" cy="' + cy + '" r="' + (ri * .45) + '" fill="#fff" stroke="#1b1b1b" stroke-width="3.5"/></g>';
    }
    var INK = '#1b1b1b', LIME = '#c4f55d';
    /* Ilham: kepala kotak, layar gelap, berdiri */
    function robotIlham() {
        return '<svg viewBox="0 0 800 960">' +
            '<ellipse cx="400" cy="930" rx="184" ry="20" fill="#000" opacity=".13"/>' +
            '<rect x="316" y="810" width="48" height="84" fill="#fff" stroke="' + INK + '" stroke-width="12"/><rect x="436" y="810" width="48" height="84" fill="#fff" stroke="' + INK + '" stroke-width="12"/>' +
            '<rect x="270" y="882" width="120" height="44" rx="20" fill="#fff" stroke="' + INK + '" stroke-width="12"/><rect x="410" y="882" width="120" height="44" rx="20" fill="#fff" stroke="' + INK + '" stroke-width="12"/>' +
            '<path d="M288 648L200 756M512 648L600 756" stroke="' + INK + '" stroke-width="16" stroke-linecap="round"/>' +
            '<circle cx="200" cy="760" r="26" fill="#fff" stroke="' + INK + '" stroke-width="14"/><circle cx="600" cy="760" r="26" fill="#fff" stroke="' + INK + '" stroke-width="14"/>' +
            '<rect x="358" y="568" width="84" height="48" fill="#f0f0ee" stroke="' + INK + '" stroke-width="12"/>' +
            '<rect x="276" y="598" width="248" height="220" rx="92" fill="#fff" stroke="' + INK + '" stroke-width="14"/><rect x="472" y="632" width="32" height="160" rx="16" fill="#f0f0ee"/>' +
            '<polygon points="400,668 438,690 438,734 400,756 362,734 362,690" fill="' + LIME + '" stroke="' + INK + '" stroke-width="10" stroke-linejoin="round"/>' +
            '<circle cx="288" cy="648" r="18" fill="' + LIME + '" stroke="' + INK + '" stroke-width="10"/><circle cx="512" cy="648" r="18" fill="' + LIME + '" stroke="' + INK + '" stroke-width="10"/>' +
            '<rect x="136" y="112" width="528" height="456" rx="112" fill="#fff"/><rect x="600" y="168" width="40" height="344" rx="20" fill="#f0f0ee"/>' +
            '<rect x="184" y="160" width="432" height="360" rx="82" fill="#1d1d1d"/>' +
            '<path d="M264 232H352M448 232H536" stroke="' + LIME + '" stroke-width="18" stroke-linecap="round"/>' +
            '<rect x="272" y="280" width="72" height="96" rx="36" fill="' + LIME + '"/><rect x="456" y="280" width="72" height="96" rx="36" fill="' + LIME + '"/>' +
            '<path d="M350 446Q400 486 450 446" fill="none" stroke="' + LIME + '" stroke-width="16" stroke-linecap="round"/>' +
            '<rect x="136" y="112" width="528" height="456" rx="112" fill="none" stroke="' + INK + '" stroke-width="16"/></svg>';
    }
    /* Bobi: kepala bulat, tubuh melayang tanpa kaki (kerucut lime di bawah) */
    function robotBobi() {
        return '<svg viewBox="0 0 800 1100">' +
            '<ellipse cx="400" cy="1064" rx="136" ry="20" fill="#000" opacity=".13"/>' +
            '<g class="bobi-float">' +
            '<ellipse cx="400" cy="906" rx="104" ry="20" fill="' + LIME + '" stroke="' + INK + '" stroke-width="10"/>' +
            '<polygon points="352,928 448,928 400,1030" fill="' + LIME + '" stroke="' + INK + '" stroke-width="10" stroke-linejoin="round"/>' +
            '<ellipse cx="400" cy="700" rx="168" ry="172" fill="#fff"/><path d="M240 760Q400 900 560 760Q540 850 400 872Q260 850 240 760Z" fill="#f0f0ee"/>' +
            '<ellipse cx="400" cy="700" rx="168" ry="172" fill="none" stroke="' + INK + '" stroke-width="10"/>' +
            '<circle cx="400" cy="704" r="24" fill="' + LIME + '" stroke="' + INK + '" stroke-width="10"/>' +
            '<circle cx="200" cy="688" r="40" fill="#fff" stroke="' + INK + '" stroke-width="10"/><circle cx="600" cy="688" r="40" fill="#fff" stroke="' + INK + '" stroke-width="10"/>' +
            '<ellipse cx="400" cy="536" rx="72" ry="16" fill="' + LIME + '" stroke="' + INK + '" stroke-width="10"/>' +
            '<rect x="160" y="168" width="480" height="358" rx="178" fill="#fff"/><path d="M192 440Q400 560 608 440Q600 500 400 524Q200 500 192 440Z" fill="#f0f0ee"/>' +
            '<rect x="224" y="248" width="352" height="216" rx="100" fill="#1d1d1d"/>' +
            '<circle cx="320" cy="352" r="32" fill="' + LIME + '"/><circle cx="480" cy="352" r="32" fill="' + LIME + '"/>' +
            '<path d="M372 420Q400 440 428 420" fill="none" stroke="' + LIME + '" stroke-width="14" stroke-linecap="round"/>' +
            '<rect x="160" y="168" width="480" height="358" rx="178" fill="none" stroke="' + INK + '" stroke-width="16"/></g></svg>';
    }
    function teacherSVG() {
        return '<svg viewBox="0 0 100 160"><circle cx="50" cy="26" r="20" fill="#f1f1ef" stroke="' + INK + '" stroke-width="3.5"/><path d="M30 22q6-18 22-16 14 2 18 16-14-8-40 0z" fill="' + INK + '"/>' +
            '<circle cx="42" cy="28" r="3" fill="' + INK + '"/><circle cx="58" cy="28" r="3" fill="' + INK + '"/><path d="M43 37q7 5 14 0" fill="none" stroke="' + INK + '" stroke-width="3" stroke-linecap="round"/>' +
            '<rect x="24" y="50" width="52" height="64" rx="14" fill="' + INK + '"/><path d="M42 50l8 14 8-14z" fill="#fff"/>' +
            '<path d="M24 66l-14 28" stroke="' + INK + '" stroke-width="9" stroke-linecap="round"/><path d="M76 64l22-14" stroke="' + INK + '" stroke-width="9" stroke-linecap="round"/><circle cx="99" cy="49" r="6" fill="#f1f1ef" stroke="' + INK + '" stroke-width="3"/>' +
            '<rect x="32" y="112" width="15" height="40" rx="5" fill="#3b3b37"/><rect x="53" y="112" width="15" height="40" rx="5" fill="#3b3b37"/></svg>';
    }
    var CURSOR = '<svg viewBox="0 0 30 34" style="width:34px;height:38px"><path d="M3 2l22 14-10 2-5 11z" fill="#fff" stroke="' + INK + '" stroke-width="2.5" stroke-linejoin="round"/></svg>';

    /* ---------- lembar dokumen ---------- */
    var SW = 132, SD = 100;
    function sheet(cls) {
        return '<div class="o3 sheet ' + (cls || '') + '" data-k="form"><i class="sh" style="left:' + (-SW / 2 + 8) + 'px;top:' + (-SD / 2 + 8) + 'px;width:' + SW + 'px;height:' + SD + 'px"></i>' +
            '<div class="sf" style="left:' + (-SW / 2) + 'px;top:' + (-SD / 2) + 'px;width:' + SW + 'px;height:' + SD + 'px">' +
            '<div class="k k-form"><b class="fh">FORM</b><p><i></i><u></u></p><p><i></i><u style="width:70%"></u></p><p><i></i><u></u></p><s></s></div>' +
            '<div class="k k-ans"><em>Jawaban siswa</em><u></u><u style="width:88%"></u><u></u><u style="width:72%"></u><u style="width:90%"></u><u style="width:46%"></u></div>' +
            '<div class="k k-ess"><em>Esai</em><u></u><u style="width:86%"></u><u></u><u style="width:70%"></u><u></u><u style="width:80%"></u><u style="width:55%"></u></div>' +
            '<div class="k k-scr"><small>NILAI</small><strong>86</strong><u></u><u></u><u></u></div>' +
            '</div></div>';
    }

    /* ---------- bangun adegan ---------- */
    function station(i, pw, pd, ox, oy, inner) {
        var s = STN[i];
        return '<div class="o3 st" style="left:' + s.x + 'px;top:' + s.y + 'px">' +
            box({ x: -pw / 2 + ox, y: -pd / 2 + oy, w: pw, d: pd, h: 14, c: 'var(--plat)', cls: 'sh-soft' }) +
            '<div class="o3" style="transform:translateZ(16px)">' + inner + '</div></div>';
    }
    var h = '';
    h += '<div class="floor" style="left:0;top:0;width:2600px;height:2600px;transform:translate3d(-700px,-300px,-2px)"></div>';
    h += '<svg class="pathsvg" style="left:-480px;top:-60px;transform:translateZ(15px)" width="3000" height="2540" viewBox="-480 -60 3000 2540">' +
        '<path d="M' + pts.map(function (p) { return p[0].toFixed(1) + ' ' + p[1].toFixed(1); }).join('L') + '" fill="none" stroke="#1b1b1b" stroke-width="34" stroke-linecap="round" stroke-linejoin="round"/>' +
        '<path d="M' + pts.map(function (p) { return p[0].toFixed(1) + ' ' + p[1].toFixed(1); }).join('L') + '" fill="none" stroke="#c4f55d" stroke-width="24" stroke-linecap="round" stroke-linejoin="round"/>' +
        '<path d="M' + pts.map(function (p) { return p[0].toFixed(1) + ' ' + p[1].toFixed(1); }).join('L') + '" fill="none" stroke="#fff" stroke-opacity=".85" stroke-width="4" stroke-dasharray="2 26" stroke-linecap="round"/></svg>';

    /* 1. Input Google Form: jendela browser + titik awal jalur */
    h += station(0, 360, 360, 0, 0,
        bb({ x: -70, y: -120, z: 0, w: 250, h: 170, cls: 'bb-card', html:
            '<div style="display:flex;gap:5px;margin-bottom:8px"><i style="width:9px;height:9px;border-radius:50%;background:#1b1b1b"></i><i style="width:9px;height:9px;border-radius:50%;background:#c4f55d;border:1.5px solid #1b1b1b"></i><i style="width:9px;height:9px;border-radius:50%;background:#c9c9c4"></i></div>' +
            '<div style="padding:5px 8px;border-radius:7px;background:#c4f55d;border:2px solid #1b1b1b;font:700 11px var(--mono);white-space:nowrap;overflow:hidden">forms.google.com/&hellip;/edit<span class="caret" style="height:11px"></span></div>' +
            '<div style="margin-top:12px;font:800 17px var(--display)">Kuis Esai Pemrograman</div><div style="margin-top:8px;height:6px;border-radius:3px;background:#c9c9c4"></div><div style="margin-top:7px;height:6px;width:70%;border-radius:3px;background:#c9c9c4"></div><div style="margin-top:7px;height:6px;width:84%;border-radius:3px;background:#c9c9c4"></div>' }) +
        disc({ x: 50, y: 0, r: 62, h: 12, c: '#c4f55d', html: '<svg viewBox="0 0 24 24" style="position:absolute;inset:26%;width:48%;height:48%;fill:none;stroke:#1b1b1b;stroke-width:2.4;stroke-linecap:round;stroke-linejoin:round"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg>' }) +
        bb({ x: 130, y: 110, z: 0, w: 110, h: 46, cls: 'bb-card', html: '<small style="opacity:1;font-weight:700;color:#1b1b1b">Tautkan Link</small>' }));

    /* 2. Kontrol rubrik: meja kontrol + robot Ilham + tombol aktif */
    h += station(1, 380, 380, 0, 0,
        arch({ dir: 'y', a: 62, h: 92, t: 22, c: '#fff' }) +
        box({ x: 96, y: -64, w: 180, d: 118, h: 56, c: '#fff', top: '<div class="rails"><div class="rail"><i></i></div><div class="rail"><i></i></div><div class="rail"><i></i></div></div>' }) +
        '<div class="o3" style="transform:translateZ(56px)">' + disc({ x: 242, y: 4, r: 20, h: 9, c: '#c4f55d', cls: 'btn-3d-wrap' }) + '</div>' +
        bb({ x: 170, y: -84, z: 56, w: 140, h: 168, html: robotIlham() }) +
        bb({ x: -150, y: -110, z: 0, w: 200, h: 126, cls: 'bb-card', html: '<b style="font-size:15px">Bobot rubrik</b><div class="wt"><div>Konten<span><i style="width:80%"></i></span>40</div><div>Logika<span><i style="width:60%"></i></span>30</div><div>Struktur<span><i style="width:60%"></i></span>30</div></div>' }));

    /* 3. Backend queue: gerbang roda gigi schedule:work + robot Bobi */
    h += station(2, 380, 380, 0, 0,
        arch({ dir: 'x', a: 74, h: 96, t: 24, c: '#fff', along: 44 }) +
        bb({ x: 0, y: 0, z: 120, w: 230, h: 150, html: '<svg viewBox="0 0 230 150">' + gearSVG(92, 80, 62, 50, 12, '', '#c4f55d') + '<g transform="translate(168 40)">' + gearSVG(0, 0, 38, 30, 9, 'rev', '#fff') + '</g></svg>' }) +
        bb({ x: -120, y: 116, z: 0, w: 124, h: 170, html: robotBobi() }) +
        bb({ x: 50, y: 165, z: 0, w: 224, h: 92, cls: 'bb-dark bb-card bb-mono', html: '<span style="color:#c4f55d">$</span> php artisan<br>schedule:work<span class="caret"></span><br><small style="opacity:.6">menarik jawaban tiap 1 menit</small>' }));

    /* 4. Pemindai AI: gerbang laser lime */
    h += station(3, 380, 380, 0, 0,
        arch({ dir: 'y', a: 96, h: 124, t: 24, c: '#fff', along: 70 }) +
        '<div class="o3 laser" style="left:0;top:0">' + box({ x: -96, y: -3, w: 192, d: 6, h: 110, c: '#c4f55d', z: 0 }) + '</div>' +
        bb({ x: -175, y: -105, z: 0, w: 168, h: 90, cls: 'bb-card', html: '<small style="color:#1b1b1b;opacity:1;font-weight:700">KATA KUNCI</small><div style="margin-top:4px"><span class="chip hl">Syntax</span><span class="chip">Error</span><span class="chip hl">Debugging&nbsp;&#10003;</span></div>' }) +
        bb({ x: -170, y: 120, z: 0, w: 168, h: 92, cls: 'bb-card', html: '<b>Relevansi rubrik</b><div class="meter"><i></i></div><small style="opacity:1;font-weight:700">92% cocok</small>' }));

    /* 5. Review manual: mesin keluaran skor + tabel interaktif + pengajar */
    h += station(4, 400, 380, 0, 0,
        box({ x: -190, y: -150, w: 130, d: 110, h: 76, c: '#1b1b1b', top: '<div style="display:grid;place-items:center;height:100%;font:700 11px var(--mono);color:#c4f55d">SKOR</div>' }) +
        bb({ x: 70, y: -80, z: 0, w: 244, h: 160, cls: 'bb-card', html: '<table class="tbl"><tr><th>Siswa</th><th>Nilai</th></tr><tr><td>Teddy</td><td>86</td></tr><tr><td>Tuti</td><td><span class="ed"><b>78</b><b>84</b></span></td></tr><tr><td>Sayuti</td><td>91</td></tr></table>' }) +
        bb({ x: 168, y: 96, z: 0, w: 82, h: 130, html: teacherSVG() }));

    /* 6. Analytics: monitor grafik + ikon unduh Excel */
    h += station(5, 420, 380, 20, 0,
        box({ x: 40, y: -110, w: 240, d: 22, h: 164, c: '#1b1b1b', front: '<div class="mon"><div class="mon-h"><i></i><i></i><i></i><span>statistik_kelas</span></div><div class="mon-b"><i style="--h:42%"></i><i style="--h:68%"></i><i style="--h:92%"></i><i style="--h:58%"></i><i style="--h:76%"></i></div><div class="mon-f"><span>Rata-rata 82</span><span>Lulus 91%</span></div></div>', cls: 'mon-wrap' }) +
        box({ x: 120, y: -104, w: 80, d: 40, h: 8, c: '#1b1b1b', z: 0 }) +
        box({ x: -170, y: 40, w: 104, d: 118, h: 12, c: '#c4f55d', top: '<div class="xl"><b>.csv</b></div>' }) +
        bb({ x: -118, y: 100, z: 46, w: 54, h: 54, html: '<div class="dl-bb"><svg viewBox="0 0 24 24"><path d="M12 4v11M7 11l5 5 5-5M5 20h14"/></svg></div>' }));

    /* lembar bergerak: satu lembar utama + lembar latar (aliran jawaban siswa) */
    h += sheet('hero');
    h += bb({ x: 0, y: 0, z: 0, w: 120, h: 44, wrap: 'cursor-bb', html: CURSOR + '<span class="pill">tarik</span>' });
    var feeders = 6, flows = 5, i;
    for (i = 0; i < feeders + flows; i++) { h += sheet('amb'); }
    world.innerHTML = h;

    var hero = world.querySelector('.sheet.hero'), cursor = world.querySelector('.cursor-bb');
    var amb = [].slice.call(world.querySelectorAll('.sheet.amb'));
    var monitor = world.querySelector('.mon-wrap'), floorEl = world.querySelector('.floor'), floorKey = '';
    var btnWrap = world.querySelector('.btn-3d-wrap');
    if (btnWrap) { btnWrap.classList.add('btn-3d'); }
    [hero].concat(amb).forEach(function (s) { s.__k = 'form'; });

    function setKind(el, k) {
        if (el.__k === k) { return; }
        el.__k = k; el.setAttribute('data-k', k);
        el.classList.remove('flip'); void el.offsetWidth; el.classList.add('flip');
    }
    function put(el, x, y, z, ang, sc) {
        el.style.setProperty('--z', z + 'px');
        el.style.transform = 'translate3d(' + x.toFixed(1) + 'px,' + y.toFixed(1) + 'px,' + z.toFixed(1) + 'px) rotateZ(' + ang.toFixed(1) + 'deg) scale(' + sc.toFixed(3) + ')';
    }

    /* ---------- tata letak + kamera ---------- */
    var ax = 0, ay = 0, base = 1, mobile = false;
    function layout() {
        var sw = scene.clientWidth, sh = scene.clientHeight;
        mobile = window.innerWidth <= 820;
        if (mobile) { ax = sw * .5; ay = sh * .56; base = clamp(sw / 760, .42, .8); }
        else { ax = sw * .7; ay = sh * .56; base = clamp(sw / 1180, .62, 1.05); }
    }
    function camera(cur) {
        var i = Math.min(N - 2, Math.floor(cur)), f = cur - i;
        if (cur >= N - 1) { i = N - 2; f = 1; }
        if (cur < 0) { i = 0; f = 0; }
        var e = easeIO(f), L = stL[i] + (stL[i + 1] - stL[i]) * e, p = at(L);
        var zoom = base * (1 - .09 * Math.sin(Math.PI * e));
        var rot = -45 + 4 * Math.sin(Math.PI * e) * (i % 2 ? 1 : -1);
        world.style.transform = 'translate(' + ax.toFixed(1) + 'px,' + ay.toFixed(1) + 'px) scale(' + zoom.toFixed(4) + ') rotateX(60deg) rotateZ(' + rot.toFixed(2) + 'deg) translate(' + (-p.x).toFixed(1) + 'px,' + (-p.y).toFixed(1) + 'px)';
        return { L: L, p: p, e: e };
    }

    /* ---------- loop animasi ---------- */
    var lite = false, slow = 0, lastT = 0, lastF = 0;
    function goLite() { if (lite) { return; } lite = true; sec.classList.add('is-lite'); amb.forEach(function (a, n) { if (n % 2) { a.style.display = 'none'; } }); }
    if ((navigator.hardwareConcurrency || 8) <= 4 || (navigator.deviceMemory || 8) <= 4) { goLite(); }
    var cur = 0, idx = 0, arrive = performance.now(), dragT0 = performance.now();
    var visible = true;
    /* Autoplay (progress durasi + pindah tahap) baru mulai dari tahap 1 saat bagian "Cara kerja"
       sudah tampil pas di layar; keluar dari layar = jeda, masuk lagi = lanjut dari posisi terakhir. */
    var started = false, active = false, frozen = 0;
    /* Lama tiap tahap = estimasi waktu baca teks tahap itu (judul + deskripsi + kartu, sekitar 11 kata/detik)
       + bonus untuk visual yang lebih ramai; dibatasi 2 - 3,4 detik. Perjalanan kamera sudah termasuk di dalamnya. */
    var VISUAL_MS = [150, 250, 400, 450, 350, 400];
    var DW = steps.map(function (st, n) {
        var words = (st.textContent || '').trim().split(/\s+/).length;
        return Math.round(clamp(words / 11 * 1000 + VISUAL_MS[n] + 250, 2000, 3400));
    });
    function frame(now) {
        var W = scroller.clientWidth || 1;
        var raw = clamp(scroller.scrollLeft / W, 0, N - 1);
        cur = reduce ? raw : cur + (raw - cur) * .1;
        if (Math.abs(raw - cur) < .0008) { cur = raw; }
        var c = camera(cur), p = c.p;
        var fx = Math.round((p.x - 1300) / 110) * 110, fy = Math.round((p.y - 1300) / 110) * 110, fk = fx + ',' + fy;
        if (fk !== floorKey) { floorKey = fk; floorEl.style.transform = 'translate3d(' + fx + 'px,' + fy + 'px,-2px)'; }

        /* lembar utama mengikuti kamera; saat tahap 1 ia ditarik kursor ke titik awal */
        var off = 0, lift = 0, curOn = false;
        if (cur < .2) {
            var k = reduce ? 1 : (started ? clamp((now - dragT0 - 350) / 1700, 0, 1) : 0);
            off = -(1 - easeOut(k)) * 300; lift = (1 - easeOut(k)) * 64;
            curOn = started && (k < 1 || (now - dragT0 - 350 - 1700) < 700);
        }
        var q = at(c.L + off), t = now / 1000;
        var hz = 52 + lift + Math.sin(t * 2) * 2.2;
        var vanish = 1 - smooth((cur - 4.4) / .55);
        put(hero, q.x, q.y, hz, q.a, Math.max(.001, vanish) * 1.3);
        setKind(hero, cur < 1.5 ? 'form' : cur < 2.5 ? 'ans' : cur < 3.5 ? 'ess' : 'scr');
        cursor.classList.toggle('on', curOn);
        cursor.style.transform = 'translate3d(' + (q.x + 20).toFixed(1) + 'px,' + (q.y - 10).toFixed(1) + 'px,' + (hz + 4) + 'px)';

        /* lembar latar: masuk disedot roda gigi (tahap 3), lalu mengalir ke pemindai dan peninjauan */
        var j, u, L, pp, s, sc;
        for (j = 0; j < amb.length; j++) {
            s = amb[j];
            if (lite && j % 2) { continue; }
            if (j < feeders) {
                u = reduce ? (j + .5) / feeders * .7 : ((t / 4.4) + j / feeders) % 1;
                L = stL[2] - 470 + u * 450; pp = at(L);
                sc = Math.min(1, u * 9) * (1 - .8 * smooth((u - .5) / .5)) * .82;
                put(s, pp.x, pp.y + Math.sin(j * 2.1) * 12, 28 + Math.pow(u, 2.4) * 120, pp.a, Math.max(.001, sc));
                setKind(s, 'ans');
            } else {
                u = reduce ? ((j - feeders) + .5) / flows * .9 : ((t / 9.5) + (j - feeders) / flows) % 1;
                L = stL[2] + 70 + u * (stL[4] - stL[2] + 40); pp = at(L);
                sc = Math.min(1, u * 12) * Math.min(1, (1 - u) * 12) * .82;
                put(s, pp.x, pp.y + Math.sin(j * 1.7) * 10, 28, pp.a, Math.max(.001, sc));
                setKind(s, L < stL[3] - 70 ? 'ans' : L < stL[4] - 90 ? 'ess' : 'scr');
            }
        }

        /* monitor muncul saat lembar berubah jadi statistik (tahap 6) */
        if (monitor) {
            var m = smooth((cur - 4.3) / .6);
            monitor.style.transform = 'translateZ(0) scale3d(1,1,' + Math.max(.02, m).toFixed(3) + ')';
            monitor.style.transformOrigin = '0 0';
        }
    }

    /* ---------- tahap aktif, teks, progres ---------- */
    function setIdx(i) {
        if (i === idx) { return; }
        idx = i; arrive = performance.now();
        steps.forEach(function (s, n) { s.classList.toggle('on', n === i); s.setAttribute('aria-hidden', n === i ? 'false' : 'true'); });
        segs.forEach(function (s, n) { s.classList.toggle('on', n === i); if (n === i) { s.setAttribute('aria-current', 'step'); } else { s.removeAttribute('aria-current'); } });
        sec.setAttribute('data-s', i);
        if (i === 0) { dragT0 = performance.now(); }
    }
    var autoOn = !reduce, userUntil = 0, tween = null, quiet = 0, touching = false, dragging = false, snapTimer = 0;
    function progress(now) {
        var auto = autoOn && active && now > userUntil && !tween && !touching && !dragging;
        var f;
        if (!started) { f = 0; }                                               // belum dimulai: bar kosong
        else if (auto) { f = clamp((now - arrive) / DW[idx], 0, 1); }
        else if (autoOn && !active) { f = clamp(frozen / DW[idx], 0, 1); }     // di luar layar: bar berhenti
        else { f = 1; }
        for (var n = 0; n < N; n++) {
            fills[n].style.transform = 'scaleX(' + (n < idx ? 1 : n === idx ? f : 0).toFixed(3) + ')';
        }
    }
    function tweenTo(x, dur) { tween = { from: scroller.scrollLeft, to: x, t0: performance.now(), dur: dur }; }
    function snap() {
        var W = scroller.clientWidth || 1, n = clamp(Math.round(scroller.scrollLeft / W), 0, N - 1);
        tweenTo(n * W, 650);
    }
    function userTouch() {
        started = true;
        userUntil = performance.now() + 9000;
        if (hint) { hint.classList.add('gone'); }
    }
    function loop(now) {
        if (!sec.isConnected) { return; }   // halaman sudah diganti Turbo: hentikan loop
        if (visible && !document.hidden) {
            var W = scroller.clientWidth || 1;
            if (tween) {
                var k = (now - tween.t0) / tween.dur;
                scroller.scrollLeft = tween.from + (tween.to - tween.from) * easeIO(k);
                quiet = now + 80;
                if (k >= 1) { scroller.scrollLeft = tween.to; tween = null; }
            }
            var still = !tween && !dragging && !touching && Math.abs(scroller.scrollLeft / W - cur) < .002 && !(idx === 0 && now - dragT0 < 2500);
            var gap = lite ? (still ? 50 : 33) : (still ? 33 : 0);
            if (now - lastF >= gap - 2) { lastF = now; frame(now); }
            var dt = now - lastT; lastT = now;
            if (dt > 34 && dt < 250) { if (++slow > 45) { goLite(); } } else if (slow > 0) { slow -= .5; }
            setIdx(clamp(Math.round(scroller.scrollLeft / W), 0, N - 1));
            if (autoOn && active && now > userUntil && !tween && !touching && !dragging && now - arrive > DW[idx]) {
                tweenTo(idx >= N - 1 ? 0 : (idx + 1) * W, idx >= N - 1 ? 1800 : 1200);
            }
            progress(now);
        }
        requestAnimationFrame(loop);
    }

    /* ---------- input: geser/swipe horizontal, seret mouse, keyboard ---------- */
    scroller.addEventListener('scroll', function () {
        var now = performance.now();
        if (tween || now < quiet) { return; }
        userTouch();
        clearTimeout(snapTimer);
        snapTimer = setTimeout(function () { if (!touching && !dragging) { snap(); } }, 160);
    }, { passive: true });
    scroller.addEventListener('wheel', function () { tween = null; userTouch(); }, { passive: true });
    scroller.addEventListener('touchstart', function () { touching = true; tween = null; userTouch(); }, { passive: true });
    ['touchend', 'touchcancel'].forEach(function (ev) {
        scroller.addEventListener(ev, function () { touching = false; clearTimeout(snapTimer); snapTimer = setTimeout(snap, 160); }, { passive: true });
    });
    var sx = 0, s0 = 0, moved = 0;
    scroller.addEventListener('pointerdown', function (e) {
        if (e.pointerType !== 'mouse') { return; }
        dragging = true; tween = null; sx = e.clientX; s0 = scroller.scrollLeft; moved = 0; userTouch();
        try { scroller.setPointerCapture(e.pointerId); } catch (err) {}
    });
    scroller.addEventListener('pointermove', function (e) {
        if (!dragging) { return; }
        moved = e.clientX - sx;
        scroller.scrollLeft = s0 - moved * 1.15;
    });
    function endDrag() {
        if (!dragging) { return; }
        dragging = false;
        var W = scroller.clientWidth || 1, n = scroller.scrollLeft / W, base0 = s0 / W;
        if (Math.abs(moved) > 40 && Math.abs(n - base0) < .5) { n = base0 + (moved < 0 ? .5 : -.5) * 1.01; }
        tweenTo(clamp(Math.round(n), 0, N - 1) * W, 650);
    }
    scroller.addEventListener('pointerup', endDrag);
    scroller.addEventListener('pointercancel', endDrag);
    scroller.addEventListener('keydown', function (e) {
        var W = scroller.clientWidth || 1, n = Math.round(scroller.scrollLeft / W);
        if (e.key === 'ArrowRight') { e.preventDefault(); userTouch(); tweenTo(clamp(n + 1, 0, N - 1) * W, 900); }
        else if (e.key === 'ArrowLeft') { e.preventDefault(); userTouch(); tweenTo(clamp(n - 1, 0, N - 1) * W, 900); }
        else if (e.key === 'Home') { e.preventDefault(); userTouch(); tweenTo(0, 900); }
        else if (e.key === 'End') { e.preventDefault(); userTouch(); tweenTo((N - 1) * W, 900); }
    });
    segs.forEach(function (b, n) {
        b.addEventListener('click', function () { userTouch(); tweenTo(n * (scroller.clientWidth || 1), 1500); });
    });

    var lastW = scroller.clientWidth;
    function onResize() {
        var oldW = lastW || 1, nW = scroller.clientWidth || 1;
        scroller.scrollLeft = scroller.scrollLeft / oldW * nW; lastW = nW;
        layout();
    }
    window.addEventListener('resize', onResize, { signal: egSignal() });
    layout();

    if ('IntersectionObserver' in window) {
        var thr = []; for (var q = 0; q <= 20; q++) { thr.push(q / 20); }
        new IntersectionObserver(function (en) {
            var e = en[en.length - 1], now = performance.now();
            visible = e.isIntersecting;
            sec.classList.toggle('is-paused', !visible);
            /* "tampil pas": minimal 60% tahap kelihatan (atau 60% tinggi layar bila tahap lebih tinggi dari layar) */
            var rb = e.rootBounds ? e.rootBounds.height : window.innerHeight;
            var on = visible && e.intersectionRect.height >= Math.min(e.boundingClientRect.height, rb) * .6;
            if (on === active) { return; }
            active = on;
            if (on) {
                if (!started) { started = true; arrive = now; dragT0 = now; }   // pertama kali: mulai dari tahap 1
                else { arrive = now - frozen; }                                  // lanjut dari posisi terakhir
            } else if (started) {
                frozen = now - arrive;
            }
        }, { threshold: thr }).observe(stage);
    }
    document.addEventListener('visibilitychange', function () { sec.classList.toggle('is-paused', document.hidden); }, { signal: egSignal() });
    steps.forEach(function (s, n) { s.setAttribute('aria-hidden', n === 0 ? 'false' : 'true'); });
    requestAnimationFrame(loop);
})();
</script>

<section class="aspek reveal" id="aspek">
    <div>
        <div class="sec-head">
            <h2 class="tag">Aspek penilaian</h2>
            <p>Setiap jawaban dinilai dari tiga aspek (skala 0-100) menjadi satu nilai akhir.</p>
        </div>

        <div class="scale">
            <span>Nilai akhir:</span>
            @foreach(config('grading.categories', []) as $label => $min)
                <span class="badge {{ $badgeOf[$label] ?? '' }}">{{ $label }}</span>
            @endforeach
        </div>

    </div>

    <div class="swap" data-swap-stack>
        @foreach($aspects as $i => $aspect)
            <article class="sw spot {{ $swapStyle[$i % count($swapStyle)] }}" tabindex="0" data-ms="{{ $aspectMs[$i] }}">
                <div>
                    <div class="sw-top">
                        <span class="sw-no" aria-hidden="true">{{ $i + 1 }}</span>
                        <span class="lime-tag">Skala 0-100</span>
                    </div>
                    <h3>{{ $aspect[0] }}</h3>
                    <p class="muted">{{ $aspect[1] }}</p>
                </div>
                <div aria-hidden="true">
                    <div class="sw-rail"></div>
                    <div class="sw-rail-labels"><span>0</span><span>50</span><span>100</span></div>
                </div>
            </article>
        @endforeach
    </div>
</section>

<section class="band reveal">
    <div>
        <h2>Siap mencoba dengan penilaian pertama Anda?</h2>
        <p class="muted">Buat penilaian dalam beberapa menit. Setiap nilai dari AI bisa Anda koreksi kapan saja, dan koreksi Anda tidak tertimpa saat penilaian diulang.</p>
        <a class="btn spec" href="{{ route('login') }}"><span class="spec-label">Masuk dan buat penilaian</span></a>
    </div>
    @include('grader-bot')
</section>
@include('landing-mobile', ['part' => 'dock'])

<script>
(function () {
    'use strict';
    var root = document.documentElement;
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function egSignal() { return window.egSignal ? window.egSignal() : undefined; }
    var $ = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

    /* Tema dikelola layout (tombol ada di semua halaman); di sini cukup membaca keadaannya */
    function currentTheme() { return root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light'; }

    /* ---------- Muncul saat digulir ---------- */
    var reveals = $$('.reveal');
    if ('IntersectionObserver' in window && !reduce) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
            });
        }, { threshold: .12, rootMargin: '0px 0px -40px 0px' });
        reveals.forEach(function (el) { io.observe(el); });
    } else {
        reveals.forEach(function (el) { el.classList.add('in'); });
    }

    /* ---------- 3a. Spotlight: posisi kursor ke variabel CSS ---------- */
    function trackPointer(el, xVar, yVar, pct) {
        var move = function (e) {
            var r = el.getBoundingClientRect();
            el.style.setProperty(xVar, pct ? ((e.clientX - r.left) / r.width * 100) + '%' : (e.clientX - r.left) + 'px');
            el.style.setProperty(yVar, pct ? ((e.clientY - r.top) / r.height * 100) + '%' : (e.clientY - r.top) + 'px');
        };
        el.addEventListener('pointermove', move);
        el.addEventListener('pointerdown', move);
    }
    $$('.spot').forEach(function (el) { trackPointer(el, '--mx', '--my', false); });

    /* ---------- 4. Tombol specular ---------- */
    $$('.spec').forEach(function (el) { trackPointer(el, '--bx', '--by', true); });

    /* ---------- 3b. Card Swap: putar otomatis, lama tampil mengikuti estimasi waktu baca tiap aspek ---------- */
    (function () {
        var stack = $('[data-swap-stack]');
        if (!stack) { return; }
        if (window.matchMedia('(max-width: 820px)').matches) { return; }   // HP: kartu digeser manual, tidak berputar sendiri
        var cards = $$('.sw', stack), n = cards.length;
        var front = 0, busy = false, timer = 0, hovering = false, focused = false, visible = true;
        var auto = !reduce;   // reduced-motion: tidak berputar sendiri, kartu tetap bisa dipilih lewat klik / Enter

        function dwell() { return parseInt(cards[front].getAttribute('data-ms'), 10) || 4000; }
        function place() {
            cards.forEach(function (c, i) {
                c.style.setProperty('--slot', (i - front + n) % n);
                c.classList.remove('is-drop');
            });
        }
        function schedule() {
            clearTimeout(timer);
            if (!auto || hovering || focused || !visible || document.hidden) { return; }
            timer = setTimeout(next, dwell());
        }
        function next() {
            if (busy) { schedule(); return; }
            busy = true;
            cards[front].classList.add('is-drop');
            setTimeout(function () {
                front = (front + 1) % n;
                place();
                setTimeout(function () { busy = false; }, 700);
                schedule();
            }, reduce ? 0 : 520);
        }
        function go(i) { if (!busy && i !== front) { front = i; place(); } schedule(); }

        cards.forEach(function (c, i) {
            c.addEventListener('click', function () { go(i); });
            c.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(i); }
            });
        });
        stack.addEventListener('pointerenter', function () { hovering = true; schedule(); });
        stack.addEventListener('pointerleave', function () { hovering = false; schedule(); });
        stack.addEventListener('focusin', function () { focused = true; schedule(); });
        stack.addEventListener('focusout', function () { focused = false; schedule(); });
        document.addEventListener('visibilitychange', schedule, { signal: egSignal() });
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (en) { visible = en[0].isIntersecting; schedule(); }).observe(stack);
        }
        place();
        schedule();
    })();

    /* ---------- 5. Latar iridescence satu halaman (WebGL, responsif terhadap kursor) ---------- */
    (function () {
        var host = $('.page-bg');
        if (!host) { return; }
        if (window.matchMedia('(max-width: 820px)').matches) { host.classList.add('no-gl'); return; }   // HP: latar statis tanpa WebGL
        var canvas = $('canvas', host);
        var gl = null;
        try { gl = canvas.getContext('webgl', { antialias: false, alpha: false, powerPreference: 'low-power' }); } catch (e) {}
        if (!gl) { host.classList.add('no-gl'); return; }

        var vsrc = 'attribute vec2 p; varying vec2 vUv; void main(){ vUv = p * .5 + .5; gl_Position = vec4(p, 0., 1.); }';
        var fsrc = [
            '#ifdef GL_FRAGMENT_PRECISION_HIGH', 'precision highp float;', '#else', 'precision mediump float;', '#endif',
            'varying vec2 vUv; uniform float uTime; uniform vec2 uSize; uniform vec2 uMouse; uniform vec2 uMF; uniform float uDark;',
            'void main(){',
            '  vec2 p = vUv * uSize;',
            '  float dm = distance(p, uMouse);',
            '  float m = exp(-dm * dm * 9.);',
            '  vec2 uv = p * 1.1;',
            '  uv += (uMF - .5) * .38;',
            '  uv += (p - uMouse) * m * .4;',
            '  float d = -uTime * .16; float a = 0.;',
            '  for (int i = 0; i < 8; i++) { float fi = float(i); a += cos(fi - d - a * uv.x); d += sin(uv.y * fi + a); }',
            '  d += uTime * .16;',
            '  vec3 col = vec3(cos(uv * vec2(d, a)) * .6 + .4, cos(a + d) * .5 + .5);',
            '  col = cos(col * cos(vec3(d, a, 2.5)) * .5 + .5);',
            '  float t = dot(col, vec3(.3333));',
            '  vec3 paper = mix(vec3(1.), vec3(.09, .09, .086), uDark);',
            '  vec3 mist = mix(vec3(.945, .945, .937), vec3(.137, .137, .129), uDark);',
            '  vec3 back = mix(vec3(.847, .847, .831), vec3(.07, .07, .067), uDark);',
            '  vec3 lime = vec3(.769, .961, .365); vec3 sky = vec3(.812, .886, 1.);',
            '  vec3 c = mix(paper, mist, smoothstep(.58, .86, t));',
            '  c = mix(c, back, smoothstep(.8, .99, t) * .6);',
            '  c = mix(c, lime, smoothstep(.86, 1., col.g) * mix(.3, .12, uDark));',
            '  c = mix(c, sky, smoothstep(.88, 1., col.b) * mix(.28, .08, uDark));',
            '  c = mix(c, lime, m * mix(.26, .12, uDark));',
            '  gl_FragColor = vec4(c, 1.);',
            '}'
        ].join('\n');

        function compile(type, src) {
            var s = gl.createShader(type);
            gl.shaderSource(s, src); gl.compileShader(s);
            return gl.getShaderParameter(s, gl.COMPILE_STATUS) ? s : null;
        }
        var vs = compile(gl.VERTEX_SHADER, vsrc), fs = compile(gl.FRAGMENT_SHADER, fsrc);
        if (!vs || !fs) { host.classList.add('no-gl'); return; }
        var prog = gl.createProgram();
        gl.attachShader(prog, vs); gl.attachShader(prog, fs); gl.linkProgram(prog);
        if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) { host.classList.add('no-gl'); return; }
        gl.useProgram(prog);

        var buf = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, buf);
        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
        var loc = gl.getAttribLocation(prog, 'p');
        gl.enableVertexAttribArray(loc);
        gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);
        var uTime = gl.getUniformLocation(prog, 'uTime'), uSize = gl.getUniformLocation(prog, 'uSize'),
            uMF = gl.getUniformLocation(prog, 'uMF'), uMouse = gl.getUniformLocation(prog, 'uMouse'), uDark = gl.getUniformLocation(prog, 'uDark');

        /* Kanvas menutupi seluruh tinggi halaman. Posisi kursor dihitung dalam satuan tinggi layar
           (sama dengan skala pola), jadi cahaya hijau mengikuti kursor di section mana pun. */
        var mouse = [0, 0], target = [0, 0], mf = [.5, .5], lastC = null, primed = false,
            t0 = performance.now(), last = 0, raf = 0, onScreen = true;

        function vh() { return window.innerHeight || 1; }
        function resize() {
            var cw = canvas.clientWidth, ch = canvas.clientHeight;
            var sc = Math.min(.5, Math.sqrt(650000 / Math.max(1, cw * ch)));   // batasi jumlah piksel di halaman panjang
            var w = Math.max(2, Math.round(cw * sc)), h = Math.max(2, Math.round(ch * sc));
            if (canvas.width !== w || canvas.height !== h) { canvas.width = w; canvas.height = h; gl.viewport(0, 0, w, h); }
        }
        function aim() {
            var r = canvas.getBoundingClientRect(), v = vh();
            if (!r.width || !r.height) { return; }
            var cx = lastC ? lastC[0] : window.innerWidth / 2, cy = lastC ? lastC[1] : v / 2;
            target = [(cx - r.left) / v, (r.height - (cy - r.top)) / v];
            mf = [Math.min(1.2, Math.max(-.2, cx / (window.innerWidth || 1))), Math.min(1.2, Math.max(-.2, 1 - cy / v))];
            if (!primed) { mouse = target.slice(); primed = true; }
        }
        function draw(time) {
            resize();
            aim();
            var v = vh();
            gl.uniform1f(uTime, time);
            gl.uniform2f(uSize, canvas.clientWidth / v, canvas.clientHeight / v);
            gl.uniform2f(uMouse, mouse[0], mouse[1]);
            gl.uniform2f(uMF, mf[0], mf[1]);
            gl.uniform1f(uDark, currentTheme() === 'dark' ? 1 : 0);
            gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
        }
        function frame(now) {
            raf = 0;
            if (!canvas.isConnected) { return; }   // halaman sudah diganti Turbo
            if (now - last >= 33) {
                last = now;
                aim();   // ikut dihitung ulang saat halaman digulir
                mouse[0] += (target[0] - mouse[0]) * .09;
                mouse[1] += (target[1] - mouse[1]) * .09;
                draw((now - t0) / 1000 + 6);
            }
            if (onScreen && !document.hidden) { raf = requestAnimationFrame(frame); }
        }
        function start() { if (!reduce && !raf) { raf = requestAnimationFrame(frame); } }

        function track(e) { lastC = [e.clientX, e.clientY]; }
        window.addEventListener('pointermove', track, { passive: true, signal: egSignal() });
        window.addEventListener('pointerdown', track, { passive: true, signal: egSignal() });

        if (reduce) {
            draw(14);
            window.addEventListener('resize', function () { draw(14); }, { signal: egSignal() });
            if ('ResizeObserver' in window) { new ResizeObserver(function () { draw(14); }).observe(host); }
            new MutationObserver(function () { draw(14); }).observe(root, { attributes: true, attributeFilter: ['data-theme'] });
            return;
        }
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (en) { onScreen = en[0].isIntersecting; start(); }).observe(host);
        }
        document.addEventListener('visibilitychange', start, { signal: egSignal() });
        start();
    })();

    /* ---------- Judul hero: animasi Stroke Text diulang tiap 5 detik ---------- */
    (function () {
        var title = $('.hero h1');
        if (!title || reduce) { return; }
        var inView = true;
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (en) { inView = en[0].isIntersecting; }).observe(title);
        }
        var iv = setInterval(function () {
            if (!inView || document.hidden) { return; }
            title.classList.add('st-reset');
            void title.offsetWidth;
            title.classList.remove('st-reset');
        }, 5000);
        var sgT = egSignal();
        if (sgT) { sgT.addEventListener('abort', function () { clearInterval(iv); }); }
    })();

    /* ---------- Parallax ilustrasi (satu handler scroll) ---------- */
    /* ---------- Parallax & mikrodinamika (satu handler scroll) ---------- */
    (function () {
        // [selector, kecepatan, batas geser px (0 = bebas), selisih kedalaman antar elemen, hanya desktop]
        var CONFIG = [
            ['.scale .badge', .05, 8, .2, true],
            ['.sw-no', .07, 8, 0, true]
        ];
        var desktop = window.matchMedia('(min-width: 821px) and (hover: hover) and (pointer: fine)');
        var items = [];
        if (!reduce) {
            CONFIG.forEach(function (c) {
                $$(c[0]).forEach(function (el, i) {
                    if (!(el instanceof HTMLElement)) { return; }
                    items.push({ el: el, host: el.parentElement, k: c[1] * (1 + (i % 4) * c[3]), max: c[2], micro: c[4], on: false });
                });
            });
        }
        function parallax() {
            var vh = window.innerHeight, live = desktop.matches, rects = new Map(), i, it, r, off;
            for (i = 0; i < items.length; i++) {            // 1) baca semua posisi dulu
                it = items[i];
                if (it.micro && !live) { continue; }
                if (!rects.has(it.host)) { rects.set(it.host, it.host.getBoundingClientRect()); }
            }
            for (i = 0; i < items.length; i++) {            // 2) lalu tulis
                it = items[i];
                if (it.micro && !live) {
                    if (it.on) { it.el.style.translate = ''; it.on = false; }
                    continue;
                }
                r = rects.get(it.host);
                if (r.bottom < -200 || r.top > vh + 200) { continue; }
                off = (r.top + r.height / 2 - vh / 2) * it.k;
                if (it.max) { off = Math.max(-it.max, Math.min(it.max, off)); }
                it.el.style.translate = '0 ' + (-off).toFixed(1) + 'px';
                it.on = true;
            }
        }
        var ticking = false;
        function update() { ticking = false; if (!reduce) { parallax(); } }
        function onScroll() { if (!ticking) { ticking = true; requestAnimationFrame(update); } }
        var sig = { passive: true, signal: egSignal() };
        window.addEventListener('scroll', onScroll, sig);
        window.addEventListener('resize', onScroll, sig);
        update();
    })();
})();
</script>

<script>
(function () {
    var bar = document.getElementById('m-cta'), cta = document.querySelector('.hero-actions');
    if (!bar || !cta || !('IntersectionObserver' in window)) { if (bar) { bar.classList.remove('is-off'); } return; }
    new IntersectionObserver(function (e) { bar.classList.toggle('is-off', e[0].isIntersecting); }).observe(cta);
})();
</script>

<script>window.__egStages = @json($coreStages);</script>
<script>
(function () {
    'use strict';
    var wrap = document.querySelector('.hero-wrap');
    if (!wrap) { return; }
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var docEl = document.documentElement;

    /* ---------- Latar hero = warna halaman yang sebenarnya (tema terang/gelap), agar peleburan tanpa garis ---------- */
function paintPaper() {
        var isDark = docEl.getAttribute('data-theme') === 'dark';
        var navEl = document.querySelector('header, nav, .navbar, .topbar');
        var navBg = navEl ? getComputedStyle(navEl).backgroundColor : '';
        var darkHex = '#171717'; // samakan dengan kode hex hitam navbar Anda

        if (!isDark) {
            wrap.style.setProperty('--hero-paper', '#ffffff');
            return;
        }

        if (navBg && navBg !== 'transparent' && navBg !== 'rgba(0, 0, 0, 0)') {
            wrap.style.setProperty('--hero-paper', navBg);
        } else {
            wrap.style.setProperty('--hero-paper', darkHex);
        }
    }
    paintPaper();
    try {
        new MutationObserver(function () { paintPaper(); setTimeout(paintPaper, 450); })
            .observe(docEl, { attributes: true, attributeFilter: ['data-theme', 'class'] });
    } catch (e) { /* abaikan */ }

    /* ---------- HeroFloor: grid isometrik + jendela lime mengikuti kursor ---------- */
    (function () {
        var bg = wrap.querySelector('.hero-bg'), floor = wrap.querySelector('.hero-floor');
        if (!bg || !floor) { return; }
        var CELL_W = 84, CELL_H = 48, SPOT = 340;
        function grid(id, sw, fill) {
            var cells = fill ? '<path d="M' + CELL_W / 2 + ' 0L' + CELL_W + ' ' + CELL_H / 2 + 'L' + CELL_W / 2 + ' ' + CELL_H + 'L0 ' + CELL_H / 2 + 'Z" fill="currentColor" fill-opacity=".42"/>' +
                '<path d="M0 0L' + CELL_W / 2 + ' 0L0 ' + CELL_H / 2 + 'ZM' + CELL_W + ' 0L' + CELL_W / 2 + ' 0L' + CELL_W + ' ' + CELL_H / 2 + 'ZM0 ' + CELL_H + 'L0 ' + CELL_H / 2 + 'L' + CELL_W / 2 + ' ' + CELL_H + 'ZM' + CELL_W + ' ' + CELL_H + 'L' + CELL_W + ' ' + CELL_H / 2 + 'L' + CELL_W / 2 + ' ' + CELL_H + 'Z" fill="currentColor" fill-opacity=".24"/>' : '';
            return '<svg aria-hidden="true" width="100%" height="100%"><defs><pattern id="' + id + '" width="' + CELL_W + '" height="' + CELL_H + '" patternUnits="userSpaceOnUse">' + cells +
                '<path d="M0 ' + CELL_H / 2 + 'L' + CELL_W / 2 + ' 0L' + CELL_W + ' ' + CELL_H / 2 + 'M0 ' + CELL_H / 2 + 'L' + CELL_W / 2 + ' ' + CELL_H + 'L' + CELL_W + ' ' + CELL_H / 2 + '" fill="none" stroke="currentColor" stroke-width="' + sw + '"/></pattern></defs>' +
                '<rect width="100%" height="100%" fill="url(#' + id + ')"/></svg>';
        }
        floor.innerHTML = '<div class="fl">' + grid('eg-floor-cell', .8) + '</div>' +
            '<div class="fl-spot" style="width:' + SPOT + 'px;height:' + SPOT + 'px"><div class="fl-lit">' + grid('eg-floor-cell-lit', 1.1, true) + '</div></div>';
        var spot = floor.querySelector('.fl-spot'), lit = floor.querySelector('.fl-lit');
        floor.setAttribute('data-spot', 'off');
        function size() { lit.style.width = bg.clientWidth + 'px'; lit.style.height = bg.clientHeight + 'px'; }
        size();
        if ('ResizeObserver' in window) { new ResizeObserver(size).observe(bg); } else { window.addEventListener('resize', size); }
        if (reduce) { return; }
        var frame = 0;
        wrap.addEventListener('pointermove', function (e) {
            if (e.pointerType !== 'mouse') { return; }
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(function () {
                var box = bg.getBoundingClientRect(), x = e.clientX - box.left - SPOT / 2, y = e.clientY - box.top - SPOT / 2;
                var navEl = document.querySelector('header, nav, .navbar, .topbar');
                var navBottom = navEl ? navEl.getBoundingClientRect().bottom : 0;
                var edge = Math.max(navBottom, box.top), d = (e.clientY - edge) / 170;
                d = Math.max(0, Math.min(1, d));
                floor.style.setProperty('--spot-o', (d * d * (3 - 2 * d)).toFixed(3));
                spot.style.transform = 'translate3d(' + x + 'px,' + y + 'px,0)';
                lit.style.transform = 'translate3d(' + (-x) + 'px,' + (-y) + 'px,0)';
                floor.setAttribute('data-spot', 'on');
            });
        });
        wrap.addEventListener('pointerleave', function () { cancelAnimationFrame(frame); floor.setAttribute('data-spot', 'off'); });
    })();

    /* ---------- CoreVisual ---------- */
    var host = document.getElementById('cv');
    if (!host) { return; }
    var STAGES = window.__egStages || [];
    if (!STAGES.length) { return; }

    /* Warna aksen: ikut --lime milik tema; turunannya dihitung dari situ */
    function parseColor(s) {
        s = (s || '').trim();
        var m = /^#([0-9a-f]{3})$/i.exec(s);
        if (m) { return [0, 1, 2].map(function (i) { return parseInt(m[1][i] + m[1][i], 16); }); }
        m = /^#([0-9a-f]{6})/i.exec(s);
        if (m) { return [0, 2, 4].map(function (i) { return parseInt(m[1].substr(i, 2), 16); }); }
        m = /rgba?\(([^)]+)\)/.exec(s);
        if (m) { return m[1].split(/[ ,\/]+/).map(parseFloat).slice(0, 3); }
        return null;
    }
    function hex(c) { return '#' + c.map(function (v) { v = Math.max(0, Math.min(255, Math.round(v))); return (v < 16 ? '0' : '') + v.toString(16); }).join(''); }
    function mix(a, b, t) { return a.map(function (v, i) { return v + (b[i] - v) * t; }); }
    var base = parseColor(getComputedStyle(docEl).getPropertyValue('--lime')) || [196, 245, 93];
    var W = [255, 255, 255], K = [0, 0, 0];
    var ACC = hex(base), ACC_HI = hex(mix(base, W, .55)), ACC_LO = hex(mix(base, K, .12)), ACC_MID = hex(mix(base, W, .25));
    var LOGO_SIDE = [.62, .52, .42, .33, .24, .15].map(function (t) { return hex(mix(base, K, t)); });
    var CRY_DK = hex(mix(base, K, .38)), CRY_RIM = hex(mix(base, K, .5)), CRY_SH = hex(mix(base, K, .65));

    /* ----- Geometri isometrik ----- */
    var ISO_X = .866, ISO_Y = .5, CX = 500, CY = 500, FLAT = .57, RING_RX = [330, 430];
    var BASE = { half: 168, radius: 40, top: 475, depth: 34 }, SEAM_GAP = 9;
    var MID = { half: 151, radius: 36, top: BASE.top - SEAM_GAP - 28, depth: 28 };
    var CAP = { half: 101, radius: 28, top: MID.top - 14, depth: 14 };
    var FACE = { left: ISO_X + ' ' + ISO_Y + ' 0 1', right: ISO_X + ' ' + (-ISO_Y) + ' 0 1', top: ISO_X + ' ' + ISO_Y + ' ' + (-ISO_X) + ' ' + ISO_Y };
    function rnd(n) { return Math.round(n * 100) / 100; }
    function ext(o, p) { var r = {}, k; for (k in o) { r[k] = o[k]; } for (k in p) { r[k] = p[k]; } return r; }
    function proj(x, y, cx, cy) { cx = cx == null ? CX : cx; cy = cy == null ? CY : cy; return [cx + (x - y) * ISO_X, cy + (x + y) * ISO_Y]; }
    function footprint(half, radius, steps) {
        steps = steps || 14;
        var r = Math.min(radius, half), c = half - r, pts = [], i;
        [[c, c, 0], [-c, c, 90], [-c, -c, 180], [c, -c, 270]].forEach(function (k) {
            for (i = 0; i <= steps; i++) {
                var a = ((k[2] + 90 * i / steps) * Math.PI) / 180;
                pts.push([k[0] + r * Math.cos(a), k[1] + r * Math.sin(a)]);
            }
        });
        return pts;
    }
    function toPath(pts) { return 'M' + pts.map(function (p) { return p[0].toFixed(2) + ' ' + p[1].toFixed(2); }).join('L') + 'Z'; }
    function topPath(slab, inset, cx, dy) {
        inset = inset || 0; cx = cx == null ? CX : cx; dy = dy || 0;
        var half = slab.half - inset, radius = Math.max(slab.radius - inset, 1);
        return toPath(footprint(half, radius).map(function (p) { return proj(p[0], p[1], cx, slab.top + dy); }));
    }
    function hull(points) {
        var pts = points.slice().sort(function (a, b) { return a[0] - b[0] || a[1] - b[1]; });
        function cross(o, a, b) { return (a[0] - o[0]) * (b[1] - o[1]) - (a[1] - o[1]) * (b[0] - o[0]); }
        function build(list) {
            var out = [];
            list.forEach(function (p) {
                while (out.length >= 2 && cross(out[out.length - 2], out[out.length - 1], p) <= 0) { out.pop(); }
                out.push(p);
            });
            out.pop();
            return out;
        }
        return build(pts).concat(build(pts.slice().reverse()));
    }
    function bodyPath(slab, inset) {
        inset = inset || 0;
        var half = slab.half - inset, radius = Math.max(slab.radius - inset, 1), o = footprint(half, radius);
        var up = o.map(function (p) { return proj(p[0], p[1], CX, slab.top); });
        var lo = o.map(function (p) { return proj(p[0], p[1], CX, slab.top + slab.depth); });
        return toPath(hull(up.concat(lo)));
    }
    function facePoint(slab, side, u, v) {
        var p;
        if (side === 'left') { p = proj(-slab.half + 2 * slab.half * u, slab.half, CX, slab.top); }
        else { p = proj(slab.half, slab.half - 2 * slab.half * u, CX, slab.top); }
        return [p[0], p[1] + slab.depth * v];
    }
    function ringPoint(rx, deg) { var a = deg * Math.PI / 180; return [rnd(CX + rx * Math.cos(a)), rnd(CY + rx * FLAT * Math.sin(a))]; }
    function ringPath(rx) { var ry = rx * FLAT; return 'M' + (CX + rx) + ' ' + CY + 'A' + rx + ' ' + ry + ' 0 1 1 ' + (CX - rx) + ' ' + CY + 'A' + rx + ' ' + ry + ' 0 1 1 ' + (CX + rx) + ' ' + CY + 'Z'; }

    /* ----- Bagian-bagian slab ----- */
    var LEAF = 'M11 0H19A3 3 0 0 1 22 3V11A11 11 0 0 1 11 22H3A3 3 0 0 1 0 19V11A11 11 0 0 1 11 0Z';
    var OPAC = [1, .9, .8, .9];
    function leaves(fill, stroke) {
        return [0, 90, 180, 270].map(function (t) {
            return '<path d="' + LEAF + '" transform="rotate(' + t + ') translate(-25 -25)" fill="' + fill + '"' + (stroke ? ' stroke="' + stroke + '" stroke-width=".6"' : '') + '/>';
        }).join('');
    }
    function faceMark(slab, side, u, v, w, h, fill, glow) {
        var p = facePoint(slab, side, u, v);
        return '<g transform="translate(' + p[0].toFixed(2) + ' ' + p[1].toFixed(2) + ') matrix(' + FACE[side] + ' 0 0)">' +
            (glow ? '<rect x="' + (-w / 2 - 2) + '" y="' + (-h / 2 - 3) + '" width="' + (w + 4) + '" height="' + (h + 6) + '" rx="' + h + '" fill="' + fill + '" filter="url(#cv-blur-sm)" opacity=".75"/>' : '') +
            '<rect x="' + (-w / 2) + '" y="' + (-h / 2) + '" width="' + w + '" height="' + h + '" rx="' + (h / 2) + '" fill="' + fill + '"/></g>';
    }
    function panelLine(slab, side, v) {
        var a = facePoint(slab, side, .14, v), b = facePoint(slab, side, .86, v);
        return '<line class="cv-s-panel" x1="' + a[0] + '" y1="' + a[1] + '" x2="' + b[0] + '" y2="' + b[1] + '" stroke="#A7A298" stroke-width=".9" opacity=".55"/>';
    }
    function fastener(slab, x, y) {
        var p = proj(x, y, CX, slab.top);
        return '<g transform="translate(' + p[0].toFixed(2) + ' ' + p[1].toFixed(2) + ') matrix(' + FACE.top + ' 0 0)"><circle class="cv-s-fast" r="6.5" fill="url(#cv-hole)" stroke="#C9C5BB" stroke-width="1"/></g>';
    }
    function slabBody(slab) {
        var d = bodyPath(slab);
        return '<g><path d="' + d + '" fill="url(#cv-side)"/><path d="' + d + '" fill="url(#cv-side-shade)"/><path class="cv-s-body" d="' + d + '" fill="none" stroke="#CFCBC1" stroke-width="1" opacity=".85"/></g>';
    }
    function slabTop(slab, groove) {
        return '<g><path class="cv-s-bevel" d="' + topPath(slab) + '" fill="url(#cv-bevel)" stroke="#D9D6D0" stroke-width="1"/>' +
            '<path class="cv-s-top" d="' + topPath(slab, 4.5) + '" fill="url(#cv-top)" stroke="#fff" stroke-width="1"/>' +
            (groove !== undefined ? '<path class="cv-s-groove" d="' + topPath(slab, groove) + '" fill="none" stroke="#DAD6CE" stroke-width="1.2"/>' : '') + '</g>';
    }
    function coreLogo(x, y) {
        function plane(lift) { return 'translate(' + x + ' ' + (y - lift) + ') matrix(' + FACE.top + ' 0 0) scale(1.42)'; }
        var s = '<g><g transform="' + plane(0) + ' translate(2.4 2.4)" opacity=".22" filter="url(#cv-blur-sm)">' + leaves('#6E6556') + '</g>';
        LOGO_SIDE.forEach(function (f, k) { s += '<g transform="' + plane(k) + '">' + leaves(f) + '</g>'; });
        s += '<g transform="' + plane(LOGO_SIDE.length) + '"><rect x="-27" y="-27" width="54" height="54" fill="url(#cv-logo-top)" clip-path="url(#cv-mark-clip)"/>' + leaves('none', hex(mix(base, W, .8))) + '</g></g>';
        return s;
    }
    function defs() {
        return '<defs>' +
            '<linearGradient id="cv-side" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#F3F1EB"/><stop offset=".46" stop-color="#E9E6DF"/><stop offset=".5" stop-color="#DAD6CD"/><stop offset="1" stop-color="#E3DFD7"/></linearGradient>' +
            '<linearGradient id="cv-side-shade" x1="0" y1="0" x2="0" y2="1"><stop offset=".45" stop-color="#7C7566" stop-opacity="0"/><stop offset="1" stop-color="#7C7566" stop-opacity=".16"/></linearGradient>' +
            '<linearGradient id="cv-bevel" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FFFFFF"/><stop offset="1" stop-color="#E6E2DA"/></linearGradient>' +
            '<linearGradient id="cv-top" x1=".1" y1="0" x2=".9" y2="1"><stop offset="0" stop-color="#FFFFFF"/><stop offset="1" stop-color="#F2EFE9"/></linearGradient>' +
            '<linearGradient id="cv-inlay" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#EFEBE4"/><stop offset="1" stop-color="#FFFFFF"/></linearGradient>' +
            '<linearGradient id="cv-seam" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="' + ACC_HI + '"/><stop offset=".5" stop-color="' + ACC + '"/><stop offset="1" stop-color="' + ACC_LO + '"/></linearGradient>' +
            '<linearGradient id="cv-slot" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#5F5A52"/><stop offset="1" stop-color="#8E897F"/></linearGradient>' +
            '<linearGradient id="cv-logo-top" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="' + ACC_HI + '"/><stop offset=".55" stop-color="' + ACC + '"/><stop offset="1" stop-color="' + ACC_LO + '"/></linearGradient>' +
            '<clipPath id="cv-mark-clip">' + leaves('#000') + '</clipPath>' +
            '<radialGradient id="cv-hole" cx=".5" cy=".4" r=".7"><stop offset="0" stop-color="#D9D5CC"/><stop offset="1" stop-color="#F3F1EB"/></radialGradient>' +
            '<filter id="cv-blur-sm" x="-40%" y="-40%" width="180%" height="180%"><feGaussianBlur stdDeviation="3.5"/></filter>' +
            '<filter id="cv-blur-glow" x="-40%" y="-40%" width="180%" height="180%"><feGaussianBlur stdDeviation="9"/></filter>' +
            '<filter id="cv-blur-spill" x="-40%" y="-40%" width="180%" height="180%"><feGaussianBlur stdDeviation="18"/></filter>' +
            '</defs>';
    }
    var SEAM = { half: MID.half - 2, radius: MID.radius - 2, top: MID.top + MID.depth - 1, depth: SEAM_GAP + 1 };
    function baseMarks() {
        return faceMark(BASE, 'right', .5, .62, 4.5, 4.5, 'url(#cv-slot)') + faceMark(BASE, 'right', .58, .62, 4.5, 4.5, 'url(#cv-slot)') +
            faceMark(BASE, 'right', .66, .62, 4.5, 4.5, 'url(#cv-slot)') + faceMark(BASE, 'left', .32, .62, 4.5, 4.5, 'url(#cv-slot)') +
            faceMark(BASE, 'left', .4, .62, 4.5, 4.5, 'url(#cv-slot)');
    }
    function midMarks() {
        return panelLine(MID, 'left', .78) + panelLine(MID, 'right', .78) +
            faceMark(MID, 'left', .62, .42, 34, 7.5, 'url(#cv-slot)') + faceMark(MID, 'left', .34, .42, 5, 5, 'url(#cv-slot)') +
            faceMark(MID, 'left', .42, .42, 5, 5, 'url(#cv-slot)') + faceMark(MID, 'right', .44, .42, 5, 5, 'url(#cv-slot)') +
            faceMark(MID, 'right', .52, .42, 5, 5, 'url(#cv-slot)') + faceMark(MID, 'right', .68, .42, 22, 4.5, 'url(#cv-slot)') +
            '<g class="cv-seam-pulse">' + faceMark(MID, 'left', .2, .42, 7, 2.6, ACC, true) + '</g>' +
            fastener(MID, 0, -MID.half + 28) + fastener(MID, -MID.half + 28, 0) + fastener(MID, MID.half - 28, 0) + fastener(MID, 0, MID.half - 28);
    }
    function inlay(slab) {
        return '<path class="cv-s-inl" d="' + topPath(slab, 19) + '" fill="url(#cv-inlay)" stroke="#DDD9D1" stroke-width="1.2"/><path class="cv-s-inl2" d="' + topPath(slab, 20.5) + '" fill="none" stroke="#fff" stroke-width="1" opacity=".9"/>';
    }
    function restCore() {
        return '<svg viewBox="0 0 1000 1000" role="img" aria-label="Inti Essay Grader">' + defs() +
            slabBody(BASE) + slabTop(BASE, 10) + panelLine(BASE, 'left', .3) + panelLine(BASE, 'right', .3) + baseMarks() +
            '<g class="cv-seam-pulse"><path d="' + topPath(MID, 4, CX, BASE.top - MID.top) + '" fill="' + ACC + '" opacity=".3" filter="url(#cv-blur-spill)"/>' +
            '<path d="' + bodyPath(SEAM) + '" fill="' + ACC + '" opacity=".6" filter="url(#cv-blur-glow)"/></g>' +
            '<path d="' + bodyPath(SEAM) + '" fill="url(#cv-seam)"/>' +
            slabBody(MID) + slabTop(MID, 11) + midMarks() +
            slabBody(CAP) + slabTop(CAP) + inlay(CAP) + coreLogo(CX, CAP.top) + '</svg>';
    }
    function coreShadow() {
        var floor = ext(BASE, { top: BASE.top + BASE.depth + 30 });
        return '<svg viewBox="0 0 1000 1000" aria-hidden="true"><defs>' +
            '<filter id="cv-blur-lg" x="-30%" y="-30%" width="160%" height="160%"><feGaussianBlur stdDeviation="26"/></filter>' +
            '<filter id="cv-blur-md" x="-30%" y="-30%" width="160%" height="160%"><feGaussianBlur stdDeviation="9"/></filter></defs>' +
            '<path d="' + topPath(floor, -14) + '" fill="#8A8377" opacity=".2" filter="url(#cv-blur-lg)"/>' +
            '<path d="' + topPath(floor, 6) + '" fill="#5D574D" opacity=".22" filter="url(#cv-blur-md)"/></svg>';
    }
    function depthBackdrop() {
        var N = [[118, 132], [318, 82], [690, 96], [884, 188], [936, 470], [70, 512], [150, 858], [850, 846]];
        return '<svg viewBox="0 0 1000 1000" aria-hidden="true"><defs><linearGradient id="cv-glass" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".7"/><stop offset="1" stop-color="#fff" stop-opacity=".12"/></linearGradient></defs>' +
            '<g class="cv-s-glass" fill="url(#cv-glass)" stroke="#E3E0D9" stroke-width="1" opacity=".35"><rect width="290" height="250" rx="22" transform="matrix(.866 -.5 0 1 232 300)"/><rect width="290" height="250" rx="22" transform="matrix(.866 .5 0 1 520 150)"/></g>' +
            '<g class="cv-nd" fill="#fff" stroke="#CFCBC1" stroke-width="1">' + N.map(function (n, i) { return '<circle cx="' + n[0] + '" cy="' + n[1] + '" r="' + (i % 3 === 0 ? 4 : 3) + '" opacity=".85"/>'; }).join('') + '</g></svg>';
    }
    function techBackdrop() {
        var dots = '', i, a, r;
        for (i = 0; i < 26; i++) {
            a = i * 137.5 * Math.PI / 180; r = 160 + ((i * 53) % 340);
            dots += '<circle cx="' + rnd(CX + r * Math.cos(a) * 1.02) + '" cy="' + rnd(CY + r * Math.sin(a) * FLAT * 1.6) + '" r="' + (.8 + (i % 3) * .5) + '" opacity="' + (.35 + (i % 4) * .12) + '"/>';
        }
        var crosses = [[212, 292], [790, 690], [860, 430], [150, 560]].map(function (p) { return 'M' + (p[0] - 5) + ' ' + p[1] + 'h10M' + p[0] + ' ' + (p[1] - 5) + 'v10'; }).join('');
        return '<svg viewBox="0 0 1000 1000" aria-hidden="true" class="cv-ink"><defs>' +
            '<linearGradient id="cv-axis-v" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="currentColor" stop-opacity="0"/><stop offset=".5" stop-color="currentColor" stop-opacity=".9"/><stop offset="1" stop-color="currentColor" stop-opacity="0"/></linearGradient>' +
            '<linearGradient id="cv-axis-h" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="currentColor" stop-opacity="0"/><stop offset=".5" stop-color="currentColor" stop-opacity="1"/><stop offset="1" stop-color="currentColor" stop-opacity="0"/></linearGradient></defs>' +
            '<rect x="' + (CX - .5) + '" y="110" width="1" height="780" fill="url(#cv-axis-v)"/><rect x="70" y="' + (CY - .5) + '" width="860" height="1" fill="url(#cv-axis-h)"/>' +
            '<g fill="currentColor">' + dots + '</g><path d="' + crosses + '" fill="none" stroke="currentColor" stroke-width="1" opacity=".7"/></svg>';
    }
    function orbitSystem() {
        return '<svg viewBox="0 0 1000 1000" aria-hidden="true" class="cv-ink"><g fill="none" stroke="currentColor" stroke-width="1">' +
            '<ellipse cx="' + CX + '" cy="' + CY + '" rx="' + RING_RX[0] + '" ry="' + RING_RX[0] * FLAT + '" opacity="1"/>' +
            '<ellipse cx="' + CX + '" cy="' + CY + '" rx="' + RING_RX[1] + '" ry="' + RING_RX[1] * FLAT + '" opacity=".8"/></g></svg>';
    }
    var ORBIT_SEC = [70, 95];
    var NODES = [[0, 20, 7], [0, 110, 5.5], [0, 200, 8], [0, 290, 6], [1, 60, 6], [1, 132, 8], [1, 204, 5.5], [1, 276, 7], [1, 348, 6.5]];
    function crystal(s) {
        return '<g filter="url(#cv-cr-shadow)"><circle r="' + s * 1.55 + '" fill="url(#cv-cr-halo)"/><circle r="' + s + '" fill="url(#cv-cr-body)"/><circle r="' + s + '" fill="url(#cv-cr-refract)"/>' +
            '<circle r="' + (s - .5) + '" fill="none" stroke="url(#cv-cr-rim)" stroke-width="1"/>' +
            '<ellipse cx="' + (-s * .32) + '" cy="' + (-s * .4) + '" rx="' + s * .36 + '" ry="' + s * .2 + '" transform="rotate(-30 ' + (-s * .32) + ' ' + (-s * .4) + ')" fill="#fff" opacity=".95"/>' +
            '<circle cx="' + s * .34 + '" cy="' + (-s * .5) + '" r="' + Math.max(.7, s * .07) + '" fill="#fff" opacity=".85"/></g>';
    }
    function particles(half) {
        var clip = half === 'back' ? '<rect x="-200" y="-200" width="1400" height="' + (CY + 200) + '"/>' : '<rect x="-200" y="' + CY + '" width="1400" height="700"/>';
        var s = '<svg viewBox="0 0 1000 1000" aria-hidden="true"><defs><clipPath id="cv-clip-' + half + '">' + clip + '</clipPath>' +
            '<linearGradient id="cv-cr-body" x1=".15" y1=".1" x2=".85" y2=".95"><stop offset="0" stop-color="' + ACC_HI + '" stop-opacity=".95"/><stop offset=".5" stop-color="' + ACC + '" stop-opacity=".9"/><stop offset="1" stop-color="' + CRY_DK + '" stop-opacity=".95"/></linearGradient>' +
            '<radialGradient id="cv-cr-refract" cx=".6" cy=".78" r=".45"><stop offset="0" stop-color="#fff" stop-opacity=".8"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></radialGradient>' +
            '<linearGradient id="cv-cr-rim" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".95"/><stop offset="1" stop-color="' + CRY_RIM + '" stop-opacity=".55"/></linearGradient>' +
            '<radialGradient id="cv-cr-halo" cx=".5" cy=".5" r=".5"><stop offset=".5" stop-color="' + ACC + '" stop-opacity=".22"/><stop offset="1" stop-color="' + ACC + '" stop-opacity="0"/></radialGradient>' +
            '<filter id="cv-cr-shadow" x="-80%" y="-80%" width="260%" height="260%"><feDropShadow dx="0" dy="2.5" stdDeviation="2.5" flood-color="' + CRY_SH + '" flood-opacity=".25"/></filter></defs>' +
            '<g clip-path="url(#cv-clip-' + half + ')">';
        NODES.forEach(function (n) {
            var rx = RING_RX[n[0]], pt = ringPoint(rx, n[1]), dur = ORBIT_SEC[n[0]], rev = n[0] === 1, st = n[1] / 360, begin = -dur * (rev ? 1 - st : st);
            s += '<g' + (reduce ? ' transform="translate(' + pt[0].toFixed(1) + ' ' + pt[1].toFixed(1) + ')"' : '') + '>' + crystal(n[2]) +
                (reduce ? '' : '<animateMotion dur="' + dur + 's" begin="' + begin + 's" repeatCount="indefinite" path="' + ringPath(rx) + '"' + (rev ? ' keyPoints="1;0" keyTimes="0;1" calcMode="linear"' : '') + '/>') + '</g>';
        });
        return s + '</g></svg>';
    }

    /* ----- Tata letak exploded view ----- */
    var EXPLODE = { gap: 94, aiLift: 12, topPlateY: 230, stagger: .05, scales: [.63, .62, .7, 1.07, 1.06] };
    var PLATE = [ext(BASE, { top: BASE.top + 17, depth: 17 }), ext(BASE, { top: BASE.top, depth: 17 }), MID, ext(CAP, { top: CAP.top + 7, depth: 7 }), ext(CAP, { top: CAP.top, depth: 7 })];
    var AI = 2, SKIRT = [0, 4, 0, 10, 10];
    var TARGET = PLATE.map(function (_, i) { return EXPLODE.topPlateY + (PLATE.length - 1 - i) * EXPLODE.gap - (i === AI ? EXPLODE.aiLift : 0); });
    var TRAVEL = PLATE.map(function (s, i) { return TARGET[i] - s.top; });
    function clamp(v) { return Math.min(1, Math.max(0, v)); }
    function pProg(i, p) { var d = Math.abs(i - AI) * EXPLODE.stagger; return clamp((p - d) / (1 - d)); }
    function tipX(i, side) { var s = PLATE[i]; return CX + (side === 'right' ? 1 : -1) * .866 * (2 * s.half - .586 * s.radius) * EXPLODE.scales[i]; }
    function cornerY(i) { return TARGET[i] + PLATE[i].depth / 2; }
    function plateNumber(slab, n, main) {
        var k = slab.half * .56, p = proj(k, k, CX, slab.top), plane = 'matrix(' + FACE.top + ' 0 0) rotate(-45)', label = (n < 10 ? '0' : '') + n;
        var t = ' text-anchor="middle" dominant-baseline="central" font-size="25" font-weight="500" letter-spacing="2.5" font-family="inherit"';
        return '<g transform="translate(' + p[0].toFixed(2) + ' ' + p[1].toFixed(2) + ')" aria-hidden="true"><text class="cv-n-sh"' + t + ' transform="translate(0 .9) ' + plane + '" fill="#FFFFFF" opacity=".95">' + label + '</text>' +
            '<text' + (main ? '' : ' class="cv-n-lb"') + t + ' transform="' + plane + '" fill="' + (main ? ACC_LO : '#CBC6BC') + '">' + label + '</text></g>';
    }
    function plateG(i, inner) {
        var slab = PLATE[i];
        return '<g class="cv-plate" data-i="' + i + '">' +
            (i > 0 ? '<path class="cv-psh" d="' + topPath(slab, -6, CX, slab.depth + 34) + '" fill="#6E6556" filter="url(#cv-blur-spill)" opacity="0"/>' : '') +
            (SKIRT[i] > 0 ? '<g class="cv-psk" opacity="0">' + slabBody(ext(slab, { top: slab.top + slab.depth - 1, depth: SKIRT[i] + 1 })) + '</g>' : '') + inner + '</g>';
    }
    function explodedCore() {
        var s = '<svg viewBox="0 0 1000 1000" aria-hidden="true">' + defs();
        s += plateG(0, slabBody(PLATE[0]) + slabTop(PLATE[0], 10) + plateNumber(PLATE[0], 1, false) + baseMarks());
        s += plateG(1, slabBody(PLATE[1]) + slabTop(PLATE[1], 10) + plateNumber(PLATE[1], 2, false) + panelLine(BASE, 'left', .3) + panelLine(BASE, 'right', .3));
        s += plateG(AI, '<g class="cv-seam-pulse"><path d="' + topPath(MID, 4, CX, BASE.top - MID.top) + '" fill="' + ACC + '" opacity=".3" filter="url(#cv-blur-spill)"/>' +
            '<path d="' + bodyPath(SEAM) + '" fill="' + ACC + '" opacity=".6" filter="url(#cv-blur-glow)"/></g>' +
            '<path class="cv-strong" d="' + bodyPath(SEAM) + '" fill="' + ACC + '" filter="url(#cv-blur-glow)" opacity="0"/>' +
            '<path d="' + bodyPath(SEAM) + '" fill="url(#cv-seam)"/>' + slabBody(MID) + slabTop(MID, 11) + plateNumber(MID, 3, true) + midMarks() +
            '<g transform="translate(' + CX + ' ' + MID.top + ') matrix(' + FACE.top + ' 0 0)">' +
            '<circle class="cv-ai-spill" r="86" fill="' + ACC + '" filter="url(#cv-blur-spill)" opacity="0"/>' +
            '<circle class="cv-ai-rim" r="56" fill="none" stroke="' + ACC_LO + '" stroke-width="1.4" opacity="0"/>' +
            '<circle class="cv-ai-halo" r="38" fill="none" stroke="' + ACC_LO + '" stroke-width="1" stroke-dasharray="3 6" opacity="0"/>' +
            '<circle class="cv-ai-core" r="9" fill="' + ACC + '" stroke="' + ACC_LO + '" stroke-width="1" opacity="0"/></g>');
        s += plateG(3, slabBody(PLATE[3]) + slabTop(PLATE[3]) + inlay(PLATE[3]) + plateNumber(PLATE[3], 4, false));
        s += plateG(4, slabBody(PLATE[4]) + slabTop(PLATE[4]) + inlay(CAP) + coreLogo(CX, CAP.top));
        return s + '</svg>';
    }

    /* ----- Ikon & kartu ----- */
    var LN = {
        form: '<rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>',
        pen: '<path d="M12.5 22H18a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v9.5"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M13.378 15.626a1 1 0 1 0-3.004-3.004l-5.01 5.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z"/>',
        check: '<rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/>',
        chart: '<line x1="12" x2="12" y1="20" y2="10"/><line x1="18" x2="18" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="16"/>',
        link: '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        key: '<path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/>',
        list: '<path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/><path d="M13 6h8"/><path d="M13 12h8"/><path d="M13 18h8"/>',
        sliders: '<line x1="21" x2="14" y1="4" y2="4"/><line x1="10" x2="3" y1="4" y2="4"/><line x1="21" x2="12" y1="12" y2="12"/><line x1="8" x2="3" y1="12" y2="12"/><line x1="21" x2="16" y1="20" y2="20"/><line x1="12" x2="3" y1="20" y2="20"/><line x1="14" x2="14" y1="2" y2="6"/><line x1="8" x2="8" y1="10" y2="14"/><line x1="16" x2="16" y1="18" y2="22"/>',
        eye: '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>',
        pencil: '<path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/>',
        note: '<path d="M22 17a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 21.286V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2z"/>',
        mouse: '<path d="M4.037 4.688a.495.495 0 0 1 .651-.651l16 6.5a.5.5 0 0 1-.063.947l-6.124 1.58a2 2 0 0 0-1.438 1.435l-1.579 6.126a.5.5 0 0 1-.947.063z"/>'
    };
    var LG = {
        forms: '<path d="M5 2h9l5 5v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z" fill="#7248B9"/><path d="M14 2l5 5h-5z" fill="#B39DDB"/><rect x="7" y="11" width="2" height="2" rx=".5" fill="#fff"/><rect x="10.5" y="11" width="6" height="2" rx=".5" fill="#fff"/><rect x="7" y="15" width="2" height="2" rx=".5" fill="#fff"/><rect x="10.5" y="15" width="6" height="2" rx=".5" fill="#fff"/>',
        sheets: '<path d="M5 2h9l5 5v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z" fill="#0F9D58"/><path d="M14 2l5 5h-5z" fill="#87CEAC"/><rect x="7" y="11" width="10" height="7" rx=".8" fill="#fff"/><path d="M7 14.5h10M11.5 11v7" stroke="#0F9D58" stroke-width="1"/>',
        google: '<text x="12" y="18" text-anchor="middle" font-size="19" font-weight="700" font-family="Arial, sans-serif" fill="#4285F4">G</text>',
        excel: '<rect x="3" y="3" width="18" height="18" rx="3.5" fill="#1D6F42"/><path d="M8.5 8.5l7 7M15.5 8.5l-7 7" stroke="#fff" stroke-width="2" stroke-linecap="round"/>',
        gemini: '<path transform="translate(1 1) scale(1.375)" fill="#448aff" d="M15 8.014A7.457 7.457 0 0 0 8.014 15h-.028A7.456 7.456 0 0 0 1 8.014v-.028A7.456 7.456 0 0 0 7.986 1h.028A7.457 7.457 0 0 0 15 7.986z"/>',
        ollama: '<ellipse cx="12" cy="13.5" rx="6.6" ry="7.5" fill="#fff" stroke="#171717" stroke-width="1.6"/><path d="M7.6 7.5 6.6 3M16.4 7.5l1-4.5" stroke="#171717" stroke-width="1.8" stroke-linecap="round"/><circle cx="9.4" cy="12.5" r="1" fill="#171717"/><circle cx="14.6" cy="12.5" r="1" fill="#171717"/><ellipse cx="12" cy="16.4" rx="2.2" ry="1.5" fill="#171717"/>',
        groq: '<rect x="3" y="3" width="18" height="18" rx="5" fill="#F55036"/><path d="M12 7.5a4.5 4.5 0 1 0 0 9h2.5v-3.2H12" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"/>'
    };
    function icon(key, cls) { return '<svg viewBox="0 0 24 24" aria-hidden="true" class="' + (cls || '') + '">' + (LN[key] || '') + '</svg>'; }
    function logo(key) {
        if (LG[key]) { return '<svg viewBox="0 0 24 24" aria-hidden="true">' + LG[key] + '</svg>'; }
        return icon(key, 'ln');
    }
    function esc(t) { return String(t).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
    function appsHtml(st, tiles) {
        var h = '<div class="cv-apps">' + st.apps.map(function (a) {
            return tiles ? '<span class="cv-tile">' + logo(a) + '</span>' : '<span class="cv-ap">' + logo(a) + '</span>';
        }).join('') + '</div>';
        return h;
    }
    function cardBody(i) {
        var st = STAGES[i], main = !!st.main;
        var h = '<div class="cv-cb"><span class="cv-no">' + (i < 9 ? '0' : '') + (i + 1) + '</span>' +
            '<div class="cv-ttl"><p>' + esc(st.title) + '</p>' + (main ? '<span class="cv-main-tag">' + esc(st.tag || 'Main layer') + '</span>' : '') + '</div>';
        if (st.icon && !main) { h += '<span class="cv-ic">' + icon(st.icon, 'ln') + '</span>'; }
        h += '<div class="cv-it"><p>' + st.items.map(esc).join(' · ') + '</p>' + (main ? appsHtml(st, true) : '') + '</div>';
        if (!main) { h += '<div class="cv-side">' + appsHtml(st, false) + '</div>'; }
        return h + '</div>';
    }
    var CARDS = { left: { edge: 0, width: 31 }, right: { edge: 0, width: 29 }, restInset: 10, outward: 2, beadSeconds: 1.6 };
    function dir(i) { return STAGES[i].side === 'left' ? -1 : 1; }

    /* ----- Rakit DOM ----- */
    function boot() {
    var cardsHtml = STAGES.map(function (st, i) {
        var c = CARDS[st.side], left = st.side === 'left' ? c.edge : 100 - c.edge - c.width;
        return '<li style="top:' + (cornerY(i) / 10) + '%;left:' + left + '%;width:' + c.width + '%"><div class="cv-card' + (st.main ? ' cv-main' : '') + '" data-i="' + i + '">' + cardBody(i) + '</div></li>';
    }).join('');
    var connHtml = STAGES.map(function (st, i) {
        var c = CARDS[st.side], d = st.side === 'right' ? 1 : -1;
        var cardEdge = st.side === 'left' ? (c.edge + c.width) * 10 : (100 - c.edge - c.width) * 10;
        var px = tipX(i, st.side) + d * 7, nx = cardEdge - d * 10, y = cornerY(i);
        return '<svg viewBox="0 0 1000 1000" aria-hidden="true" data-i="' + i + '" data-px="' + px + '" data-nx="' + nx + '" style="opacity:0">' +
            '<line x1="' + px + '" x2="' + nx + '" y1="' + y + '" y2="' + y + '" stroke="#CFCAC1" stroke-width="1"/>' +
            '<line class="lit-line" x1="' + px + '" x2="' + nx + '" y1="' + y + '" y2="' + y + '" stroke="' + ACC_LO + '" stroke-width="1.3" opacity="0"/>' +
            '<circle cx="' + px + '" cy="' + y + '" r="2.6" fill="#A8A399"/>' +
            '<circle class="halo" cx="' + nx + '" cy="' + y + '" r="' + (st.main ? 10 : 8) + '" fill="' + ACC + '" opacity="' + (st.main ? .4 : .25) + '"/>' +
            '<circle cx="' + nx + '" cy="' + y + '" r="3.6" fill="' + ACC + '" stroke="' + ACC_LO + '" stroke-width="1"/>' +
            '<circle class="bead" cx="' + nx + '" cy="' + y + '" r="2.8" fill="' + ACC + '" stroke="' + ACC_LO + '" stroke-width=".8" opacity="0"/></svg>';
    }).join('');

    host.innerHTML =
        '<div class="cv-in">' +
        '<div class="cv-halo" aria-hidden="true"></div>' +
        '<div class="cv-fade"><div class="cv-layer cv-px" data-reach="-5">' + depthBackdrop() + techBackdrop() + '</div></div>' +
        '<div class="cv-layer cv-px" data-reach="12"><div class="cv-layer cv-orb">' + orbitSystem() + particles('back') + '</div></div>' +
        '<div class="cv-layer cv-shadow">' + coreShadow() + '</div>' +
        '<div class="cv-persp"><div class="cv-rot"><div class="cv-float">' +
        '<div class="cv-layer cv-rest">' + restCore() + '</div><div class="cv-layer cv-exp" style="opacity:0">' + explodedCore() + '</div>' +
        '<div class="cv-conn">' + connHtml + '</div></div></div></div>' +
        '<div class="cv-layer cv-px" data-reach="12"><div class="cv-layer cv-orb">' + particles('front') + '</div></div>' +
        '<div class="cv-persp"><div class="cv-rot"><div class="cv-float"><ul class="cv-cards" aria-label="Alur kerja Essay Grader">' + cardsHtml + '</ul></div></div></div>' +
        '</div>' +
        '<p class="cv-hint" aria-hidden="true"><svg viewBox="0 0 24 24" class="ln">' + LN.mouse + '</svg><span class="hov">Arahkan kursor untuk melihat alurnya</span><span class="tap">Ketuk untuk melihat alurnya</span></p>' +
        '<button type="button" class="cv-hit" aria-expanded="false" aria-label="Tampilkan cara kerja Essay Grader"></button>';

    function q(s, r) { return (r || host).querySelector(s); }
    function qa(s, r) { return Array.prototype.slice.call((r || host).querySelectorAll(s)); }

    var elRest = q('.cv-rest'), elExp = q('.cv-exp'), elShadow = q('.cv-shadow'), elHint = q('.cv-hint'), hit = q('.cv-hit');
    var pxLayers = qa('.cv-px'), orbLayers = qa('.cv-orb'), rots = qa('.cv-rot'), svgParticles = qa('.cv-orb svg').filter(function (s) { return s.querySelector('animateMotion'); });
    var cardEls = qa('.cv-card'), connEls = qa('.cv-conn svg');
    var plates = qa('.cv-plate').map(function (g) {
        var b = g.getBBox();
        return { g: g, cx: b.x + b.width / 2, cy: b.y + b.height / 2, sh: g.querySelector('.cv-psh'), sk: g.querySelector('.cv-psk') };
    });
    var aiSpill = q('.cv-ai-spill'), aiRim = q('.cv-ai-rim'), aiHalo = q('.cv-ai-halo'), aiCore = q('.cv-ai-core'), strong = q('.cv-strong');

    /* ----- Animasi: pegas, parallax, tilt ----- */
    var EXPL = { k: 105, c: 25, m: 1 }, PTR = { k: 55, c: 18, m: .9 }, MAXTILT = 3.5;
    var prog = { x: 0, v: 0, t: 0 }, sx = { x: 0, v: 0, t: 0 }, sy = { x: 0, v: 0, t: 0 };
    var open = false, openAt = 0, hovered = null, onScreen = true, pageVisible = !document.hidden, raf = 0, last = 0, pointerType = 'mouse';
    function step(s, P, h) { var a = (-P.k * (s.x - s.t) - P.c * s.v) / P.m; s.v += a * h; s.x += s.v * h; }
    function settled(s) { return Math.abs(s.x - s.t) < 1e-3 && Math.abs(s.v) < 1e-3; }
    function lerpRange(v, a, b, o0, o1) { var t = clamp((v - a) / (b - a)); return o0 + (o1 - o0) * t; }
    function ease(k) { return k * k * (3 - 2 * k); }

    function render(now) {
        var p = prog.x, x = sx.x, y = sy.x;
        elRest.style.opacity = clamp(1 - p / .04);
        elExp.style.opacity = clamp(p / .03);
        elShadow.style.opacity = 1 - p * .45;
        var orbOp = lerpRange(p, 0, .6, 1, .15);
        orbLayers.forEach(function (el) { el.style.opacity = orbOp; });
        elHint.style.opacity = clamp(1 - p / .25);
        pxLayers.forEach(function (el) { var r = parseFloat(el.getAttribute('data-reach')); el.style.transform = 'translate(' + (x * r).toFixed(2) + 'px,' + (y * r).toFixed(2) + 'px)'; });
        var tr = 'rotateX(' + (y * -MAXTILT + p * 4).toFixed(3) + 'deg) rotateY(' + (x * MAXTILT).toFixed(3) + 'deg)';
        rots.forEach(function (el) { el.style.transform = tr; });
        plates.forEach(function (o, i) {
            var pp = pProg(i, p), ty = TRAVEL[i] * pp, sc = 1 + (EXPLODE.scales[i] - 1) * pp;
            o.g.setAttribute('transform', 'translate(0 ' + ty.toFixed(2) + ') translate(' + o.cx + ' ' + o.cy + ') scale(' + sc.toFixed(4) + ') translate(' + (-o.cx) + ' ' + (-o.cy) + ')');
            if (o.sh) { o.sh.setAttribute('opacity', (pp * .16).toFixed(3)); }
            if (o.sk) { o.sk.setAttribute('opacity', pp.toFixed(3)); }
        });
        var on = clamp((p - .5) / .4), halo = on * .55;
        aiRim.setAttribute('opacity', (on * .9).toFixed(3)); aiHalo.setAttribute('opacity', halo.toFixed(3));
        aiCore.setAttribute('opacity', (on * .9).toFixed(3)); aiSpill.setAttribute('opacity', (halo * .35).toFixed(3));
        strong.setAttribute('opacity', (clamp((p - .4) / .5) * .6).toFixed(3));
        var cShow = lerpRange(p, .55, .9, 0, 1), cardOp = lerpRange(p, .35, .9, 0, 1);
        cardEls.forEach(function (el, i) {
            el.style.opacity = cardOp;
            el.style.transform = 'translateX(' + (-dir(i) * CARDS.restInset + (dir(i) * CARDS.outward + dir(i) * CARDS.restInset) * clamp(p)).toFixed(2) + 'px)';
        });
        connEls.forEach(function (svg, i) {
            svg.style.opacity = cShow;
            var bead = svg.querySelector('.bead'), k = 0, local, m;
            if (open && !reduce) {
                local = (now - openAt) / 1000 - (.5 + i * .25);
                if (local > 0) { m = local % (CARDS.beadSeconds + 1.4); k = m < CARDS.beadSeconds ? ease(m / CARDS.beadSeconds) : 0; }
            }
            var px = parseFloat(svg.getAttribute('data-px')), nx = parseFloat(svg.getAttribute('data-nx'));
            bead.setAttribute('cx', (nx + (px - nx) * k).toFixed(2));
            bead.setAttribute('opacity', (cShow * Math.sin(Math.PI * k)).toFixed(3));
        });
    }
    function wantsRun() { return onScreen && pageVisible && host.offsetParent !== null; }
    function tick(ts) {
        raf = 0;
        if (!wantsRun()) { return; }
        var dt = Math.min(.05, (ts - last) / 1000 || .016), n = Math.max(1, Math.ceil(dt / .008)), h = dt / n, i;
        last = ts;
        if (reduce) { prog.x = prog.t; prog.v = 0; }
        else { for (i = 0; i < n; i++) { step(prog, EXPL, h); step(sx, PTR, h); step(sy, PTR, h); } }
        render(ts);
        if (!(settled(prog) && settled(sx) && settled(sy)) || (open && !reduce)) { raf = requestAnimationFrame(tick); }
        else { prog.x = prog.t; prog.v = 0; sx.x = sx.t; sx.v = 0; sy.x = sy.t; sy.v = 0; render(ts); }
    }
    function wake() { if (!raf && wantsRun()) { last = performance.now(); raf = requestAnimationFrame(tick); } }
    function syncBeads() {
        var paused = open || !onScreen || !pageVisible;
        svgParticles.forEach(function (s) { try { if (paused) { s.pauseAnimations(); } else { s.unpauseAnimations(); } } catch (e) { /* abaikan */ } });
    }
    function setOpen(v) {
        if (open === v) { return; }
        open = v; prog.t = v ? 1 : 0;
        if (v) { openAt = performance.now(); }
        host.classList.toggle('is-open', v);
        hit.setAttribute('aria-expanded', v ? 'true' : 'false');
        cardEls.forEach(function (el) { el.setAttribute('aria-hidden', v ? 'false' : 'true'); });
        syncBeads(); wake();
    }
    function setHover(i) {
        hovered = i;
        cardEls.forEach(function (el, k) { el.classList.toggle('is-lit', k === i); });
        connEls.forEach(function (svg, k) {
            svg.querySelector('.lit-line').setAttribute('opacity', k === i ? '.9' : '0');
            svg.querySelector('.halo').setAttribute('opacity', (k === i || STAGES[k].main) ? '.4' : '.25');
        });
        if (i !== null && !reduce) { setOpen(true); }
    }

    host.addEventListener('pointermove', function (e) {
        if (reduce || e.pointerType !== 'mouse') { return; }
        var b = host.getBoundingClientRect();
        sx.t = ((e.clientX - b.left) / b.width - .5) * 2; sy.t = ((e.clientY - b.top) / b.height - .5) * 2;
        wake();
    });
    host.addEventListener('pointerleave', function () { sx.t = 0; sy.t = 0; setHover(null); setOpen(false); wake(); });
    hit.addEventListener('pointerdown', function (e) { pointerType = e.pointerType; });
    hit.addEventListener('pointerenter', function (e) { if (e.pointerType === 'mouse' && !reduce) { setOpen(true); } });
    hit.addEventListener('click', function (e) { if (e.detail === 0 || pointerType !== 'mouse') { setOpen(!open); } });
    hit.addEventListener('blur', function () { setOpen(false); });
    cardEls.forEach(function (el, i) {
        el.addEventListener('pointerenter', function (e) { if (e.pointerType === 'mouse') { setHover(i); } });
        el.addEventListener('pointerleave', function () { setHover(null); });
        el.setAttribute('aria-hidden', 'true');
    });
    if ('IntersectionObserver' in window) {
        new IntersectionObserver(function (en) { onScreen = en[0].isIntersecting; syncBeads(); wake(); }, { rootMargin: '40px' }).observe(host);
    }
    document.addEventListener('visibilitychange', function () { pageVisible = !document.hidden; syncBeads(); wake(); });
    window.addEventListener('resize', wake, { passive: true });
    render(performance.now());
    }
    function tryBoot() {
        if (host.offsetParent === null) { return false; }
        boot();
        return true;
    }
    if (!tryBoot()) {
        var onSize = function () { if (tryBoot()) { window.removeEventListener('resize', onSize); } };
        window.addEventListener('resize', onSize);
    }
})();
</script>

@endsection
