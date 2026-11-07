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
    .hero { position: relative; isolation: isolate; display: grid; grid-template-columns: 1.05fr .95fr; gap: 28px; align-items: center; padding: 12px 0 30px; }
    .hero .lead { max-width: 48ch; }
    .hero-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 22px; }
    .hero .art { width: 100%; height: auto; max-height: 400px; object-fit: contain; animation: art-float 7s ease-in-out infinite; }
    @keyframes art-float { 50% { transform: translateY(-10px); } }

    /* Latar iridescence satu halaman penuh. Kanvas menempel (sticky) setinggi layar di dalam pembungkus
       setinggi seluruh konten, sehingga murah dirender. Tepi atas/bawah memudar agar tidak menabrak topbar dan footer. */
    #isi { position: relative; isolation: isolate; }
    .page-bg { position: absolute; inset: 0 -36px; z-index: -1; pointer-events: none;
        -webkit-mask-image: linear-gradient(to bottom, transparent 0, #000 260px, #000 calc(100% - 180px), transparent 100%);
                mask-image: linear-gradient(to bottom, transparent 0, #000 260px, #000 calc(100% - 180px), transparent 100%); }
    .page-bg canvas { position: sticky; top: 0; display: block; width: 100%; height: 100vh; height: 100svh; opacity: .8; }
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
    @keyframes st-draw  { to { clip-path: inset(-14% -4% -14% 0); } }
    @keyframes st-flood { to { background-size: 100% 100%; } }
    .hero .lead, .hero-actions { animation: rise .7s ease both; animation-delay: 1.1s; }
    .hero-actions { animation-delay: 1.25s; }
    @keyframes rise { from { opacity: 0; transform: translateY(14px); } }

    /* =========================================================
       4. Tombol Specular (pantulan cahaya mengikuti kursor)
       ========================================================= */
    .spec { position: relative; overflow: hidden; isolation: isolate; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .28); }
    .spec.btn-outline { box-shadow: inset 0 -2px 0 rgba(27, 27, 27, .07); }
    .spec-label { position: relative; z-index: 2; display: inline-flex; align-items: center; gap: 8px; }
    .spec::before { content: ""; position: absolute; inset: 0; z-index: 1; pointer-events: none; opacity: 0; transition: opacity .25s;
        background: radial-gradient(96px circle at var(--bx, 50%) var(--by, 0%), rgba(255, 255, 255, .5), rgba(255, 255, 255, .12) 45%, transparent 72%); }
    .spec.btn-outline::before { background: radial-gradient(96px circle at var(--bx, 50%) var(--by, 0%), rgba(196, 245, 93, .85), rgba(196, 245, 93, .25) 50%, transparent 74%); }
    .spec::after { content: ""; position: absolute; top: -25%; bottom: -25%; left: -60%; width: 38%; z-index: 1; pointer-events: none; opacity: 0;
        background: linear-gradient(105deg, transparent, rgba(255, 255, 255, .55), transparent); transform: skewX(-18deg); }
    .spec.btn-outline::after { background: linear-gradient(105deg, transparent, rgba(196, 245, 93, .8), transparent); }
    .spec:hover::before, .spec:focus-visible::before { opacity: 1; }
    .spec:hover::after { animation: spec-sweep .85s ease; }
    .spec:hover { box-shadow: 3px 3px 0 var(--lime), inset 0 1px 0 rgba(255, 255, 255, .28); }
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

    .hiw-stage { position: relative; height: clamp(600px, 82vh, 720px); margin-top: 8px; user-select: none; -webkit-user-select: none; }

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
    .hiw-step h3 mark { color: #1b1b1b; background: var(--lime); padding: 0 .14em; margin-left: -.04em; border-radius: .14em; -webkit-box-decoration-break: clone; box-decoration-break: clone; }
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

/* Legenda mesin: hanya angka dalam lingkaran lime, rata tengah di bawah ilustrasi */
    .hm .hm-legend { justify-content: center; gap: 12px; }
    .hm .hm-legend .hm-chip { font-size: 0; gap: 0; padding: 0; background: none; border: 0; border-radius: 50%; box-shadow: none; }
    .hm .hm-legend .hm-chip-no { width: 34px; height: 34px; font-size: 13.5px; transition: transform .2s ease, box-shadow .2s ease; }
    .hm .hm-legend .hm-chip:hover, .hm .hm-legend .hm-chip:focus-visible,
    .hm .hm-legend .hm-chip[aria-pressed="true"] { background: none; box-shadow: none; }
    .hm .hm-legend .hm-chip:hover .hm-chip-no,
    .hm .hm-legend .hm-chip:focus-visible .hm-chip-no { transform: translateY(-2px); box-shadow: 0 0 0 4px color-mix(in srgb, var(--lime) 45%, transparent); }
    .hm .hm-legend .hm-chip[aria-pressed="true"] .hm-chip-no { transform: scale(1.12); box-shadow: 0 0 0 4px color-mix(in srgb, var(--lime) 45%, transparent), 2px 2px 0 var(--ink); }

    /* =========================================================
       Responsif
       ========================================================= */
    @media (max-width: 820px) {
        .hero, .band, .aspek { grid-template-columns: 1fr; }
        .page-bg { inset: 0 -16px; }
        .hero .art { max-height: 280px; }
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
        .hero .lead, .hero-actions, .hero .art { animation: none; }
        .js .reveal { opacity: 1; translate: 0 0; transition: none; }
        .cloud-track { animation: none; }
        .cloud-set[aria-hidden="true"] { display: none; }
        .spec:hover::after { animation: none; }
        .sw, .sw.is-drop { transition: none; }
    }
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

<div class="page-bg" aria-hidden="true"><canvas></canvas></div>

<section class="hero">
    <div>
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
            <a class="btn spec" href="{{ route('login') }}" data-rocket><span class="spec-label">Masuk</span></a>
            @if($signupOpen)
                <a class="btn btn-outline spec" href="{{ route('register') }}"><span class="spec-label">Daftar</span></a>
            @endif
            <a class="btn btn-outline spec" href="#cara-kerja"><span class="spec-label">Lihat cara kerja</span></a>
        </div>
    </div>
    {{-- Mesin 3D: empat stasiun = empat langkah cara kerja (gambar SVG lama dipakai sebagai fallback di dalam partial) --}}
    @include('hero-machine')
</section>

@php
    // Logo showcase: nama app, berkas SVG di public/images/, dan ikon cadangan (dipakai bila berkasnya belum ada).
    // Ganti nama berkas di kolom ke-2 sesuai nama SVG yang Anda simpan (huruf besar/kecil harus sama di server Linux).
    $showNames = true;   // false bila SVG Anda sudah berupa wordmark yang memuat tulisan nama app
    $tools = [
        ['Akun Google', 'akun_google.svg', '<path d="M9 3v4M15 3v4"/><path d="M7 7h10v4a5 5 0 0 1-10 0V7z"/><path d="M12 16v5"/>'],
        ['Google Forms', 'google_forms.svg', '<rect x="5" y="3" width="14" height="18" rx="2.5"/><path d="M5 8h14"/><rect x="8" y="11" width="2.6" height="2.6" rx=".7"/><path d="M12.8 12.3H16"/><rect x="8" y="16" width="2.6" height="2.6" rx=".7"/><path d="M12.8 17.3H16"/>'],
        ['Groq AI', 'groq_ai.svg', '<path d="M12 3l6 9-6 9-6-9z"/><path d="M6 12h12"/><ellipse cx="12" cy="12" rx="10" ry="3.4" transform="rotate(-24 12 12)"/>'],
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
                    <div class="hw-row"><b>Rekap nilai kelas</b><span class="mono">.xlsx</span></div>
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
    var world = stage.querySelector('.hiw-world'), scene = stage.querySelector('.hiw-scene');
    var scroller = stage.querySelector('.hiw-scroller'), hint = stage.querySelector('.hiw-hint');
    var steps = [].slice.call(stage.querySelectorAll('.hiw-step'));
    var segs = [].slice.call(stage.querySelectorAll('.hiw-seg'));
    var fills = segs.map(function (s) { return s.querySelector('i'); });
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var N = 6;
    sec.classList.add('is-js');

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
            '<div style="margin-top:12px;font:800 17px var(--display)">Kuis Esai Biologi</div><div style="margin-top:8px;height:6px;border-radius:3px;background:#c9c9c4"></div><div style="margin-top:7px;height:6px;width:70%;border-radius:3px;background:#c9c9c4"></div><div style="margin-top:7px;height:6px;width:84%;border-radius:3px;background:#c9c9c4"></div>' }) +
        disc({ x: 50, y: 0, r: 62, h: 12, c: '#c4f55d', html: '<svg viewBox="0 0 24 24" style="position:absolute;inset:26%;width:48%;height:48%;fill:none;stroke:#1b1b1b;stroke-width:2.4;stroke-linecap:round;stroke-linejoin:round"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg>' }) +
        bb({ x: 130, y: 110, z: 0, w: 110, h: 46, cls: 'bb-card', html: '<small style="opacity:1;font-weight:700;color:#1b1b1b">TITIK AWAL</small><span style="font-size:13px">Tempel link</span>' }));

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
        bb({ x: -175, y: -105, z: 0, w: 168, h: 90, cls: 'bb-card', html: '<small style="color:#1b1b1b;opacity:1;font-weight:700">KATA KUNCI</small><div style="margin-top:4px"><span class="chip hl">fotosintesis</span><span class="chip">klorofil</span><span class="chip hl">makna&nbsp;&#10003;</span></div>' }) +
        bb({ x: -170, y: 120, z: 0, w: 168, h: 92, cls: 'bb-card', html: '<b>Relevansi rubrik</b><div class="meter"><i></i></div><small style="opacity:1;font-weight:700">92% cocok</small>' }));

    /* 5. Review manual: mesin keluaran skor + tabel interaktif + pengajar */
    h += station(4, 400, 380, 0, 0,
        box({ x: -190, y: -150, w: 130, d: 110, h: 76, c: '#1b1b1b', top: '<div style="display:grid;place-items:center;height:100%;font:700 11px var(--mono);color:#c4f55d">SKOR</div>' }) +
        bb({ x: 70, y: -80, z: 0, w: 244, h: 160, cls: 'bb-card', html: '<table class="tbl"><tr><th>Siswa</th><th>Nilai</th></tr><tr><td>Rina A.</td><td>86</td></tr><tr><td>Dimas P.</td><td><span class="ed"><b>78</b><b>84</b></span></td></tr><tr><td>Salsa M.</td><td>91</td></tr></table>' }) +
        bb({ x: 168, y: 96, z: 0, w: 82, h: 130, html: teacherSVG() }));

    /* 6. Analytics: monitor grafik + ikon unduh Excel */
    h += station(5, 420, 380, 20, 0,
        box({ x: 40, y: -110, w: 240, d: 22, h: 164, c: '#1b1b1b', front: '<div class="mon"><div class="mon-h"><i></i><i></i><i></i><span>statistik_kelas</span></div><div class="mon-b"><i style="--h:42%"></i><i style="--h:68%"></i><i style="--h:92%"></i><i style="--h:58%"></i><i style="--h:76%"></i></div><div class="mon-f"><span>Rata-rata 82</span><span>Lulus 91%</span></div></div>', cls: 'mon-wrap' }) +
        box({ x: 120, y: -104, w: 80, d: 40, h: 8, c: '#1b1b1b', z: 0 }) +
        box({ x: -170, y: 40, w: 104, d: 118, h: 12, c: '#c4f55d', top: '<div class="xl"><b>XLSX</b></div>' }) +
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
            var k = reduce ? 1 : clamp((now - dragT0 - 350) / 1700, 0, 1);
            off = -(1 - easeOut(k)) * 300; lift = (1 - easeOut(k)) * 64;
            curOn = k < 1 || (now - dragT0 - 350 - 1700) < 700;
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
        var auto = autoOn && now > userUntil && !tween && !touching && !dragging;
        var f = auto ? clamp((now - arrive) / DW[idx], 0, 1) : 1;
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
        userUntil = performance.now() + 9000;
        if (hint) { hint.classList.add('gone'); }
    }
    function loop(now) {
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
            if (autoOn && now > userUntil && !tween && !touching && !dragging && now - arrive > DW[idx]) {
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
    window.addEventListener('resize', onResize);
    layout();

    if ('IntersectionObserver' in window) {
        new IntersectionObserver(function (en) {
            visible = en[0].isIntersecting;
            sec.classList.toggle('is-paused', !visible);
            if (visible) { arrive = performance.now(); }
        }, { threshold: 0 }).observe(stage);
    }
    document.addEventListener('visibilitychange', function () { sec.classList.toggle('is-paused', document.hidden); if (!document.hidden) { arrive = performance.now(); } });
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


<script>
(function () {
    'use strict';
    var root = document.documentElement;
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
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
        document.addEventListener('visibilitychange', schedule);
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
        var canvas = $('canvas', host);
        var gl = null;
        try { gl = canvas.getContext('webgl', { antialias: false, alpha: false, powerPreference: 'low-power' }); } catch (e) {}
        if (!gl) { host.classList.add('no-gl'); return; }

        var vsrc = 'attribute vec2 p; varying vec2 vUv; void main(){ vUv = p * .5 + .5; gl_Position = vec4(p, 0., 1.); }';
        var fsrc = [
            '#ifdef GL_FRAGMENT_PRECISION_HIGH', 'precision highp float;', '#else', 'precision mediump float;', '#endif',
            'varying vec2 vUv; uniform float uTime; uniform vec2 uRes; uniform vec2 uMouse; uniform float uDark;',
            'void main(){',
            '  vec2 asp = vec2(uRes.x / uRes.y, 1.);',
            '  float dm = distance(vUv * asp, uMouse * asp);',
            '  float m = exp(-dm * dm * 9.);',
            '  vec2 uv = vUv * asp * 1.1;',
            '  uv += (uMouse - .5) * .38;',
            '  uv += (vUv - uMouse) * m * .4;',
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
        var uTime = gl.getUniformLocation(prog, 'uTime'), uRes = gl.getUniformLocation(prog, 'uRes'),
            uMouse = gl.getUniformLocation(prog, 'uMouse'), uDark = gl.getUniformLocation(prog, 'uDark');

        var mouse = [.5, .5], target = [.5, .5], t0 = performance.now(), last = 0, raf = 0, onScreen = true;

        function resize() {
            var w = Math.max(2, Math.round(canvas.clientWidth * .5)), h = Math.max(2, Math.round(canvas.clientHeight * .5));
            if (canvas.width !== w || canvas.height !== h) { canvas.width = w; canvas.height = h; gl.viewport(0, 0, w, h); }
        }
        function draw(time) {
            resize();
            gl.uniform1f(uTime, time);
            gl.uniform2f(uRes, canvas.width, canvas.height);
            gl.uniform2f(uMouse, mouse[0], mouse[1]);
            gl.uniform1f(uDark, currentTheme() === 'dark' ? 1 : 0);
            gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
        }
        function frame(now) {
            raf = 0;
            if (now - last >= 33) {
                last = now;
                mouse[0] += (target[0] - mouse[0]) * .09;
                mouse[1] += (target[1] - mouse[1]) * .09;
                draw((now - t0) / 1000 + 6);
            }
            if (onScreen && !document.hidden) { raf = requestAnimationFrame(frame); }
        }
        function start() { if (!reduce && !raf) { raf = requestAnimationFrame(frame); } }

        function aim(e) {
            var r = canvas.getBoundingClientRect();
            if (!r.width || !r.height) { return; }
            target = [Math.min(1.2, Math.max(-.2, (e.clientX - r.left) / r.width)),
                      Math.min(1.2, Math.max(-.2, 1 - (e.clientY - r.top) / r.height))];
        }
        window.addEventListener('pointermove', aim, { passive: true });
        window.addEventListener('pointerdown', aim, { passive: true });

        if (reduce) {
            draw(14);
            window.addEventListener('resize', function () { draw(14); });
            new MutationObserver(function () { draw(14); }).observe(root, { attributes: true, attributeFilter: ['data-theme'] });
            return;
        }
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (en) { onScreen = en[0].isIntersecting; start(); }).observe(host);
        }
        document.addEventListener('visibilitychange', start);
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
        setInterval(function () {
            if (!inView || document.hidden) { return; }
            title.classList.add('st-reset');
            void title.offsetWidth;
            title.classList.remove('st-reset');
        }, 5000);
    })();

    /* ---------- Parallax ilustrasi (satu handler scroll) ---------- */
    (function () {
        // [selector, kecepatan]. Positif = bergerak lebih lambat dari halaman, negatif = berlawanan.
        var CONFIG = [['.hero .art', .12]];
        var items = [];
        if (!reduce) {
            CONFIG.forEach(function (c) {
                $$(c[0]).forEach(function (el) { items.push({ el: el, host: el.parentElement, k: c[1] }); });
            });
        }
        function parallax() {
            var vh = window.innerHeight;
            items.forEach(function (it) {
                var r = it.host.getBoundingClientRect();
                if (r.bottom < -200 || r.top > vh + 200) { return; }
                var offset = (r.top + r.height / 2 - vh / 2) * it.k;
                it.el.style.translate = '0 ' + (-offset).toFixed(1) + 'px';
            });
        }
        var ticking = false;
        function update() { ticking = false; if (!reduce) { parallax(); } }
        function onScroll() { if (!ticking) { ticking = true; requestAnimationFrame(update); } }
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll);
        update();
    })();
})();
</script>
@endsection
