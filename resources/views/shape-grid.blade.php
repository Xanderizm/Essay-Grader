{{--
    Latar ShapeGrid (port vanilla JS dari React Bits, tanpa React).
    Dipakai di: Penilaian saya, Masuk, Daftar. Cukup: @include('shape-grid') di awal @section('content').

    - Warna dibaca dari CSS variable proyek: garis = --ink, sorot kursor = --lime. Ikut berubah saat mode terang/gelap.
    - Kanvas menempel (sticky) setinggi layar di dalam #isi, sama seperti .page-bg di landing, jadi murah dirender.
    - Tepi atas kanvas dipudarkan dengan mask-image agar tidak menabrak topbar; tepi bawah ikut dipudarkan.
    - Kontainer pointer-events: none, jadi tidak menghalangi klik; kursor dibaca lewat listener window.
    - Atur lewat atribut data- pada <div data-sg>: data-size (px sel), data-speed (px/detik).
    - Mode kotak: @include('shape-grid', ['scope' => 'box']) membatasi efek HANYA di dalam kotak putih (shell) halaman;
      area di luar kotak tidak tersentuh. Kotak dicari otomatis (induk terdekat yang punya warna latar). Bila salah tebak,
      tunjuk manual: @include('shape-grid', ['scope' => 'box', 'box' => '.nama-kelas-kotak']).
--}}
<div class="sg-bg" data-sg data-size="38" data-speed="16" data-scope="{{ $scope ?? 'page' }}" data-box="{{ $box ?? '' }}" aria-hidden="true"><canvas></canvas></div>

