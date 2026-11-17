<style>
    /* ============ Halaman Statistik User (.ux) ============ */
    .ux {
        --ux-card: #ffffff; --ux-line: #e8e8e3; --ux-ink: #17171a; --ux-mut: #7a7a72; --ux-soft: #f6f6f3;
        --ux-green: #12b863; --ux-red: #e5484d; --ux-shadow: 0 1px 0 rgba(0,0,0,.02), 0 10px 28px -14px rgba(0,0,0,.18);
        --t-a: #b8b8b4; --t-b: #ff5a1f; --t-c: #ffb800; --t-d: #8b3dff; --t-e: #7cff5b;
        padding: 18px; border-radius: 24px;
        background-image: radial-gradient(circle, color-mix(in oklab, var(--ink) 9%, transparent) 0 1px, transparent 1.4px);
        background-size: 20px 13px;
    }
    html[data-theme="dark"] .ux {
        --ux-card: #1c1c1a; --ux-line: #32322e; --ux-ink: #ecece8; --ux-mut: #a1a19a; --ux-soft: #252523;
        --ux-green: #3bf08c; --ux-red: #ff8a80; --ux-shadow: none;
        --t-a: #6b6b66; --t-b: #ff6a33; --t-c: #ffc61a; --t-d: #9a5cff; --t-e: #7cff5b;
    }
    .ux-card { position: relative; min-width: 0; padding: 20px; border-radius: 28px; border: 1px solid var(--ux-line);
               background: var(--ux-card); color: var(--ux-ink); box-shadow: var(--ux-shadow);
               animation: uxIn .5s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i, 0) * 50ms); }
    @keyframes uxIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    .ux-label { font: 500 13px var(--mono); color: var(--ux-mut); margin: 0; }
    .ux-big { font: 700 clamp(32px, 4.2vw, 46px)/1 var(--mono); letter-spacing: -.04em; font-variant-numeric: tabular-nums; margin: 6px 0 0; }
    .ux-sub { font: 400 12px var(--mono); color: var(--ux-mut); margin-top: 6px; }
    .ux-delta { font: 700 12px var(--mono); }
    .ux-delta.up { color: var(--ux-green); } .ux-delta.down { color: var(--ux-red); }
    .ux-frame { border: 1px solid var(--ux-line); border-radius: 18px; background: var(--ux-soft); padding: 8px 10px 6px; min-width: 0; }
    .ux-frame small { display: block; font: 400 10.5px var(--mono); color: var(--ux-mut); margin-bottom: 2px; }
    .ux-legend { display: flex; flex-wrap: wrap; gap: 4px 12px; margin-top: 14px; font: 400 11.5px var(--mono); color: var(--ux-mut); }
    .ux-legend i { display: inline-block; width: 14px; height: 3px; border-radius: 2px; margin-right: 5px; vertical-align: middle; }
    .ux-sq { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-right: 5px; }

    .ux-spark { display: block; width: 100%; height: auto; overflow: visible; }
    .ux-spark .gl { stroke: var(--ux-line); stroke-width: 1; stroke-dasharray: 2 3; }
    .ux-spark .ln { fill: none; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }
    .ux-spark text { fill: var(--ux-mut); font: 400 9.5px var(--mono); }
    .ux-spark .hit { fill: transparent; }
    .ux-spark .v { stroke: var(--ux-mut); stroke-dasharray: 3 3; opacity: 0; }
    .ux-spark .pt:hover .v { opacity: .6; }

    /* Tab dan segmented: reset gaya tombol global layout */
    .ux button, .adm button { box-shadow: none; }
    .ux button:hover, .adm button:hover { transform: none; box-shadow: none; }
    .ux-seg { display: inline-flex; padding: 3px; border-radius: 12px; background: var(--ux-soft); }
    .ux-seg button { min-height: 0; padding: 6px 12px; border: 0; border-radius: 9px; background: transparent; color: var(--ux-mut); font: 600 12px var(--mono); }
    .ux-seg button.on { background: var(--ux-card); color: var(--ux-ink); box-shadow: 0 1px 3px rgba(0,0,0,.18); }
    .ux-tabs { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin: 16px 0 14px; }
    .ux-tab { display: block; text-align: left; min-height: 0; padding: 0; border: 0; background: transparent; color: var(--ux-ink); opacity: .55; transition: opacity .2s; }
    .ux-tab.on { opacity: 1; }
    .ux-tab .tl { display: flex; align-items: center; gap: 6px; font: 600 12px var(--mono); }
    .ux-tab .tl i { width: 6px; height: 6px; border-radius: 50%; background: var(--ux-mut); }
    .ux-tab.on .tl i { background: var(--ux-green); }
    .ux-tab .tn { display: block; margin-top: 6px; font: 700 clamp(20px, 2.6vw, 30px)/1 var(--mono); letter-spacing: -.03em; }
    .ux-tab .ts { display: block; margin-top: 4px; font: 400 11px var(--mono); color: var(--ux-mut); }

    /* Treemap */
    .ux-tree { position: relative; width: 100%; height: 300px; }
    .ux-tile { position: absolute; padding: 3px; transition: left .55s cubic-bezier(.22,1,.36,1), top .55s cubic-bezier(.22,1,.36,1), width .55s cubic-bezier(.22,1,.36,1), height .55s cubic-bezier(.22,1,.36,1); }
    .ux-tile > div { position: relative; width: 100%; height: 100%; border-radius: 18px; padding: 10px 11px; overflow: hidden; color: #111; }
    .ux-tile .nm { display: inline-flex; align-items: center; gap: 6px; font: 700 10.5px var(--mono); }
    .ux-tile .nm b { display: grid; place-items: center; width: 15px; height: 15px; border-radius: 5px; background: #111; color: #fff; font-size: 11px; line-height: 1; }
    .ux-tile .ct { position: absolute; left: 11px; bottom: 9px; font: 700 clamp(16px, 2.1vw, 28px)/1 var(--mono); letter-spacing: -.03em; }
    .ux-tile .pc { position: absolute; right: 9px; bottom: 9px; padding: 2px 7px; border-radius: 8px; background: #111; color: #fff; font: 700 9.5px var(--mono); }
    .ux-tile.light > div { color: #fff; }
    .ux-table { width: 100%; margin-top: 16px; }
    .ux-table th, .ux-table td { padding: 11px 0; border-bottom: 1px solid var(--ux-line); background: transparent; border-radius: 0; font: 400 12.5px var(--mono); }
    .ux-table th { color: var(--ux-mut); font-weight: 400; }
    .ux-table tr:last-child td { border-bottom: 0; }
    .ux-table .r { text-align: right; }

    .ux-kv { margin: 0; }
    .ux-kv > div { display: flex; justify-content: space-between; gap: 10px; padding: 11px 0; border-top: 1px solid var(--ux-line); font: 400 13px var(--mono); }
    .ux-kv dt { color: var(--ux-mut); } .ux-kv dd { margin: 0; font-weight: 700; }
    .ux-radar { display: block; width: 100%; max-width: 360px; margin: 0 auto; height: auto; }
    .ux-radar .rg { fill: none; stroke: var(--ux-line); stroke-width: 1; }
    .ux-radar .sp { stroke: var(--ux-line); stroke-width: 1; }
    .ux-radar .lb { fill: var(--ux-mut); font: 400 10.5px var(--mono); }
    .ux-radar .shape { fill: color-mix(in srgb, var(--ux-green) 14%, transparent); stroke: var(--ux-green); stroke-width: 2.4; stroke-linejoin: round; transition: d .5s cubic-bezier(.22,1,.36,1); }
    .ux-radar .dot { fill: var(--ux-green); }

    /* ============ Dashboard Admin (.adm) ============ */
    .adm {
        --a-stage: #efefeb; --a-card: #ffffff; --a-ink: #17171a; --a-mut: #6d6d66; --a-line: #e4e4de; --a-soft: #f4f4f0;
        --a-lime: #c8f966; --a-vio: #b993ff; --a-acc: #4d6b00; --a-bad: #e5484d;
        padding: 14px; border-radius: 32px; background: var(--a-stage); color: var(--a-ink);
    }
    html[data-theme="dark"] .adm {
        --a-stage: #0d0d0c; --a-card: #171716; --a-ink: #ecece8; --a-mut: #9a9a92; --a-line: #2a2a27; --a-soft: #222220;
        --a-acc: #c8f966; --a-bad: #ff8a80;
    }
    .adm-card { position: relative; min-width: 0; overflow: hidden; padding: 18px; border-radius: 26px; border: 1px solid var(--a-line);
                background: var(--a-card); color: var(--a-ink); animation: uxIn .5s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i, 0) * 45ms); }
    .adm-lime { background: var(--a-lime); border-color: var(--a-lime); color: #14170a; }
    .adm-t { margin: 0 0 12px; font: 600 12.5px var(--mono); color: var(--a-mut); display: flex; justify-content: space-between; align-items: center; gap: 8px; }
    .adm-lime .adm-t { color: rgba(20,23,10,.65); }
    .adm-big { font: 700 clamp(28px, 3.6vw, 44px)/1 var(--mono); letter-spacing: -.04em; font-variant-numeric: tabular-nums; }
    .adm-sub { margin-top: 6px; font: 400 11.5px var(--mono); color: var(--a-mut); }
    .adm-lime .adm-sub { color: rgba(20,23,10,.62); }
    .adm-acc { color: var(--a-acc); }
    .adm-chip { display: inline-block; padding: 2px 9px; border-radius: 999px; background: var(--a-lime); color: #14170a; font: 700 11px var(--mono); }
    .adm-chip.bad { background: var(--a-bad); } .adm-chip.warn { background: #ffd24d; } .adm-chip.mut { background: var(--a-soft); color: var(--a-mut); }
    .adm-pills { display: inline-flex; padding: 3px; gap: 2px; border-radius: 999px; background: var(--a-soft); border: 1px solid var(--a-line); }
    .adm-pills button { min-height: 0; padding: 5px 12px; border: 0; border-radius: 999px; background: transparent; color: var(--a-mut); font: 600 11.5px var(--mono); }
    .adm-pills button.on { background: var(--a-ink); color: var(--a-card); }
    .adm-strip { display: flex; align-items: flex-end; gap: 3px; height: 110px; }
    .adm-strip i { flex: 1; min-width: 0; border-radius: 3px; transform-origin: bottom; animation: bnRise .6s cubic-bezier(.22,1,.36,1) both; }
    @keyframes bnRise { from { transform: scaleY(0); } to { transform: scaleY(1); } }
    .adm-foot { display: flex; justify-content: space-between; margin-top: 8px; font: 500 10.5px var(--mono); color: var(--a-mut); }
    .adm-seg { display: flex; gap: 3px; height: 26px; margin: 10px 0 14px; }
    .adm-seg i { display: block; height: 100%; border-radius: 6px; }
    .adm-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 7px 0; font: 400 12px var(--mono); }
    .adm-row span.k { display: inline-flex; align-items: center; gap: 7px; color: var(--a-mut); }
    .adm-row b { font-weight: 700; }
    .adm-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .adm-kv { margin: 0; font: 400 12px var(--mono); }
    .adm-kv > div { display: flex; justify-content: space-between; padding: 8px 0; border-top: 1px solid var(--a-line); }
    .adm-lime .adm-kv > div { border-top-color: rgba(20,23,10,.18); }
    .adm-kv dt { opacity: .7; } .adm-kv dd { margin: 0; font-weight: 700; }
    .adm-act { list-style: none; margin: 0; padding: 0; }
    .adm-act li { display: flex; align-items: center; gap: 12px; padding: 10px 0; }
    .adm-act .ic { display: grid; place-items: center; width: 36px; height: 36px; border-radius: 12px; background: var(--a-soft); font: 700 13px var(--mono); flex-shrink: 0; }
    .adm-act .tx { flex: 1; min-width: 0; font: 600 13px var(--mono); }
    .adm-act .tx small { display: block; font-weight: 400; font-size: 11px; color: var(--a-mut); }
    .adm-act .rt { text-align: right; font: 700 13px var(--mono); }
    .adm-act .rt small { display: block; font-weight: 700; font-size: 10px; }
    .adm-table th, .adm-table td { padding: 11px 10px 11px 0; border-bottom: 1px solid var(--a-line); background: transparent; border-radius: 0; font: 400 12.5px var(--mono); vertical-align: middle; }
    .adm-table th { color: var(--a-mut); font-weight: 400; white-space: nowrap; }
    .adm-table tr:last-child td { border-bottom: 0; }
    .adm-table code { background: var(--a-soft); color: var(--a-ink); }
    .adm .badge { border: 0; background: var(--a-soft); color: var(--a-ink); }
    .adm .badge.ok { background: var(--a-lime); color: #14170a; } .adm .badge.warn { background: #ffd24d; color: #14170a; }
    .adm .badge.bad { background: var(--a-bad); color: #14170a; } .adm .badge.info { background: var(--a-vio); color: #14170a; }
    .adm input:not([type=checkbox]) { background: var(--a-soft); color: var(--a-ink); border: 1px solid var(--a-line); border-radius: 14px; }
    .adm input:focus-visible { outline: 2px solid var(--a-lime); box-shadow: none; }
    .adm .btn-pill { min-height: 0; padding: 11px 18px; border: 0; border-radius: 999px; background: var(--a-lime); color: #14170a; font: 700 12.5px var(--mono); }
    .adm .btn-pill.dark { background: #14170a; color: var(--a-lime); }
    .adm .btn-pill.ghost { background: var(--a-soft); color: var(--a-ink); border: 1px solid var(--a-line); }
    .adm a.btn-pill { text-decoration: none; }
    .adm a { text-decoration: none !important; }
    .adm .btn-pill:hover { background: var(--a-lime); }
    .adm .btn-pill.dark:hover { background: #14170a; }
    .adm .btn-pill.ghost:hover { background: var(--a-soft); }
    .adm label { color: var(--a-mut); font-family: var(--mono); font-weight: 600; }
    .adm .error { color: var(--a-bad); }

    /* Tumpukan kartu kunci API */
    .adm-stack { position: relative; height: 168px; }
    .adm-kc { position: absolute; left: 0; right: 0; height: 108px; padding: 10px 12px; border: 0; border-radius: 16px; text-align: left; color: #1a1612;
              cursor: pointer; transition: top .45s cubic-bezier(.22,1,.36,1), transform .45s cubic-bezier(.22,1,.36,1), opacity .3s; box-shadow: 0 -6px 18px -10px rgba(0,0,0,.4);
              display: block; min-height: 0; }
    .adm-kc .r1 { display: flex; justify-content: space-between; font: 700 11px var(--mono); }
    .adm-kc .r2 { margin-top: 24px; font: 700 15px var(--mono); letter-spacing: .06em; }
    .adm-kc .r3 { display: flex; justify-content: space-between; margin-top: 6px; font: 400 10px var(--mono); opacity: .75; }
    .adm-detail { margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--a-line); }

    /* Gauge */
    .adm-gauge { display: block; width: 100%; max-width: 230px; margin: 0 auto; height: auto; overflow: visible; }
    .adm-gauge .tr { fill: none; stroke: var(--a-line); stroke-width: 12; stroke-linecap: round; }
    .adm-gauge .va { fill: none; stroke: var(--a-lime); stroke-width: 12; stroke-linecap: round; transition: stroke-dasharray .6s cubic-bezier(.22,1,.36,1); }
    .adm-gauge .nd { stroke: var(--a-ink); stroke-width: 3; stroke-linecap: round; transform-origin: 100px 100px; transition: transform .6s cubic-bezier(.22,1,.36,1); }
    .adm-gauge .hub { fill: var(--a-ink); }

    @media (prefers-reduced-motion: reduce) {
        .ux-card, .adm-card, .adm-strip i { animation: none; }
        .ux-tile, .adm-kc, .adm-gauge .va, .adm-gauge .nd { transition: none; }
    }
</style>
<script>
    /* Angka naik; teks akhir sudah ada di HTML sehingga aman bila JS mati */
    document.addEventListener('DOMContentLoaded', function () {
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.querySelectorAll('[data-count]').forEach(function (el) {
            var to = parseFloat(el.getAttribute('data-count')) || 0, dec = parseInt(el.getAttribute('data-dec') || '0', 10);
            var fmt = function (v) { return v.toLocaleString('id-ID', { minimumFractionDigits: dec, maximumFractionDigits: dec }); };
            if (reduce || to === 0) { el.textContent = fmt(to); return; }
            var t0 = performance.now(), dur = 900;
            (function step(now) {
                var p = Math.min(1, (now - t0) / dur);
                el.textContent = fmt(to * (1 - Math.pow(1 - p, 3)));
                if (p < 1) { requestAnimationFrame(step); }
            })(t0);
        });
    });
</script>