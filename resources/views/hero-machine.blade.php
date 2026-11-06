{{--
    Mesin 3D hero: empat stasiun = empat langkah "Cara kerja".
    Vanilla JS + three.js (tanpa React/TS). Warna dibaca langsung dari CSS variable proyek
    (--ink, --paper, --mist, --lime, --back) dan ikut berubah saat mode terang/gelap.
    Gambar SVG lama tetap ada sebagai fallback (tanpa JS / tanpa WebGL / reduced-motion tetap diam satu frame).
--}}
@php
    $machineSteps = [
        ['Hubungkan Google', 'Izin baca saja, lalu tempel link Form.'],
        ['Form dibaca otomatis', 'Kolom identitas dipisah dari pertanyaan esai.'],
        ['Isi rubrik', 'Atur rubrik per pertanyaan (opsional).'],
        ['Nilai dan unduh', 'AI menilai, hasil siap diunduh ke Excel.'],
    ];
@endphp

<div class="hm" id="hm" data-hm>
    <div class="hm-fallback flex items-center justify-center h-full">
        {{-- Opsional: Spinner atau kotak kosong --}}
    </div>

    <div class="hm-stage" data-hm-stage hidden>
        <canvas class="hm-canvas" data-hm-canvas role="img"
            aria-label="Ilustrasi 3D mesin empat tahap: hubungkan Google, form dibaca otomatis, isi rubrik, nilai dan unduh ke Excel. Seret untuk memutar."></canvas>
        <div class="hm-labels" data-hm-labels aria-hidden="true">
            @foreach($machineSteps as $i => $s)
                <div class="hm-label" data-hm-label="{{ $i }}">
                    <span class="hm-no">{{ $i + 1 }}</span>
                    <span class="hm-card"><strong>{{ $s[0] }}</strong><small>{{ $s[1] }}</small></span>
                </div>
            @endforeach
        </div>
    </div>

    <ol class="hm-legend" aria-label="Tahapan cara kerja">
        @foreach($machineSteps as $i => $s)
            <li>
                <button type="button" class="hm-chip" data-hm-focus="{{ $i }}" aria-pressed="false">
                    <span class="hm-chip-no">{{ $i + 1 }}</span>{{ $s[0] }}
                </button>
            </li>
        @endforeach
    </ol>
</div>

