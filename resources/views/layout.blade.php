<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#d8d8d4">
    <meta name="description" content="Nilai esai Google Form lebih cepat dengan bantuan AI. Keputusan akhir tetap di tangan anda.">
    <title>@yield('title', 'Essay Grader')</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: ['selector', '[data-theme="dark"]'],
            corePlugins: { preflight: false },
            theme: {
                extend: {
                    colors: {
                        brand: {
                            lime: '#c4f55d',
                            ink: '#1b1b1b',
                            mist: '#f1f1ef',
                            sky: '#cfe2ff',
                            amber: '#ffe27a',
                            coral: '#ff9d8a',
                        }
                    },
                    fontFamily: {
                        display: ['"Space Grotesk"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        mono: ['"Space Mono"', 'ui-monospace', 'monospace'],
                    }
                }
            }
        };
    </script>
    <script defer src="{{ asset('js/alpine.min.js') }}"></script>
    <script>
        /* Tema dibaca paling awal agar halaman tidak berkedip. Pilihan dibagikan ke semua halaman lewat localStorage. */
        (function () {
            var d = document.documentElement;
            d.classList.add('js');
            try {
                var t = localStorage.getItem('eg-theme');
                if (t === 'dark' || t === 'light') { d.setAttribute('data-theme', t); }
            } catch (e) {}
        })();
    </script>
    <style>
        :root {
            --ink: #1b1b1b;
            --paper: #ffffff;
            --mist: #f1f1ef;
            --lime: #c4f55d;
            --back: #d8d8d4;
            --muted: #5f5f5a;
            --sky: #cfe2ff;
            --amber: #ffe27a;
            --coral: #ff9d8a;
            --dark-text: #1b1b1b;      /* teks di atas warna pastel/lime: selalu gelap di kedua mode */
            --surface: #ffffff;        /* latar kartu, input, tombol outline */
            --on-ink: #ffffff;         /* teks di atas tombol/elemen --ink */
            --line: #e3e3df;
            --line-2: #d9d9d4;
            --ph: #8a8a84;
            --err: #9b1c1c;
            --err-bg: #fff5f2;
            --card-dark-bg: #1b1b1b;
            --card-dark-fg: #ffffff;
            --display: "Space Grotesk", ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            --mono: "Space Mono", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        }
        html[data-theme="dark"] {
            color-scheme: dark;
            --ink: #ecece8; --paper: #171716; --mist: #232321; --back: #0d0d0c; --muted: #a4a49d;
            --surface: #1f1f1d; --on-ink: #171716; --line: #33332f; --line-2: #3a3a36; --ph: #77776f;
            --err: #ff9d8a; --err-bg: #2c1c18; --card-dark-bg: #2a2a28; --card-dark-fg: #ecece8;
        }
        * { box-sizing: border-box; }
        /* Hanya html yang memotong overflow horizontal (menjadi penggulir viewport). Jika body juga diberi
           overflow-x: hidden, body menjadi penggulir sendiri dan position: sticky (navbar + tombol tema) berhenti bekerja. */
        html { overflow-x: hidden; width: 100%; }
        body { width: 100%; max-width: 100vw; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0; background: var(--back); color: var(--ink);
            font: 400 13.5px/1.65 var(--mono); -webkit-font-smoothing: antialiased;
        }
        .skip { position: absolute; left: -999px; top: 8px; background: var(--ink); color: var(--on-ink); padding: 8px 12px; border-radius: 8px; z-index: 50; }
        .skip:focus { left: 8px; }

        /* Bingkai halaman: kartu putih membulat di atas latar abu-abu, seperti pada referensi */
        .shell { max-width: 1180px; margin: 24px auto; padding: 22px 36px 28px; background: var(--paper); border-radius: 28px; }

        h1, h2, h3, h4 { font-family: var(--display); font-weight: 700; line-height: 1.15; margin: 0 0 .5em; letter-spacing: -.01em; }
        h1 { font-size: clamp(30px, 5vw, 46px); }
        h2 { font-size: clamp(22px, 3vw, 28px); }
        h3 { font-size: 18px; }
        p { margin: 0 0 1em; }
        :focus-visible { outline: 3px solid var(--ink); outline-offset: 2px; }

        /* Tautan di isi halaman: garis bawah hijau seperti penanda stabilo */
        main a:not(.btn), .foot a {
            color: var(--ink); text-decoration: underline; text-decoration-color: var(--lime);
            text-decoration-thickness: 3px; text-underline-offset: 3px;
        }
        main a:not(.btn):hover, .foot a:hover { background: var(--lime); color: var(--dark-text); }

        [x-cloak] { display: none !important; }

        /* Bilah atas */
        /* Sticky: menempel 14px dari tepi atas layar, jadi kartu putih melengkung tetap terlihat di atasnya */
        .topbar {
            position: sticky; top: 14px; z-index: 40;
            display: flex; flex-direction: column;
            margin: -10px -14px 12px; padding: 10px 14px;
            border: 1.5px solid transparent; border-radius: 18px;
            background: color-mix(in srgb, var(--paper) 86%, transparent);
            -webkit-backdrop-filter: blur(14px) saturate(1.2); backdrop-filter: blur(14px) saturate(1.2);
            transition: border-color .2s, box-shadow .2s, transform .28s ease;
        }
        /* Smart Header: naik keluar layar saat menggulir, turun lagi saat berhenti */
        .topbar.is-hidden { transform: translateY(calc(-100% - 24px)); pointer-events: none; }
        @media (prefers-reduced-motion: reduce) { .topbar { transition: border-color .2s, box-shadow .2s; } }
        .topbar.is-stuck { border-color: var(--ink); box-shadow: 0 4px 0 var(--ink); }
        .topbar-row {
            display: flex; align-items: center; justify-content: space-between; gap: 12px; width: 100%;
        }
        html { scroll-padding-top: 96px; }
        .brand { display: flex; align-items: center; gap: 10px; font: 700 17px var(--display); color: var(--ink); text-decoration: none; flex-shrink: 0; }
        .brand-mark { width: 30px; height: 30px; border-radius: 9px; background: transparent; display: grid; place-items: center; }
        .nav { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; font-size: 12.5px; }
        .nav a { padding: 6px 10px; border-radius: 8px; color: var(--ink); text-decoration: none; }
        .nav a:hover { background: var(--mist); }
        .nav a.active { background: var(--lime); color: var(--dark-text); }
        .nav a.pill { border: 1.5px solid var(--ink); border-radius: 10px; padding: 7px 14px; background: var(--surface); margin-left: 8px; font-weight: 700; }
        .nav a.pill.solid { background: var(--ink); color: var(--on-ink); margin-left: 0; }
        .nav form { margin: 0; }

        .nav-desktop { display: flex; }
        .nav-mobile-bar { display: none; align-items: center; gap: 8px; }

        .nav-user-box {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 12px; min-height: 42px; max-width: 155px;
            border: 1.5px solid var(--ink); border-radius: 10px;
            background: var(--surface); color: var(--ink);
            text-decoration: none !important; font: 700 12.5px var(--display);
            box-shadow: 0 2px 0 var(--ink); vertical-align: middle;
            transition: transform .12s, box-shadow .12s;
        }
        .nav-user-box:hover {
            background: var(--mist); transform: translate(-1px, -1px);
            box-shadow: 2px 2px 0 var(--lime); color: var(--ink);
        }
        .nav-user-name {
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 110px;
        }
        .nav-user-icon { flex-shrink: 0; color: var(--ink); }

        .nav-hamburger-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 44px; height: 44px; min-width: 44px; min-height: 44px; padding: 0;
            border: 1.5px solid var(--ink); border-radius: 10px;
            background: var(--surface); color: var(--ink);
            cursor: pointer; box-shadow: 0 2px 0 var(--ink);
            transition: transform .12s, box-shadow .12s, background .15s;
        }
        .nav-hamburger-btn:hover {
            transform: translate(-1px, -1px); box-shadow: 3px 3px 0 var(--lime); background: var(--mist);
        }
        .nav-hamburger-btn:active { transform: none; box-shadow: none; }

        /* Mengambang di atas konten (absolute terhadap .topbar yang sticky), tidak mendorong elemen di bawahnya */
        .nav-mobile-dropdown {
            position: absolute; top: calc(100% + 8px); left: 0; right: 0; z-index: 60;
            max-height: calc(100vh - 110px); max-height: calc(100dvh - 110px); overflow-y: auto; overscroll-behavior: contain;
            padding: 12px;
            border: 1.5px solid var(--ink); border-radius: 16px;
            background: color-mix(in srgb, var(--paper) 95%, transparent);
            -webkit-backdrop-filter: blur(16px) saturate(1.2); backdrop-filter: blur(16px) saturate(1.2);
            box-shadow: 0 6px 0 var(--ink); display: flex; flex-direction: column; gap: 6px;
        }
        .nav-dropdown-link {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 14px; min-height: 44px; border-radius: 10px;
            font: 700 13px var(--display); color: var(--ink); text-decoration: none !important;
            transition: background .15s;
        }
        .nav-dropdown-link:hover { background: var(--mist); color: var(--ink); }
        .nav-dropdown-link.active { background: var(--lime); color: var(--dark-text); }
        .nav-dropdown-divider { height: 1px; background: var(--line); margin: 4px 0; }
        .nav-dropdown-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; min-height: 44px; padding: 10px 16px;
            font: 700 12.5px/1 var(--mono); border: 1.5px solid var(--ink); border-radius: 10px;
            cursor: pointer; text-decoration: none;
        }

        /* Tombol */
        button, .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            font: 700 12.5px/1 var(--mono); background: var(--ink); color: var(--on-ink);
            border: 1.5px solid var(--ink); border-radius: 10px; padding: 11px 16px;
            cursor: pointer; text-decoration: none; transition: transform .12s, box-shadow .12s;
        }
        button:hover, .btn:hover { transform: translate(-1px, -1px); box-shadow: 3px 3px 0 var(--lime); }
        button:active, .btn:active { transform: none; box-shadow: none; }
        button[disabled] { opacity: .5; cursor: not-allowed; }
        .btn-outline { background: var(--surface); color: var(--ink); }
        .btn-lime { background: var(--lime); color: var(--dark-text); }
        .danger { background: var(--coral) !important; color: var(--dark-text) !important; }
        .linkbtn { background: none; color: var(--ink); border: 0; padding: 6px 10px; font-weight: 400; text-decoration: underline; text-decoration-color: var(--lime); text-decoration-thickness: 3px; text-underline-offset: 3px; }
        .linkbtn:hover { background: var(--mist); box-shadow: none; transform: none; }

        /* Kartu: tepi bawah tebal seperti kartu layanan pada referensi */
        .card { background: var(--surface); border: 1.5px solid var(--ink); border-radius: 18px; padding: 22px; box-shadow: 0 4px 0 var(--ink); }
        .card-dark { background: var(--card-dark-bg); color: var(--card-dark-fg); }
        .card-dark .muted { color: #b9b9b3; }
        .card-dark a:not(.btn) { color: var(--card-dark-fg); }
        .card-dark .btn { background: var(--card-dark-fg); color: var(--card-dark-bg); border-color: var(--card-dark-fg); }
        .card-dark .btn-ghost { background: transparent; color: var(--card-dark-fg); border-color: var(--card-dark-fg); }
        .card-dark :focus-visible { outline-color: var(--card-dark-fg); }
        .card-mist { background: var(--mist); border-color: var(--mist); box-shadow: none; }

        /* Judul bagian bergaya stabilo hijau */
        .tag { display: inline-block; background: var(--lime); padding: 2px 10px; border-radius: 5px; font: 700 20px/1.3 var(--display); margin: 0; color: var(--dark-text); }
        .card-dark .tag { background: var(--card-dark-fg); }
        .sec-head { display: flex; align-items: flex-start; gap: 18px; margin: 44px 0 22px; flex-wrap: wrap; }
        .sec-head p { max-width: 52ch; margin: 3px 0 0; font-size: 12.5px; color: var(--muted); }

        /* Lencana status */
        .badge { display: inline-block; padding: 1px 10px; border: 1.5px solid var(--ink); border-radius: 999px; font: 700 11px/1.7 var(--mono); background: var(--surface); color: var(--ink); vertical-align: middle; }
        .badge.ok { background: var(--lime); }
        .badge.info { background: var(--sky); }
        .badge.warn { background: var(--amber); }
        .badge.bad { background: var(--coral); }
        .badge.ok, .badge.info, .badge.warn, .badge.bad, .badge[class*="type-"] { color: var(--dark-text); }

        .muted { color: var(--muted); font-size: 12.5px; }
        .error { color: var(--err); font-size: 13px; font-weight: 700; }
        code { background: var(--mist); padding: 1px 6px; border-radius: 5px; font-size: 12.5px; }
        .row-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; align-items: center; }
        .flash { background: var(--lime); border: 1.5px solid var(--ink); border-radius: 12px; padding: 10px 14px; margin-bottom: 18px; font-weight: 700; color: var(--dark-text); }

        /* Formulir */
        label { display: block; font-weight: 700; font-size: 12.5px; margin-bottom: 4px; }
        input, textarea, select {
            width: 100%; font: 400 13.5px/1.5 var(--mono); color: var(--ink); background: var(--surface);
            border: 1.5px solid var(--ink); border-radius: 10px; padding: 10px 12px;
        }
        textarea { min-height: 90px; resize: vertical; }
        select { width: auto; min-width: 150px; padding: 8px 10px; }
        input[type=number] { padding: 8px; }
        input::placeholder, textarea::placeholder { color: var(--ph); }
        input:focus-visible, textarea:focus-visible, select:focus-visible { outline: 3px solid var(--lime); outline-offset: 0; box-shadow: 0 0 0 1.5px var(--ink); }
        .field { margin-bottom: 14px; }

        /* Tabel */
        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        th, td { padding: 12px; text-align: left; vertical-align: top; border-bottom: 1px solid var(--line); }
        th { font: 700 12px var(--display); background: var(--mist); white-space: nowrap; }
        th:first-child { border-top-left-radius: 10px; }
        th:last-child { border-top-right-radius: 10px; }
        tr:last-child td { border-bottom: 0; }
        details { margin-top: 8px; }
        summary { cursor: pointer; font-weight: 700; font-size: 12.5px; }
        details[open] { background: var(--mist); border-radius: 12px; padding: 10px 12px; }

        /* Halaman masuk dan daftar */
        .auth { display: grid; grid-template-columns: minmax(380px, 460px) 1fr; gap: 40px; align-items: center; max-width: 1100px; margin: 10px auto 0; }
        .auth-card { padding: 28px; }
        .auth-head { text-align: center; margin-bottom: 18px; }
        .auth-head h1 { font-size: 30px; margin-bottom: 6px; }
        .auth-errors { margin-bottom: 14px; }
        .auth-foot { margin: 18px 0 0; padding-top: 16px; border-top: 1px solid var(--line-2); text-align: center; }
        .btn-block { width: 100%; }
        .btn-google { gap: 10px; padding: 12px 16px; }
        .btn-google svg { width: 18px; height: 18px; flex-shrink: 0; }
        .divider { display: flex; align-items: center; gap: 12px; color: var(--muted); font-size: 12.5px; margin: 16px 0; }
        .divider::before, .divider::after { content: ""; height: 1px; background: var(--line-2); flex: 1; }
        .name-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .pw { position: relative; }
        .pw input { padding-right: 46px; }
        .pw-toggle { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); background: none; border: 0; color: var(--ink); padding: 8px; box-shadow: none; }
        .pw-toggle:hover { background: var(--mist); transform: translateY(-50%); box-shadow: none; }
        input[type=checkbox] { width: 18px; height: 18px; padding: 0; accent-color: var(--ink); flex: 0 0 18px; margin-top: 2px; }
        .consent { display: flex; gap: 10px; align-items: flex-start; margin: 4px 0 18px; font-size: 12.5px; }
        .consent label { font-weight: 400; margin: 0; }
        .auth .art { width: 100%; height: auto; min-height: 420px; max-height: 560px; object-fit: contain; }

        .art { display: block; }
        .foot { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-top: 36px; padding-top: 16px; border-top: 1.5px solid var(--ink); font-size: 12px; color: var(--muted); }

        @media (max-width: 520px) { .name-row { grid-template-columns: 1fr; } }
        @media (max-width: 820px) {
            .shell { margin: 0; border-radius: 0; padding: 18px 14px 28px; width: 100%; box-sizing: border-box; }
            .topbar { top: 8px; margin: -8px -8px 10px; padding: 8px 10px; border-radius: 14px; gap: 8px; }
            .nav-desktop { display: none !important; }
            .nav-mobile-bar { display: flex !important; }
            button, .btn { min-height: 44px; padding: 10px 16px; }
            input, select, textarea { font-size: 14px; }
            .auth { grid-template-columns: 1fr; }
            .auth .art { display: none; }
            .sec-head { margin-top: 24px; }
            .card { padding: 16px; border-radius: 14px; }
            .dlg-actions button { min-height: 44px; }
        }
        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; scroll-behavior: auto !important; }
            html.theme-anim *, html.theme-anim *::before, html.theme-anim *::after { transition: none !important; }
        }

        [hidden] { display: none !important; }
        #toasts { position: fixed; right: 16px; bottom: 16px; display: grid; gap: 8px; z-index: 100; max-width: calc(100% - 32px); }
        .toast { background: var(--ink); color: var(--on-ink); border-radius: 12px; padding: 10px 14px; font-weight: 700; }
        .toast-ok { background: var(--lime); color: var(--dark-text); border: 1.5px solid var(--ink); }
        .toast-error { background: var(--coral); color: var(--dark-text); border: 1.5px solid var(--ink); }
        .toast-out { opacity: 0; transition: opacity .25s; }
        .strength { margin-top: 6px; font-size: 12px; }
        .strength i { display: block; height: 6px; border-radius: 3px; background: var(--line); position: relative; }
        .strength i::after { content: ""; position: absolute; inset: 0 auto 0 0; border-radius: 3px; width: var(--w, 0); background: var(--c, var(--line)); }
        .strength.s1 { --w: 25%; --c: var(--coral); } .strength.s2 { --w: 50%; --c: var(--amber); }
        .strength.s3 { --w: 75%; --c: var(--sky); } .strength.s4 { --w: 100%; --c: var(--lime); }

        /* Tombol mode terang/gelap: melayang di pojok kanan bawah, tetap di dalam batas kartu putih */
        .theme-dock { position: sticky; bottom: 18px; height: 0; z-index: 45; pointer-events: none; }
        .theme-btn { pointer-events: auto; position: absolute; right: 0; bottom: 0; width: 46px; height: 46px; padding: 0; border-radius: 50%; overflow: hidden;
            background: var(--ink); color: var(--on-ink); box-shadow: 0 4px 0 var(--lime); }
        .theme-btn:hover { box-shadow: 0 4px 0 var(--lime), 0 0 0 4px color-mix(in srgb, var(--lime) 45%, transparent); transform: translate(0, -1px); }
        .theme-btn svg { position: absolute; width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
            transition: transform .5s cubic-bezier(.5, 0, .2, 1), opacity .35s ease; }
        .theme-btn .i-moon { opacity: 0; transform: rotate(-90deg) scale(.4); }
        html[data-theme="dark"] .theme-btn .i-sun { opacity: 0; transform: rotate(90deg) scale(.4); }
        html[data-theme="dark"] .theme-btn .i-moon { opacity: 1; transform: none; }
        html.theme-anim *, html.theme-anim *::before, html.theme-anim *::after {
            transition: background-color .45s ease, color .45s ease, border-color .45s ease, box-shadow .45s ease, fill .45s ease, stroke .45s ease !important; }

        /* Mode gelap: penyesuaian untuk elemen yang tidak cukup dengan token warna */
        html[data-theme="dark"] .brand-mark { background: #ecece8; }
        html[data-theme="dark"] .card { border-color: rgba(236, 236, 232, .55); box-shadow: 0 4px 0 rgba(236, 236, 232, .22); }
        html[data-theme="dark"] .card-dark { border-color: rgba(236, 236, 232, .55); }
        html[data-theme="dark"] .topbar.is-stuck { box-shadow: 0 4px 0 rgba(236, 236, 232, .22); }
        html[data-theme="dark"] img.art, html[data-theme="dark"] svg.art { background: #ecece8; border-radius: 20px; padding: 10px; }
        html[data-theme="dark"] .card-dark .art { background: none; padding: 0; }
        html[data-theme="dark"] .flash { border-color: var(--ink); }

        /* Dialog / Popup Modal Tema */
        .dlg { border: 0; padding: 0; background: transparent; max-width: 480px; width: calc(100% - 32px); }
        .dlg::backdrop {
            background: rgba(27, 27, 27, .55);
            -webkit-backdrop-filter: blur(3px);
            backdrop-filter: blur(3px);
            animation: dlgBackdropFade .18s ease-out;
        }
        .dlg[open] {
            animation: dlgPop .2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes dlgPop {
            from { opacity: 0; transform: scale(0.93) translateY(8px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        @keyframes dlgBackdropFade {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .dlg-box { background: var(--surface); color: var(--ink); border: 1.5px solid var(--ink); border-radius: 18px; box-shadow: 0 4px 0 var(--ink); padding: 22px; }
        .dlg-center .dlg-box { text-align: center; }
        .dlg-icon { width: 36px; height: 36px; border-radius: 50%; background: var(--amber); border: 1.5px solid var(--ink); display: grid; place-items: center; font: 700 18px var(--display); color: var(--dark-text); }
        .dlg-icon-lg { width: 52px; height: 52px; margin: 0 auto 12px; font-size: 24px; }
        .dlg-icon-warn { background: var(--amber); }
        .dlg-icon-danger { background: var(--coral); }
        .dlg-icon-primary { background: var(--lime); }
        .dlg-actions { display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap; }
        .dlg-actions button { min-width: 110px; }

        /* Mode gelap untuk halaman lain (dialog, grafik, lencana) yang punya warna tetap di CSS-nya sendiri */
        html[data-theme="dark"] .dlg::backdrop { background: rgba(0, 0, 0, .65); }
        html[data-theme="dark"] .dlg-box, html[data-theme="dark"] .dlg-opt { background: var(--surface); }
        html[data-theme="dark"] .dlg-opt-primary { background: var(--lime); color: var(--dark-text); }
        html[data-theme="dark"] .dlg-opt-primary span { color: var(--dark-text); }
        html[data-theme="dark"] .dlg-opt-danger { border-color: var(--err); }
        html[data-theme="dark"] .dlg-opt-danger b { color: var(--err); }
        html[data-theme="dark"] .dlg-warn { background: var(--err-bg); border-color: var(--err); }
        html[data-theme="dark"] .dlg-icon, html[data-theme="dark"] .respondent-badge { color: var(--dark-text); }
        html[data-theme="dark"] .summary-footer-info { border-top-color: var(--line); }
        html[data-theme="dark"] .chart-host svg line[stroke="#e3e3df"] { stroke: var(--line); }
        html[data-theme="dark"] .chart-host svg line[stroke="#1b1b1b"] { stroke: var(--ink); }
        html[data-theme="dark"] .chart-host svg text[fill="#5f5f5a"] { fill: var(--muted); }
        html[data-theme="dark"] .chart-host svg text[fill="#1b1b1b"], html[data-theme="dark"] .chart-host svg circle[fill="#1b1b1b"] { fill: var(--ink); }
        html[data-theme="dark"] .chart-host svg circle[fill="#c9c9c4"] { fill: var(--line-2); }
        html[data-theme="dark"] .nav-mobile-dropdown {
            border-color: rgba(236, 236, 232, .55);
            box-shadow: 0 6px 0 rgba(236, 236, 232, .22);
            background: color-mix(in srgb, var(--paper) 96%, transparent);
        }
        html[data-theme="dark"] .nav-user-box,
        html[data-theme="dark"] .nav-hamburger-btn {
            border-color: rgba(236, 236, 232, .55);
            box-shadow: 0 2px 0 rgba(236, 236, 232, .22);
        }
    </style>
    @stack('styles')
</head>
<body>
@php
    $cur = fn (...$patterns) => request()->routeIs(...$patterns) ? ' aria-current="page"' : '';
    $has = fn (string $name) => \Illuminate\Support\Facades\Route::has($name);
    $mode = config('registration.mode', 'open');
    $signupOpen = $mode === 'open' || ($mode === 'invite' && filled(config('registration.code')));
    $hasActiveExams = auth()->check() && \App\Models\Exam::where('user_id', auth()->id())->where('status', 'active')->exists();
@endphp
<a class="skip" href="#isi">Lewati ke konten</a>
<div class="shell">
    <header class="topbar" 
            x-data="{ open: false }" 
            @keydown.escape.window="open = false" 
            @click.outside="open = false">
        <div class="topbar-row flex items-center justify-between w-full gap-3">
            <a class="brand flex items-center gap-2.5 font-bold text-[17px] text-ink shrink-0 no-underline" href="{{ auth()->check() ? route('dashboard') : url('/') }}">
                <span class="brand-mark w-[30px] h-[30px] grid place-items-center" aria-hidden="true">
                    <img src="{{ asset('favicon.svg') }}" style="width: 30px; height: 30px; object-fit: contain;" alt="">
                </span>
                <span>Essay Grader</span>
            </a>

            {{-- Desktop Navigation (Tampil di Desktop / Layar Lebar) --}}
            <nav class="nav nav-desktop hidden md:flex items-center gap-1 text-[12.5px]" aria-label="Menu utama">
                @auth
                    <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard', 'exams.*', 'questions.*', 'answers.*')]){!! $cur('dashboard', 'exams.*', 'questions.*', 'answers.*') !!}>Penilaian</a>
                    <a href="{{ route('google.index') }}" @class(['active' => request()->routeIs('google.*')]){!! $cur('google.*') !!}>Google</a>
                    @if(auth()->user()->is_admin)
                        <a href="{{ route('keys.index') }}" @class(['active' => request()->routeIs('keys.*')]){!! $cur('keys.*') !!}>Kunci AI</a>
                        @if($has('admin.status'))
                            <a href="{{ route('admin.status') }}" @class(['active' => request()->routeIs('admin.status')]){!! $cur('admin.status') !!}>Status sistem</a>
                        @endif
                    @endif
                    @if($has('account.show'))
                        <a class="pill" href="{{ route('account.show') }}"{!! $cur('account.*') !!}>{{ auth()->user()->name }}</a>
                    @else
                        <span class="muted" style="margin-left:8px">{{ auth()->user()->name }}</span>
                    @endif
                    <form class="logout-form" method="POST" action="{{ route('logout') }}" data-has-active="{{ $hasActiveExams ? 1 : 0 }}">
                        @csrf
                        <button type="submit" class="linkbtn">Keluar</button>
                    </form>
                @else
                    <a href="{{ url('/') }}" @class(['active' => request()->is('/')])>Beranda</a>
                    <a href="{{ route('privacy') }}" @class(['active' => request()->routeIs('privacy')])>Privasi</a>
                    <a class="pill" href="{{ route('login') }}">Masuk</a>
                    @if($signupOpen)
                        <a class="pill solid" href="{{ route('register') }}">Daftar</a>
                    @endif
                @endauth
            </nav>

            {{-- Mobile Bar (Tampil di Mobile: Kotak Nama User + Tombol Hamburger) --}}
            <div class="nav-mobile-bar flex md:hidden items-center gap-2">
                @auth
                    {{-- Kotak Nama Pengguna di sisi kanan berdampingan dengan hamburger menu --}}
                    @if($has('account.show'))
                        <a class="nav-user-box" href="{{ route('account.show') }}" title="Akun: {{ auth()->user()->name }}">
                            <svg class="nav-user-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <span class="nav-user-name">{{ auth()->user()->name }}</span>
                        </a>
                    @else
                        <span class="nav-user-box">
                            <svg class="nav-user-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <span class="nav-user-name">{{ auth()->user()->name }}</span>
                        </span>
                    @endif
                @endauth

                {{-- Tombol Hamburger Menu (☰) --}}
                <button type="button" 
                        class="nav-hamburger-btn" 
                        @click="open = !open" 
                        :aria-expanded="open" 
                        aria-label="Buka menu navigasi">
                    <svg x-show="!open" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                    <svg x-show="open" x-cloak width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Dropdown Menu Navigasi Mobile (Alpine.js) --}}
        <div x-show="open" 
             x-cloak 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2 scale-98"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 -translate-y-2 scale-98"
             class="nav-mobile-dropdown"
             role="menu"
             aria-label="Menu navigasi mobile">
            @auth
                {{-- Menu Dashboard / Internal --}}
                <a href="{{ route('dashboard') }}" 
                   class="nav-dropdown-link @if(request()->routeIs('dashboard', 'exams.*', 'questions.*', 'answers.*')) active @endif"
                   @click="open = false">
                    <span>Penilaian</span>
                    <span class="badge" style="font-size:10px">Ujian</span>
                </a>
                <a href="{{ route('google.index') }}" 
                   class="nav-dropdown-link @if(request()->routeIs('google.*')) active @endif"
                   @click="open = false">
                    <span>Google</span>
                    <span class="badge" style="font-size:10px">Koneksi</span>
                </a>
                @if(auth()->user()->is_admin)
                    <a href="{{ route('keys.index') }}" 
                       class="nav-dropdown-link @if(request()->routeIs('keys.*')) active @endif"
                       @click="open = false">
                        <span>Kunci AI</span>
                        <span class="badge info" style="font-size:10px">Admin</span>
                    </a>
                    @if($has('admin.status'))
                        <a href="{{ route('admin.status') }}" 
                           class="nav-dropdown-link @if(request()->routeIs('admin.status')) active @endif"
                           @click="open = false">
                            <span>Status sistem</span>
                            <span class="badge ok" style="font-size:10px">Admin</span>
                        </a>
                    @endif
                @endif

                <div class="nav-dropdown-divider"></div>

                <form class="logout-form" method="POST" action="{{ route('logout') }}" data-has-active="{{ $hasActiveExams ? 1 : 0 }}">
                    @csrf
                    <button type="submit" class="nav-dropdown-btn danger">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        <span>Keluar Akun</span>
                    </button>
                </form>
            @else
                {{-- Menu Publik / Landing Page --}}
                <a href="{{ url('/') }}" 
                   class="nav-dropdown-link @if(request()->is('/')) active @endif"
                   @click="open = false">
                    <span>Beranda</span>
                </a>
                <a href="{{ route('privacy') }}" 
                   class="nav-dropdown-link @if(request()->routeIs('privacy')) active @endif"
                   @click="open = false">
                    <span>Privasi</span>
                </a>
                @if($has('terms'))
                    <a href="{{ route('terms') }}" 
                       class="nav-dropdown-link @if(request()->routeIs('terms')) active @endif"
                       @click="open = false">
                        <span>Ketentuan Layanan</span>
                    </a>
                @endif

                <div class="nav-dropdown-divider"></div>

                <div style="display:flex; flex-direction:column; gap:8px;">
                    <a class="btn btn-outline" href="{{ route('login') }}" style="width:100%; justify-content:center; text-align:center;">
                        Masuk
                    </a>
                    @if($signupOpen)
                        <a class="btn btn-lime" href="{{ route('register') }}" style="width:100%; justify-content:center; text-align:center;">
                            Daftar
                        </a>
                    @endif
                </div>
            @endauth
        </div>
    </header>

    <main id="isi">
        @if(session('status'))
            <div class="flash" role="status">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>

    {{-- Tombol mode terang/gelap: ada di semua halaman, pilihan tersimpan di peramban --}}
    <div class="theme-dock">
        <button type="button" class="theme-btn" id="theme-toggle" aria-label="Ganti ke mode gelap" aria-pressed="false">
            <svg class="i-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            <svg class="i-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
        </button>
    </div>

    <footer class="foot">
        <span>Nilai dari AI adalah bantuan penilaian. Keputusan akhir ada pada pembuat.</span>
        <span>
            <a href="{{ route('privacy') }}">Kebijakan privasi</a>
            @if($has('terms'))
                <a href="{{ route('terms') }}" style="margin-left:14px">Ketentuan layanan</a>
            @endif
        </span>
    </footer>
</div>
<script>
    // Topbar sticky + Smart Header: garis/bayangan saat menempel; sembunyi saat menggulir, muncul lagi saat berhenti
    (function () {
        var bar = document.querySelector('.topbar');
        if (!bar) { return; }
        var busy = false, lastY = window.scrollY, idle = 0;
        function menuOpen() {
            var b = bar.querySelector('.nav-hamburger-btn');
            return !!b && b.getAttribute('aria-expanded') === 'true';
        }
        function show() { bar.classList.remove('is-hidden'); }
        function update() {
            busy = false;
            var y = window.scrollY, top = parseFloat(getComputedStyle(bar).top) || 0;
            bar.classList.toggle('is-stuck', y > 8 && bar.getBoundingClientRect().top <= top + 1);
            // sembunyi hanya saat benar-benar menggulir, bukan di puncak halaman, dan tidak saat menu/fokus aktif di bilah
            if (Math.abs(y - lastY) > 3 && y > 90 && !menuOpen() && !bar.matches(':focus-within')) {
                bar.classList.add('is-hidden');
            }
            lastY = y;
            clearTimeout(idle);
            idle = setTimeout(show, 220);
        }
        window.addEventListener('scroll', function () {
            if (!busy) { busy = true; requestAnimationFrame(update); }
        }, window.egSignal ? { passive: true, signal: egSignal() } : { passive: true });
        update();
    })();
    // Mode terang/gelap
    (function () {
        var root = document.documentElement, btn = document.getElementById('theme-toggle');
        var meta = document.querySelector('meta[name="theme-color"]');
        if (!btn) { return; }
        function current() { return root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light'; }
        function sync() {
            var dark = current() === 'dark';
            btn.setAttribute('aria-pressed', dark ? 'true' : 'false');
            btn.setAttribute('aria-label', dark ? 'Ganti ke mode terang' : 'Ganti ke mode gelap');
            if (meta) { meta.setAttribute('content', dark ? '#0d0d0c' : '#d8d8d4'); }
        }
        sync();
        btn.addEventListener('click', function () {
            var next = current() === 'dark' ? 'light' : 'dark';
            root.classList.add('theme-anim');
            root.setAttribute('data-theme', next);
            try { localStorage.setItem('eg-theme', next); } catch (e) {}
            sync();
            window.dispatchEvent(new CustomEvent('themechange', { detail: next }));
            setTimeout(function () { root.classList.remove('theme-anim'); }, 520);
        });
        // Tab lain berganti tema: ikuti
        window.addEventListener('storage', function (e) {
            if (e.key === 'eg-theme' && (e.newValue === 'dark' || e.newValue === 'light')) { root.setAttribute('data-theme', e.newValue); sync(); }
        });
    })();
    // Tombol mata: tampilkan atau sembunyikan password
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.pw-toggle');
        if (!btn) { return; }
        var input = document.getElementById(btn.getAttribute('data-target'));
        if (!input) { return; }
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
    });
</script>
<dialog id="confirm-dialog" class="dlg dlg-center" aria-labelledby="cf-title" aria-describedby="cf-text">
    <div class="dlg-box">
        <span id="cf-icon" class="dlg-icon dlg-icon-lg dlg-icon-warn" aria-hidden="true">!</span>
        <h3 id="cf-title">Konfirmasi</h3>
        <p id="cf-text" class="muted" style="white-space:pre-line; margin-bottom:18px"></p>
        <div class="dlg-actions" style="justify-content:center; gap:10px">
            <button type="button" id="cf-cancel" class="btn-outline" data-close autofocus>Batalkan</button>
            <button type="button" id="cf-ok">Lanjutkan</button>
        </div>
    </div>
</dialog>
<div id="toasts" aria-live="polite" role="status"></div>
<script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