<style>
    #isi { position: relative; isolation: isolate; }
    .sg-bg { --sg-fade: 170px; position: absolute; inset: 0 -36px; z-index: -1; pointer-events: none; }
    .sg-bg canvas { position: sticky; top: 0; display: block; width: 100%; height: 100vh; height: 100svh;
        -webkit-mask-image: linear-gradient(to bottom, transparent 0, rgba(0, 0, 0, .3) calc(var(--sg-fade) * .55), #000 var(--sg-fade), #000 calc(100% - 90px), transparent 100%);
                mask-image: linear-gradient(to bottom, transparent 0, rgba(0, 0, 0, .3) calc(var(--sg-fade) * .55), #000 var(--sg-fade), #000 calc(100% - 90px), transparent 100%); }
    /* Cadangan bila layout tidak punya pembungkus #isi */
    .sg-bg.is-fixed { position: fixed; inset: 0; isolation: isolate; }
    .sg-bg.is-fixed canvas { position: static; height: 100%; }
    @media (max-width: 820px) {
        .sg-bg { inset: 0 -16px; --sg-fade: 130px; }
    }
    /* Mode kotak: mengisi tepat kotak putih, dipotong mengikuti sudut kotak (clip-path diatur lewat JS) */
    .sg-bg.is-box { position: absolute; inset: 0; z-index: -1; }
    .sg-bg.is-box.is-tall canvas { position: absolute; top: 0; left: 0; }
</style>

<script>
(function () {
    'use strict';
    var host = document.querySelector('[data-sg]');
    if (!host) { return; }
    var canvas = host.querySelector('canvas');
    var ctx = canvas.getContext('2d');
    if (!ctx) { return; }
    var boxMode = false, tall = false;

    // Cari kotak putih: pilihan manual (data-box) atau induk terdekat yang punya warna latar (bukan html/body)
    function findBox() {
        var sel = host.getAttribute('data-box');
        if (sel) { var m = document.querySelector(sel); if (m) { return m; } }
        var el = host.parentElement;
        while (el && el !== document.body && el !== document.documentElement) {
            var bg = getComputedStyle(el).backgroundColor;
            if (bg && bg !== 'transparent' && !/^rgba\(\s*0,\s*0,\s*0,\s*0\s*\)$/.test(bg)) { return el; }
            el = el.parentElement;
        }
        return null;
    }
    function mountInBox(box) {
        var cs = getComputedStyle(box);
        if (cs.position === 'static') { box.style.position = 'relative'; }
        box.style.isolation = 'isolate';                       // agar z-index -1 tampil DI ATAS warna latar kotak
        host.classList.add('is-box');
        host.style.clipPath = 'inset(0 round ' + [cs.borderTopLeftRadius, cs.borderTopRightRadius, cs.borderBottomRightRadius, cs.borderBottomLeftRadius].join(' ') + ')';
        // Bila kotak memotong isinya (overflow bukan visible/clip), sticky tidak bisa dipakai: kanvas dibuat setinggi kotak
        tall = (cs.overflowY !== 'visible' && cs.overflowY !== 'clip');
        if (tall) { host.classList.add('is-tall'); }
        box.insertBefore(host, box.firstChild);
        boxMode = true;
    }
    if (host.getAttribute('data-scope') === 'box') {
        var theBox = findBox();
        if (theBox) { mountInBox(theBox); }
    }
    if (!boxMode && !document.getElementById('isi')) { host.classList.add('is-fixed'); }

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var small = window.matchMedia('(max-width: 820px)').matches;
    var S = (parseFloat(host.getAttribute('data-size')) || 38) * (small ? .8 : 1);
    var SPEED = parseFloat(host.getAttribute('data-speed')) || 16;
    var RADIUS = 150, TRAIL = 720, MAX_TRAIL = 40;

    var W = 1, H = 1, dpr = 1, ox = 0, oy = 0;
    var mx = -9999, my = -9999, inside = false, lastCell = '';
    var trail = [];
    var ink = [27, 27, 27], lime = [196, 245, 93], dark = false;

    function css(name, fb) {
        var v = getComputedStyle(document.documentElement).getPropertyValue(name);
        return (v && v.trim()) || fb;
    }
    var probe = document.createElement('canvas'); probe.width = probe.height = 1;
    var pctx = probe.getContext('2d', { willReadFrequently: true });
    function rgb(value, fb) {
        try {
            pctx.clearRect(0, 0, 1, 1); pctx.fillStyle = '#000'; pctx.fillStyle = value; pctx.fillRect(0, 0, 1, 1);
            var d = pctx.getImageData(0, 0, 1, 1).data; return [d[0], d[1], d[2]];
        } catch (e) { return fb; }
    }
    function readPalette() {
        ink = rgb(css('--ink', '#1b1b1b'), ink);
        lime = rgb(css('--lime', '#c4f55d'), lime);
        dark = document.documentElement.getAttribute('data-theme') === 'dark';
    }
    var rgba = function (c, a) { return 'rgba(' + c[0] + ',' + c[1] + ',' + c[2] + ',' + a.toFixed(3) + ')'; };

    function resize() {
        if (boxMode) {
            var hh = host.clientHeight;
            canvas.style.height = (tall ? hh : Math.min(window.innerHeight, hh)) + 'px';
        }
        W = canvas.clientWidth; H = canvas.clientHeight;
        if (!W || !H) { return; }
        dpr = Math.min(window.devicePixelRatio || 1, 1.5);
        canvas.width = Math.round(W * dpr); canvas.height = Math.round(H * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        kick();
    }

    function draw(now) {
        ctx.clearRect(0, 0, W, H);

        // Garis dasar: satu path untuk seluruh kisi
        var x0 = -(((ox % S) + S) % S), y0 = -(((oy % S) + S) % S);
        ctx.lineWidth = 1;
        ctx.strokeStyle = rgba(ink, dark ? .09 : .12);
        ctx.beginPath();
        for (var x = x0; x <= W; x += S) { ctx.moveTo(Math.round(x) + .5, 0); ctx.lineTo(Math.round(x) + .5, H); }
        for (var y = y0; y <= H; y += S) { ctx.moveTo(0, Math.round(y) + .5); ctx.lineTo(W, Math.round(y) + .5); }
        ctx.stroke();

        // Jejak kursor: sel yang dilewati terisi lime lalu memudar (ikut bergeser bersama kisi)
        for (var i = trail.length - 1; i >= 0; i--) {
            var age = now - trail[i].t;
            if (age > TRAIL) { trail.splice(i, 1); continue; }
            var k = 1 - age / TRAIL, a = k * k;
            var cx = trail[i].c * S - ox, cy = trail[i].r * S - oy;
            ctx.fillStyle = rgba(lime, .9 * a);
            ctx.fillRect(cx + 1, cy + 1, S - 1, S - 1);
            ctx.strokeStyle = rgba(ink, .5 * a);
            ctx.strokeRect(cx + .5, cy + .5, S, S);
        }

        // Sorotan halus di sekitar kursor: garis sel mengeras mendekati kursor
        if (inside) {
            var c0 = Math.floor((mx - RADIUS + ox) / S), c1 = Math.floor((mx + RADIUS + ox) / S);
            var r0 = Math.floor((my - RADIUS + oy) / S), r1 = Math.floor((my + RADIUS + oy) / S);
            for (var r = r0; r <= r1; r++) {
                for (var c = c0; c <= c1; c++) {
                    var px = c * S - ox, py = r * S - oy;
                    var d = Math.hypot(px + S / 2 - mx, py + S / 2 - my);
                    if (d >= RADIUS) { continue; }
                    var t = 1 - d / RADIUS; t *= t;
                    ctx.fillStyle = rgba(lime, t * (dark ? .1 : .16));
                    ctx.fillRect(px + 1, py + 1, S - 1, S - 1);
                    ctx.strokeStyle = rgba(ink, t * (dark ? .38 : .42));
                    ctx.strokeRect(px + .5, py + .5, S, S);
                }
            }
        }
    }

    var raf = 0, last = 0, visible = true;
    function tick(now) {
        raf = 0;
        var dt = Math.min(.05, (now - last) / 1000); last = now;
        if (!reduce) { ox += SPEED * dt * .8; oy += SPEED * dt * .8; }
        draw(now);
        if (visible && !document.hidden && (!reduce || trail.length)) { raf = requestAnimationFrame(tick); }
    }
    function kick() {
        if (raf || !visible || document.hidden) { return; }
        last = performance.now(); raf = requestAnimationFrame(tick);
    }

    function onMove(e) {
        var r = canvas.getBoundingClientRect();
        mx = e.clientX - r.left; my = e.clientY - r.top;
        inside = mx >= 0 && my >= 0 && mx <= r.width && my <= r.height;
        if (inside) {
            var c = Math.floor((mx + ox) / S), row = Math.floor((my + oy) / S), key = c + ':' + row;
            if (key !== lastCell) {
                lastCell = key; trail.push({ c: c, r: row, t: performance.now() });
                if (trail.length > MAX_TRAIL) { trail.shift(); }
            }
        }
        kick();
    }
    window.addEventListener('pointermove', onMove, { passive: true });
    window.addEventListener('pointerdown', onMove, { passive: true });
    window.addEventListener('pointerup', function (e) { if (e.pointerType === 'touch') { inside = false; kick(); } }, { passive: true });
    document.documentElement.addEventListener('mouseleave', function () { inside = false; lastCell = ''; kick(); });
    window.addEventListener('blur', function () { inside = false; kick(); });

    if ('IntersectionObserver' in window) {
        new IntersectionObserver(function (en) { visible = en[0].isIntersecting; if (visible) { kick(); } }).observe(canvas);
    }
    document.addEventListener('visibilitychange', function () { if (!document.hidden) { kick(); } });
    window.addEventListener('resize', resize);
    if (boxMode && 'ResizeObserver' in window) { new ResizeObserver(resize).observe(host); }   // isi dashboard bisa bertambah tinggi
    new MutationObserver(function () { setTimeout(function () { readPalette(); kick(); }, 60); })
        .observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

    readPalette();
    resize();
    draw(performance.now());
    kick();
})();
</script>