<style>
    .hero:has(.hm) { grid-template-columns: minmax(0, .95fr) minmax(0, 1.15fr); }
    .hm { position: relative; min-width: 0; }
    .hm-stage { position: relative; height: clamp(300px, 42vw, 470px); touch-action: pan-y; cursor: grab; }
    .hm-stage.is-drag { cursor: grabbing; }
    .hm-canvas { display: block; width: 100%; height: 100%; outline-offset: 4px; }
    .hm.is-ready .hm-fallback { display: none; }
    .hm-fallback .art { width: 100%; height: auto; max-height: 400px; object-fit: contain; animation: art-float 7s ease-in-out infinite; }

    .hm-labels { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
    .hm-label { position: absolute; left: 0; top: 0; display: flex; flex-direction: column; align-items: center; gap: 6px;
        opacity: 0; transition: opacity .3s ease; will-change: transform; }
    .hm-label.is-on { opacity: 1; }
    .hm-no { display: inline-grid; place-items: center; width: 28px; height: 28px; border-radius: 50%; background: var(--lime); color: #1b1b1b;
        border: 1.5px solid var(--ink); font: 700 12.5px var(--display); transition: transform .25s ease, box-shadow .25s ease; }
    .hm-card { display: none; flex-direction: column; gap: 2px; width: max-content; max-width: 190px; padding: 8px 11px; border-radius: 12px;
        background: var(--paper); color: var(--ink); border: 1.5px solid var(--ink); box-shadow: 3px 3px 0 var(--lime); text-align: left; }
    .hm-card strong { font: 700 12.5px var(--display); }
    .hm-card small { font: 400 11px/1.35 var(--mono); color: var(--muted); }
    .hm-label.is-hot { z-index: 3; }
    .hm-label.is-hot .hm-card { display: flex; }
    .hm-label.is-hot .hm-no { transform: scale(1.12); box-shadow: 0 0 0 4px color-mix(in srgb, var(--lime) 45%, transparent); }

    .hm-legend { display: flex; flex-wrap: wrap; gap: 8px; margin: 10px 0 0; padding: 0; list-style: none; }
    .hm-chip { display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px 6px 6px; border-radius: 999px; cursor: pointer;
        background: var(--paper); color: var(--ink); border: 1.5px solid color-mix(in srgb, var(--ink) 22%, transparent);
        font: 400 12px var(--mono); transition: border-color .2s, background .2s, box-shadow .2s; }
    .hm-chip:hover, .hm-chip:focus-visible, .hm-chip[aria-pressed="true"] { border-color: var(--ink); box-shadow: 2px 2px 0 var(--lime); }
    .hm-chip[aria-pressed="true"] { background: var(--lime); color: #1b1b1b; }
    .hm-chip-no { display: inline-grid; place-items: center; width: 20px; height: 20px; border-radius: 50%; background: var(--lime); color: #1b1b1b;
        border: 1.5px solid #1b1b1b; font: 700 10.5px var(--display); }

    @media (max-width: 820px) {
        .hero:has(.hm) { grid-template-columns: 1fr; }
        .hm-stage { height: clamp(260px, 70vw, 380px); }
        .hm-card { max-width: 150px; }
    }
    @media (prefers-reduced-motion: reduce) {
        .hm-fallback .art { animation: none; }
        .hm-label { transition: none; }
    }
</style>

<script>
(function () {
    'use strict';
    var host = document.querySelector('[data-hm]');
    if (!host) { return; }

    var THREE_URL = 'https://cdn.jsdelivr.net/npm/three@0.160.1/build/three.module.js';
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var stage = host.querySelector('[data-hm-stage]');
    var canvas = host.querySelector('[data-hm-canvas]');
    var labelEls = Array.prototype.slice.call(host.querySelectorAll('[data-hm-label]'));
    var chips = Array.prototype.slice.call(host.querySelectorAll('[data-hm-focus]'));

    function webglOk() {
        try { var c = document.createElement('canvas'); return !!(window.WebGLRenderingContext && (c.getContext('webgl2') || c.getContext('webgl'))); }
        catch (e) { return false; }
    }
    if (!webglOk()) { return; }

    // Muat three hanya saat hero terlihat, supaya halaman tetap ringan.
    var started = false;
    function boot() {
        if (started) { return; }
        started = true;
        import(THREE_URL).then(function (THREE) { build(THREE); }).catch(function () { /* tetap pakai gambar fallback */ });
    }
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (en) { if (en[0].isIntersecting) { io.disconnect(); boot(); } }, { rootMargin: '200px' });
        io.observe(host);
    } else { boot(); }

    function build(THREE) {
        var css = function (n, fb) { return (getComputedStyle(document.documentElement).getPropertyValue(n) || fb).trim() || fb; };
        var pal = {};
        function readPalette() {
            pal.ink = css('--ink', '#1b1b1b'); pal.paper = css('--paper', '#ffffff'); pal.mist = css('--mist', '#f1f1ef');
            pal.lime = css('--lime', '#c4f55d'); pal.back = css('--back', '#d8d8d4');
        }
        readPalette();

        var renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true, powerPreference: 'low-power' });
        renderer.setClearColor(0x000000, 0);
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        var scene = new THREE.Scene();
        var camera = new THREE.PerspectiveCamera(28, 1, 0.1, 100);

        scene.add(new THREE.HemisphereLight(0xffffff, 0x9a9a94, 1.05));
        var sun = new THREE.DirectionalLight(0xffffff, 1.9);
        sun.position.set(-5, 11, 7);
        sun.castShadow = true;
        sun.shadow.mapSize.set(1024, 1024);
        sun.shadow.camera.left = -9; sun.shadow.camera.right = 9; sun.shadow.camera.top = 9; sun.shadow.camera.bottom = -9;
        sun.shadow.bias = -0.0006; sun.shadow.normalBias = 0.03;
        scene.add(sun);

        /* ---------- bahan: satu set, warna diperbarui saat tema berganti ---------- */
        var mats = {};
        function mat(key, role, extra) {
            var m = new THREE.MeshStandardMaterial(Object.assign({ roughness: .55, metalness: .05 }, extra || {}));
            m.userData.role = role; mats[key] = m; return m;
        }
        mat('plate', 'mist'); mat('slab', 'back'); mat('body', 'paper'); mat('dark', 'ink', { roughness: .4 });
        mat('mist', 'mist'); mat('belt', 'ink', { roughness: .8 }); mat('back', 'back');
        mat('lime', 'lime', { roughness: .35 }); mat('ledGlow', 'lime', { roughness: .3, emissiveIntensity: .55 }); mat('coreGlow', 'lime', { roughness: .3, emissiveIntensity: .55 });
        var scanMat = new THREE.MeshBasicMaterial({ transparent: true, opacity: .55, depthWrite: false, side: THREE.DoubleSide }); scanMat.userData.role = 'lime'; mats.scan = scanMat;
        function paint() {
            Object.keys(mats).forEach(function (k) {
                var m = mats[k]; var c = pal[m.userData.role]; m.color.set(c);
                if (k.indexOf('Glow') > 0) { m.emissive.set(pal.lime); }
            });
        }
        paint();

        /* ---------- helpers geometri ---------- */
        function rbox(w, h, d, r, b) {
            b = b == null ? .05 : b; r = Math.min(r == null ? .16 : r, (w - 2 * b) / 2, (d - 2 * b) / 2);
            var x = -w / 2 + b, y = -d / 2 + b, ww = w - 2 * b, dd = d - 2 * b, s = new THREE.Shape();
            s.moveTo(x + r, y); s.lineTo(x + ww - r, y); s.quadraticCurveTo(x + ww, y, x + ww, y + r);
            s.lineTo(x + ww, y + dd - r); s.quadraticCurveTo(x + ww, y + dd, x + ww - r, y + dd);
            s.lineTo(x + r, y + dd); s.quadraticCurveTo(x, y + dd, x, y + dd - r);
            s.lineTo(x, y + r); s.quadraticCurveTo(x, y, x + r, y);
            var g = new THREE.ExtrudeGeometry(s, { depth: Math.max(.001, h - 2 * b), bevelEnabled: true, bevelThickness: b, bevelSize: b, bevelSegments: 2, curveSegments: 6 });
            g.rotateX(-Math.PI / 2); g.translate(0, b, 0); return g;
        }
        function add(parent, geo, m, x, y, z, shadow) {
            var o = new THREE.Mesh(geo, m); o.position.set(x || 0, y || 0, z || 0);
            if (shadow !== false) { o.castShadow = true; o.receiveShadow = true; }
            parent.add(o); return o;
        }
        function box(w, h, d, m, x, y, z, r) { return add(g_, rbox(w, h, d, r), m, x, y, z); }
        var g_ = scene;

        /* ---------- alas mesin ---------- */
        var machine = new THREE.Group(); scene.add(machine);
        var PX0 = -5.0, PX1 = 5.4, PZ = 3.6;
        g_ = machine;
        add(machine, rbox(PX1 - PX0 + .6, .22, PZ * 2 + .6, .5), mats.slab, (PX0 + PX1) / 2, -.22, 0);
        add(machine, rbox(PX1 - PX0, .3, PZ * 2, .45), mats.plate, (PX0 + PX1) / 2, 0, 0);
        var ground = new THREE.Mesh(new THREE.PlaneGeometry(40, 40), new THREE.ShadowMaterial({ opacity: .16 }));
        ground.rotation.x = -Math.PI / 2; ground.position.y = -.231; ground.receiveShadow = true; scene.add(ground);

        /* ---------- jalur sabuk: legs A (belakang, ke kanan) -> hairpin -> C (depan, ke kiri) ---------- */
        var ZA = -2.4, ZC = 2.4, XL = -4.4, XR = 2.2, R = 2.4, BY = .32;
        var pts = [], i;
        for (i = 0; i <= 60; i++) { pts.push(new THREE.Vector3(XL + (XR - XL) * i / 60, BY, ZA)); }
        for (i = 1; i < 48; i++) { var a = -Math.PI / 2 + Math.PI * i / 48; pts.push(new THREE.Vector3(XR + Math.cos(a) * R, BY, Math.sin(a) * R)); }
        for (i = 0; i <= 60; i++) { pts.push(new THREE.Vector3(XR - (XR - XL) * i / 60, BY, ZC)); }
        var cum = [0]; for (i = 1; i < pts.length; i++) { cum.push(cum[i - 1] + pts[i].distanceTo(pts[i - 1])); }
        var L = cum[cum.length - 1];
        var tmpP = new THREE.Vector3(), tmpT = new THREE.Vector3();
        function at(u) {
            var d = Math.min(Math.max(u, 0), 1) * L, lo = 0, hi = cum.length - 1;
            while (hi - lo > 1) { var mid = (lo + hi) >> 1; if (cum[mid] <= d) { lo = mid; } else { hi = mid; } }
            var f = (d - cum[lo]) / Math.max(1e-6, cum[hi] - cum[lo]);
            tmpP.copy(pts[lo]).lerp(pts[hi], f); tmpT.copy(pts[hi]).sub(pts[lo]).normalize();
            return { p: tmpP, t: tmpT };
        }
        function uOf(x, z) { var best = 0, bd = 1e9; for (var k = 0; k < pts.length; k++) { var dx = pts[k].x - x, dz = pts[k].z - z, dd = dx * dx + dz * dz; if (dd < bd) { bd = dd; best = k; } } return cum[best] / L; }

        // pita sabuk (ribbon) dengan UV sepanjang jalur agar garis-garis bisa bergerak
        var BW = .85, pos = [], uv = [], idx = [];
        for (i = 0; i < pts.length; i++) {
            var tn = (pts[Math.min(i + 1, pts.length - 1)].clone().sub(pts[Math.max(i - 1, 0)])).normalize();
            var nx = -tn.z, nz = tn.x;
            pos.push(pts[i].x + nx * BW / 2, BY, pts[i].z + nz * BW / 2, pts[i].x - nx * BW / 2, BY, pts[i].z - nz * BW / 2);
            uv.push(cum[i] / 1.1, 0, cum[i] / 1.1, 1);
            if (i < pts.length - 1) { var o = i * 2; idx.push(o, o + 1, o + 2, o + 1, o + 3, o + 2); }
        }
        var bg = new THREE.BufferGeometry();
        bg.setAttribute('position', new THREE.Float32BufferAttribute(pos, 3)); bg.setAttribute('uv', new THREE.Float32BufferAttribute(uv, 2));
        bg.setIndex(idx); bg.computeVertexNormals();
        var tc = document.createElement('canvas'); tc.width = 64; tc.height = 16;
        var tx = tc.getContext('2d'); tx.fillStyle = '#8c8c8c'; tx.fillRect(0, 0, 64, 16); tx.fillStyle = '#ffffff'; tx.fillRect(0, 0, 6, 16);
        var beltTex = new THREE.CanvasTexture(tc); beltTex.wrapS = THREE.RepeatWrapping; beltTex.colorSpace = THREE.SRGBColorSpace;
        mats.belt.map = beltTex;
        var belt = new THREE.Mesh(bg, mats.belt); belt.receiveShadow = true; machine.add(belt);
        // rel tepi sabuk
        [-1, 1].forEach(function (sd) {
            var rp = []; for (var k = 0; k < pts.length; k += 2) {
                var t2 = (pts[Math.min(k + 1, pts.length - 1)].clone().sub(pts[Math.max(k - 1, 0)])).normalize();
                rp.push(new THREE.Vector3(pts[k].x - t2.z * sd * (BW / 2 + .06), BY + .05, pts[k].z + t2.x * sd * (BW / 2 + .06)));
            }
            var tube = new THREE.Mesh(new THREE.TubeGeometry(new THREE.CatmullRomCurve3(rp), 220, .045, 6, false), mats.mist);
            tube.castShadow = true; machine.add(tube);
        });

        /* ---------- stasiun ---------- */
        var STX = [-2.4, 1.0, 1.0, -2.4], STZ = [ZA, ZA, ZC, ZC];
        var stations = [], uSt = [];
        for (i = 0; i < 4; i++) { var grp = new THREE.Group(); grp.position.set(STX[i], 0.15, STZ[i]); machine.add(grp); stations.push(grp); uSt.push(uOf(STX[i], STZ[i])); }

        // --- 1 Hubungkan Google: dokumen Form di belakang sabuk + colokan izin-baca ---
        var s1 = stations[0], s1a = {};
        g_ = s1;
        box(1.9, .16, 1.3, mats.dark, 0, 0, -1.55, .12);                       // alas dokumen
        var doc = new THREE.Group(); doc.position.set(0, .16, -1.55); doc.rotation.x = -.12; s1.add(doc);
        g_ = doc;
        box(1.6, 1.9, .14, mats.body, 0, 0, 0, .1);
        box(1.6, .42, .16, mats.lime, 0, 1.48, .0, .1);
        for (i = 0; i < 3; i++) {
            box(.2, .02, .2, mats.dark, -.5, .2 + (2 - i) * .0, 0.0, .04).position.set(-.5, 1.0 - i * .38, .09);
            box(.7, .02, .08, mats.mist, .1, 0, 0, .03).position.set(.1, 1.0 - i * .38, .09);
        }
        g_ = s1;
        s1a.plug = new THREE.Group(); s1a.plug.position.set(-1.45, .5, -1.35); s1.add(s1a.plug);
        g_ = s1a.plug;
        box(.55, .45, .5, mats.dark, 0, 0, 0, .1);
        add(s1a.plug, new THREE.CylinderGeometry(.06, .06, .35, 10), mats.mist, .3, .08, -.1).rotation.z = Math.PI / 2;
        add(s1a.plug, new THREE.CylinderGeometry(.06, .06, .35, 10), mats.mist, .3, -.08, .1).rotation.z = Math.PI / 2;
        g_ = s1;
        s1a.led = add(s1, new THREE.SphereGeometry(.09, 12, 10), mats.ledGlow, -.55, .75, -1.38 + .05, false);
        box(.5, .28, .5, mats.dark, -.7, 0, -1.35, .08).position.set(-.7, .16, -1.35); // soket

        // --- 2 Form dibaca otomatis: gerbang pemindai + laci identitas ---
        var s2 = stations[1], s2a = {};
        g_ = s2;
        [-.78, .78].forEach(function (z) { box(.34, 1.45, .34, mats.dark, 0, .0, z, .08); });
        box(.5, .3, 2.1, mats.body, 0, 1.45, 0, .1);
        s2a.laser = add(s2, new THREE.BoxGeometry(.05, .04, 1.45), mats.lime, 0, .5, 0, false);
        s2a.beam = add(s2, new THREE.PlaneGeometry(1.45, .9), mats.scan, 0, .95, 0, false); s2a.beam.rotation.y = Math.PI / 2; s2a.beam.position.y = .85;
        // laci/tempat sampah identitas di belakang
        box(1.3, .5, .8, mats.dark, 0, 0, -1.7, .08);
        box(1.1, .1, .6, mats.mist, 0, .5, -1.7, .05).position.y = .45;
        s2a.chip = add(s2, rbox(.38, .06, .26, .04), mats.mist, 0, .4, 0); s2a.chip.visible = false;
        s2a.bin = new THREE.Vector3(0, .62, -1.7);

        // --- 3 Isi rubrik: konsol miring dengan 3 slider (Konten, Logika, Struktur) ---
        var s3 = stations[2], s3a = { bars: [], knobs: [] };
        g_ = s3;
        box(2.2, .5, 1.1, mats.dark, 0, 0, 1.7, .12);
        var panel = new THREE.Group(); panel.position.set(0, .5, 1.7); panel.rotation.x = -.5; s3.add(panel);
        g_ = panel;
        box(2.0, .09, .95, mats.body, 0, 0, 0, .08);
        for (i = 0; i < 3; i++) {
            var zz = -.3 + i * .3;
            box(1.5, .03, .1, mats.mist, .1, .1, zz, .03);
            var fill = add(panel, new THREE.BoxGeometry(1, .05, .1), mats.lime, 0, .12, zz, false);
            var knob = add(panel, new THREE.CylinderGeometry(.1, .1, .1, 14), mats.dark, 0, .15, zz);
            s3a.bars.push(fill); s3a.knobs.push(knob);
            box(.14, .03, .14, mats.dark, -.82, .1, zz, .04);
        }
        s3a.panel = panel;

        // --- 4 Nilai dan unduh: inti AI di atas sabuk + lembar Excel keluar ke baki ---
        var s4 = stations[3], s4a = {};
        g_ = s4;
        box(1.7, 1.25, 1.9, mats.dark, 0, 0, 0, .14);
        // terowongan sabuk
        var tun = add(s4, new THREE.BoxGeometry(1.72, .6, .86), mats.belt, 0, .26, 0, false); tun.scale.set(1.02, 1, 1.02);
        s4a.core = add(s4, new THREE.OctahedronGeometry(.42, 0), mats.coreGlow, 0, 1.7, 0);
        s4a.ring = add(s4, new THREE.TorusGeometry(.62, .035, 8, 40), mats.lime, 0, 1.7, 0, false); s4a.ring.rotation.x = Math.PI / 2.4;
        // baki Excel di ujung sabuk
        var tray = new THREE.Group(); tray.position.set(-2.15, 0, 0); s4.add(tray);
        g_ = tray;
        box(1.3, .12, 1.2, mats.back, 0, 0, 0, .1);
        s4a.sheets = [];
        var gtex = (function () {
            var c = document.createElement('canvas'); c.width = 128; c.height = 128; var x2 = c.getContext('2d');
            x2.fillStyle = '#ffffff'; x2.fillRect(0, 0, 128, 128);
            x2.fillStyle = '#c4f55d'; x2.fillRect(0, 0, 128, 24);
            x2.strokeStyle = '#1b1b1b'; x2.lineWidth = 3;
            for (var k = 0; k <= 4; k++) { x2.beginPath(); x2.moveTo(0, k * 32); x2.lineTo(128, k * 32); x2.stroke(); }
            for (var j = 0; j <= 3; j++) { x2.beginPath(); x2.moveTo(j * 42.6, 0); x2.lineTo(j * 42.6, 128); x2.stroke(); }
            var t = new THREE.CanvasTexture(c); t.colorSpace = THREE.SRGBColorSpace; t.anisotropy = 4; return t;
        })();
        var gridMat = new THREE.MeshStandardMaterial({ map: gtex, roughness: .6 });
        for (i = 0; i < 3; i++) {
            var sh = add(tray, new THREE.BoxGeometry(.95, .035, .95), gridMat, 0, .12 + i * .045, 0);
            sh.rotation.y = (i - 1) * .08; s4a.sheets.push(sh);
        }
        s4a.arrow = new THREE.Group(); s4a.arrow.position.set(0, 1.2, 0); tray.add(s4a.arrow);
        add(s4a.arrow, new THREE.CylinderGeometry(.09, .09, .4, 10), mats.lime, 0, .2, 0);
        add(s4a.arrow, new THREE.ConeGeometry(.22, .3, 14), mats.lime, 0, -.1, 0).rotation.z = Math.PI;
        s4a.tray = tray;

        /* ---------- paket jawaban yang berjalan di sabuk ---------- */
        var N = 5, SPEED = 1.15, packets = [];
        var pGeo = rbox(.62, .07, .78, .06, .025);
        var hGeo = rbox(.5, .03, .18, .03, .012), lGeo = new THREE.BoxGeometry(.46, .015, .045);
        var pM = new THREE.MeshStandardMaterial({ roughness: .5 }); pM.userData.role = 'paper'; mats.pkt = pM;
        for (i = 0; i < N; i++) {
            var pk = new THREE.Group();
            var card = new THREE.Mesh(pGeo, pM); card.castShadow = true; card.receiveShadow = true; pk.add(card);
            var head = new THREE.Mesh(hGeo, mats.mist); head.position.set(0, .075, -.27); pk.add(head);
            for (var ln = 0; ln < 3; ln++) { var l = new THREE.Mesh(lGeo, mats.dark); l.position.set(0, .068, -.04 + ln * .15); pk.add(l); }
            var tag = new THREE.Mesh(new THREE.BoxGeometry(.5, .025, .07), mats.lime); tag.position.set(0, .08, .33); tag.visible = false; pk.add(tag);
            pk.userData = { head: head, tag: tag }; machine.add(pk); packets.push(pk);
        }
        paint();

        /* ---------- kamera, interaksi ---------- */
        var CTR = new THREE.Vector3((PX0 + PX1) / 2, .4, 0);
        var baseYaw = .0, basePitch = .72, userYaw = 0, userPitch = 0, lastDrag = -99;
        var focusIdx = -1, focusK = 0, hoverIdx = -1, dragging = false, sx = 0, sy = 0, moved = 0;
        var W = 1, H = 1, dist = 22;
        function fit() {
            W = stage.clientWidth; H = stage.clientHeight; if (!W || !H) { return; }
            renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.75)); renderer.setSize(W, H, false);
            camera.aspect = W / H; camera.updateProjectionMatrix();
            var th = Math.tan(THREE.MathUtils.degToRad(camera.fov / 2)), tw = th * camera.aspect;
            dist = Math.max(6.7 / tw, 4.5 / th) * 1.04;
        }
        var tgt = new THREE.Vector3(), cam = new THREE.Vector3(), fv = new THREE.Vector3();
        function placeCamera(t) {
            var idle = (t - lastDrag) > 3 ? 1 : 0;
            userYaw += (0 - userYaw) * (idle ? .02 : 0); userPitch += (0 - userPitch) * (idle ? .02 : 0);
            var yaw = baseYaw + userYaw + (reduce ? 0 : Math.sin(t * .25) * .1), pitch = basePitch + userPitch;
            var d = dist * (1 - .38 * focusK);
            tgt.copy(CTR);
            if (focusIdx >= 0) { tgt.lerp(fv.set(stations[focusIdx].position.x, .6, stations[focusIdx].position.z), focusK * .85); }
            cam.set(tgt.x + Math.sin(yaw) * Math.cos(pitch) * d, tgt.y + Math.sin(pitch) * d, tgt.z + Math.cos(yaw) * Math.cos(pitch) * d);
            camera.position.copy(cam); camera.lookAt(tgt);
        }
        canvas.addEventListener('pointerdown', function (e) { dragging = true; moved = 0; sx = e.clientX; sy = e.clientY; stage.classList.add('is-drag'); try { canvas.setPointerCapture(e.pointerId); } catch (er) {} });
        canvas.addEventListener('pointermove', function (e) {
            if (dragging) {
                var dx = e.clientX - sx, dy = e.clientY - sy; sx = e.clientX; sy = e.clientY; moved += Math.abs(dx) + Math.abs(dy);
                userYaw = THREE.MathUtils.clamp(userYaw - dx * .006, -.9, .9); userPitch = THREE.MathUtils.clamp(userPitch + dy * .004, -.3, .3); lastDrag = performance.now() / 1000;
            } else { var hp = pick(e); if (hp !== hoverIdx) { hoverIdx = hp; poke(); } }
        });
        function endDrag(e) { if (dragging && moved < 5) { var p = pick(e); setFocus(p === focusIdx ? -1 : p); } dragging = false; stage.classList.remove('is-drag'); }
        canvas.addEventListener('pointerup', endDrag);
        canvas.addEventListener('pointercancel', function () { dragging = false; stage.classList.remove('is-drag'); });
        canvas.addEventListener('pointerleave', function () { if (!dragging) { hoverIdx = -1; } });
        var ray = new THREE.Raycaster(), ndc = new THREE.Vector2();
        function pick(e) {
            var r = canvas.getBoundingClientRect(); ndc.set(((e.clientX - r.left) / r.width) * 2 - 1, -((e.clientY - r.top) / r.height) * 2 + 1);
            ray.setFromCamera(ndc, camera);
            var best = -1, bd = 1e9;
            stations.forEach(function (g, k) { var h = ray.intersectObject(g, true)[0]; if (h && h.distance < bd) { bd = h.distance; best = k; } });
            return best;
        }
        function setFocus(k) {
            focusIdx = k; poke(); chips.forEach(function (c, j) { c.setAttribute('aria-pressed', j === k ? 'true' : 'false'); });
        }
        chips.forEach(function (c, j) { c.addEventListener('click', function () { setFocus(focusIdx === j ? -1 : j); }); });
        window.addEventListener('keydown', function (e) { if (e.key === 'Escape' && focusIdx >= 0) { setFocus(-1); } });

        /* ---------- label HTML mengikuti posisi 3D ---------- */
        var lp = new THREE.Vector3();
        function updateLabels() {
            labelEls.forEach(function (el, k) {
                lp.set(stations[k].position.x, stations[k].position.y + (k === 3 ? 2.9 : k === 0 ? 2.8 : k === 1 ? 2.2 : 1.7), stations[k].position.z + (k === 0 ? -1.5 : 0)).project(camera);
                var vis = lp.z < 1; el.style.transform = 'translate(' + ((lp.x * .5 + .5) * W).toFixed(1) + 'px,' + ((-lp.y * .5 + .5) * H).toFixed(1) + 'px) translate(-50%,-100%)';
                el.classList.toggle('is-on', vis);
                el.classList.toggle('is-hot', k === hoverIdx || k === focusIdx);
            });
        }

        /* ---------- animasi ---------- */
        var clock = { t: 0 }, lastNow = performance.now();
        var bump = function (d, w) { var x = Math.max(0, 1 - Math.abs(d) / w); return x * x * (3 - 2 * x); };
        var act = [0, 0, 0, 0];
        var hl = new THREE.Color();
        function frame(t, dt) {
            var tau = (t * SPEED) / L;
            beltTex.offset.x = -((t * SPEED) / 1.1) % 1;
            act[0] = act[1] = act[2] = act[3] = 0;
            var near = -1, nd = 1e9;
            for (var k = 0; k < N; k++) {
                var u = ((tau + k / N) % 1), a = at(u), pk2 = packets[k];
                pk2.position.copy(a.p); pk2.position.y += .06; pk2.rotation.y = Math.atan2(a.t.x, a.t.z);
                var sc = Math.min(1, u / .05, (1 - u) / .06); pk2.scale.setScalar(Math.max(.001, sc));
                for (var s = 0; s < 4; s++) { act[s] = Math.max(act[s], bump((u - uSt[s]) * L, 1.1)); }
                pk2.userData.head.visible = u < uSt[1] + .01; pk2.userData.tag.visible = u > uSt[2];
                var dd = (u - uSt[1]) * L; if (Math.abs(dd) < nd) { nd = Math.abs(dd); near = dd; }
            }
            // 1: colokan masuk-keluar + LED
            s1a.plug.position.x = -1.45 + act[0] * .32 + Math.sin(t * 2) * .02;
            s1a.led.material.emissiveIntensity = .35 + act[0] * 1.4;
            // 2: laser menyapu, kepingan identitas melompat ke laci
            s2a.laser.position.x = Math.sin(t * 3.2) * .38; s2a.beam.position.x = s2a.laser.position.x;
            s2a.beam.material.opacity = .25 + act[1] * .45;
            var cp = THREE.MathUtils.clamp((near + 1.1) / 1.6, 0, 1);
            if (near > -1.1 && near < .5 && act[1] > .02) {
                s2a.chip.visible = true; var e1 = cp * cp * (3 - 2 * cp);
                s2a.chip.position.set(0, .5 + Math.sin(e1 * Math.PI) * .7, THREE.MathUtils.lerp(-.1, -1.7, e1)); s2a.chip.rotation.y = e1 * 3;
            } else { s2a.chip.visible = false; }
            // 3: slider rubrik bernapas
            for (var b = 0; b < 3; b++) {
                var v = .35 + .5 * (.5 + .5 * Math.sin(t * (.8 + b * .3) + b * 2)) * (.6 + act[2] * .4);
                s3a.bars[b].scale.x = Math.max(.02, v * 1.5); s3a.bars[b].position.x = -.65 + v * .75; s3a.knobs[b].position.x = -.65 + v * 1.5;
            }
            // 4: inti AI berputar, lembar bergeser, panah unduh memantul
            s4a.core.rotation.y = t * 1.3; s4a.core.rotation.x = t * .7; s4a.core.position.y = 1.7 + Math.sin(t * 2) * .06;
            s4a.core.material.emissiveIntensity = .4 + act[3] * 1.2; s4a.ring.rotation.z = t * 1.6;
            for (var q = 0; q < 3; q++) { s4a.sheets[q].position.y = .12 + q * .045 + Math.max(0, Math.sin(t * 2.2 - q * .8)) * .03 * act[3]; }
            s4a.arrow.position.y = 1.15 + Math.sin(t * 3) * .1;
            // sorot stasiun yang di-hover / difokuskan
            stations.forEach(function (g, j) { var tgtY = (j === hoverIdx || j === focusIdx) ? .15 + .18 : .15; g.position.y += (tgtY - g.position.y) * Math.min(1, dt * 8); });
            focusK += ((focusIdx >= 0 ? 1 : 0) - focusK) * Math.min(1, dt * 4);
            placeCamera(t); updateLabels();
            renderer.render(scene, camera);
        }

        var visible = true, raf = 0, running = false, wake = 0;
        function poke() { wake = performance.now() + 1400; start(); }
        function loop(now) {
            raf = 0; if (!visible || document.hidden || (reduce && now > wake)) { running = false; return; }
            var dt = Math.min(.05, (now - lastNow) / 1000); lastNow = now; clock.t += dt;
            frame(clock.t, dt); raf = requestAnimationFrame(loop);
        }
        function start() { if (running || (reduce && performance.now() > wake)) { return; } running = true; lastNow = performance.now(); raf = requestAnimationFrame(loop); }
        if ('IntersectionObserver' in window) { new IntersectionObserver(function (en) { visible = en[0].isIntersecting; if (visible) { start(); } }).observe(stage); }
        document.addEventListener('visibilitychange', function () { if (!document.hidden) { start(); } });

        // ikuti pergantian tema (atribut data-theme pada <html>)
        new MutationObserver(function () { setTimeout(function () { readPalette(); paint(); poke(); }, 60); })
            .observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
        window.addEventListener('resize', function () { fit(); poke(); });

        host.classList.add('is-ready'); stage.hidden = false; fit();
        clock.t = 6; frame(clock.t, 0);              // satu frame awal, juga untuk reduced-motion
        start();
    }
})();
</script>
