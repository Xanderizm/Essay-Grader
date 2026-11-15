/* Transisi tema "cipratan tinta": tema baru melebar dari tombol dengan tepi organik dan butir tinta.
   Durasi dan kurva jarak tempuh diukur dari GIF referensi (sekitar 2,4 detik). */
(function () {
    'use strict';
    if (window.egInkTransition) { return; }

    var TAU = Math.PI * 2, DURATION = 2400, STEPS = 36, N = 100, busy = false;

    function rng(seed) {
        var s = seed >>> 0;
        return function () {
            s = (s + 0x6D2B79F5) >>> 0;
            var t = s;
            t = Math.imul(t ^ (t >>> 15), t | 1);
            t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
            return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
        };
    }
    function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }
    function smooth(a, b, x) { var t = clamp((x - a) / (b - a), 0, 1); return t * t * (3 - 2 * t); }
    function r1(n) { return Math.round(n * 10) / 10; }

    function path(pts) {
        var s = 'M' + r1(pts[0][0]) + ' ' + r1(pts[0][1]);
        for (var i = 1; i < pts.length; i++) { s += 'L' + r1(pts[i][0]) + ' ' + r1(pts[i][1]); }
        return s + 'Z';
    }
    function ring(cx, cy, r, jit) {
        var pts = [];
        for (var i = 0; i < jit.length; i++) {
            var a = i / jit.length * TAU;
            pts.push([cx + Math.cos(a) * r * jit[i], cy + Math.sin(a) * r * jit[i]]);
        }
        return pts;
    }

    function frames(ox, oy, w, h) {
        var rand = rng(Date.now() + Math.round(ox * 31 + oy * 17));
        var far = Math.max(Math.hypot(ox, oy), Math.hypot(w - ox, oy), Math.hypot(ox, h - oy), Math.hypot(w - ox, h - oy));
        var rMin = far * .16, rMax = far * 1.12;   // radius akhir: lingkaran penuh pasti menutup layar
        var i, j;

        function jitter(n) { var a = []; for (var k = 0; k < n; k++) { a.push(.55 + rand() * .8); } return a; }

        var waves = [[2, .09], [3, .08], [5, .07], [8, .055], [13, .045], [21, .03]].map(function (c) {
            return { k: c[0], a: c[1], p: rand() * TAU, v: (rand() - .5) * 4 };
        });
        var spikes = [];
        for (i = 0; i < 8; i++) { spikes.push({ at: rand() * TAU, w: .09 + rand() * .15, h: .1 + rand() * .2, v: (rand() - .5) * 1.4 }); }
        var drops = [];   // butir tinta di depan tepi
        for (i = 0; i < 16; i++) { drops.push({ a: rand() * TAU, gap: 40 + rand() * 170, r: 4 + rand() * 13, t0: rand() * .55, jit: jitter(7) }); }
        var holes = [];   // sisa tema lama yang tertinggal lalu mengecil
        for (i = 0; i < 10; i++) { holes.push({ a: rand() * TAU, d: far * (.12 + rand() * .62), r: 14 + rand() * 34, jit: jitter(9) }); }

        function edge(th, t, amp) {
            var f = 0, k, d;
            for (k = 0; k < waves.length; k++) { f += waves[k].a * Math.sin(waves[k].k * th + waves[k].p + waves[k].v * t); }
            for (k = 0; k < spikes.length; k++) {
                d = ((th - spikes[k].at - spikes[k].v * t) % TAU + TAU) % TAU;
                if (d > Math.PI) { d = TAU - d; }
                if (d < spikes[k].w) { var c = Math.cos(d / spikes[k].w * Math.PI / 2); f += spikes[k].h * c * c; }
            }
            return 1 + amp * f;
        }

        var out = [];
        for (j = 0; j <= STEPS; j++) {
            var t = j / STEPS;
            // Kurva jarak tempuh hasil ukur GIF: cepat di awal, melambat di akhir
            var R = rMin + (rMax - rMin) * (1 - Math.pow(1 - t, 1.3));
            var amp = 1 - smooth(.55, 1, t);   // tepi bergerigi mereda agar layar tertutup bersih
            var str = '', pts = [];

            for (i = 0; i < N; i++) {
                var th = i / N * TAU, r = R * edge(th, t, amp);
                pts.push([ox + Math.cos(th) * r, oy + Math.sin(th) * r]);
            }
            str += path(pts);

            drops.forEach(function (dr) {
                var ph = clamp((t - dr.t0) / .3, 0, 1), rad = dr.r * Math.sin(ph * Math.PI);
                var dist = R * edge(dr.a, t, amp) + dr.gap * (1 - ph);
                str += path(ring(ox + Math.cos(dr.a) * dist, oy + Math.sin(dr.a) * dist, rad, dr.jit));
            });
            holes.forEach(function (ho) {
                var front = R * edge(ho.a, t, amp);
                var rad = ho.r * smooth(0, 1, (front - ho.d) / (far * .1)) * (1 - smooth(.55, 1, t));
                str += path(ring(ox + Math.cos(ho.a) * ho.d, oy + Math.sin(ho.a) * ho.d, rad, ho.jit));
            });

            out.push({ clipPath: "path(evenodd, '" + str + "')" });
        }
        return out;
    }

    /** @return {boolean} true = transisi dijalankan (atau klik diabaikan karena masih berjalan); false = pakai cadangan */
    window.egInkTransition = function (ox, oy, apply) {
        if (busy) { return true; }
        if (typeof document.startViewTransition !== 'function'
            || window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return false; }

        var root = document.documentElement, vt;
        busy = true;
        root.classList.add('theme-ink');
        function done() { root.classList.remove('theme-ink'); busy = false; }

        try { vt = document.startViewTransition(apply); } catch (e) { done(); return false; }

        vt.ready.then(function () {
            root.animate(frames(ox, oy, window.innerWidth, window.innerHeight), {
                duration: DURATION, easing: 'linear', fill: 'forwards', pseudoElement: '::view-transition-new(root)'
            });
        }).catch(function () {});
        vt.finished.then(done, done);

        return true;
    };
})();