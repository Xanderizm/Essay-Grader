{{--
    resources/views/grader-bot.blade.php

    Dipakai di band ajakan landing:   @include('grader-bot')

    Adegan: Ilham (duduk) mengambil lembar jawaban dari tumpukan kanan, memeriksanya bersama Bobi (melayang di
    belakang, memindai dengan sinar lime), memberi centang, lalu menaruhnya di tumpukan kiri. Berulang mulus (10 dtk).
    Bentuk SVG Ilham & Bobi diambil dari auth-mascots.blade.php (kaki Ilham dipendekkan agar duduk, lengan dibuat
    fleksibel agar bisa menjangkau kertas). Semua kelas/atribut berawalan "gb" agar tidak bentrok dengan CSS lain.
--}}
@verbatim
<style>
/* ───────── Token ───────── */
.gb{
  --gb-ink:#1b1b1b; --gb-paper:#ffffff; --gb-mist:#f1f1ef; --gb-lime:var(--lime,#c4f55d); --gb-line:#1b1b1b;
  position:relative; width:100%; max-width:340px; margin-inline:auto; justify-self:center; align-self:center;
  user-select:none; -webkit-user-select:none; pointer-events:none;
}
html[data-theme="dark"] .gb{ --gb-mist:#d6d6d1; --gb-line:#f1f1ef; }
@media (prefers-color-scheme:dark){
  html:not([data-theme="light"]):not(.light) .gb{ --gb-mist:#d6d6d1; --gb-line:#f1f1ef; }
}
.gb-svg{ display:block; width:100%; height:auto; overflow:visible }

/* ───────── Bahasa bentuk SVG (sama dengan auth-mascots) ───────── */
.gb .p{ fill:var(--gb-paper) }
.gb .m{ fill:var(--gb-mist) }
.gb .i{ fill:var(--gb-ink) }
.gb .k{ stroke:var(--gb-ink); stroke-linejoin:round; stroke-linecap:round }
.gb .acc{ fill:var(--gb-lime) }
.gb .acc-s{ fill:none; stroke:var(--gb-lime); stroke-width:3.4; stroke-linecap:round }

.gb-ground{ stroke:var(--gb-line); stroke-width:3; stroke-linecap:round }
.gb-sh{ fill:var(--gb-line); opacity:.12 }
.gb-arm{ fill:none; stroke:var(--gb-ink); stroke-width:3.4; stroke-linecap:round; stroke-linejoin:round }
.gb-beam{ fill:var(--gb-lime); stroke:var(--gb-lime); stroke-width:1.2; stroke-linejoin:round; fill-opacity:0; stroke-opacity:0 }

/* ───────── Gerak ringan berbasis CSS (JS menangani jalur & pose) ───────── */
@keyframes gb-bob{ 0%,100%{ transform:translateY(-2.5px) } 50%{ transform:translateY(2.5px) } }
@keyframes gb-flick{ 0%,100%{ transform:scaleY(1) } 35%{ transform:scaleY(.76) } 70%{ transform:scaleY(1.2) } }
@keyframes gb-blink{ 0%,92%,100%{ transform:scaleY(1) } 96%{ transform:scaleY(.08) } }
@keyframes gb-nod{ 0%,100%{ transform:translateY(0) } 50%{ transform:translateY(1.2px) } }
.gb [data-p="float"]{ animation:gb-bob 2.6s ease-in-out infinite }
.gb .gb-fl{ transform-box:fill-box; transform-origin:50% 0; animation:gb-flick .22s linear infinite }
.gb .gb-blink{ transform-box:fill-box; transform-origin:center; animation:gb-blink 4.8s infinite }
.gb .gb-blink.b{ animation-duration:6.1s; animation-delay:-2s }
.gb .gb-nod{ animation:gb-nod 3.2s ease-in-out infinite }
.gb.gb-paused *{ animation-play-state:paused !important }
@media (prefers-reduced-motion:reduce){ .gb *{ animation:none !important } }
</style>
@endverbatim

@php
    // Tumpukan kanan (belum dinilai): 12 lembar, sedikit berantakan. Indeks 11 = lembar teratas yang diambil Ilham.
    $rightJitter = [0, 2, -2, 1, -1, 3, -2, 1, 2, -1, 2, 0];
@endphp

<div class="gb" data-gb>
    <svg class="gb-svg" viewBox="92 36 236 250" role="img" aria-label="Ilham mengambil lembar jawaban, Bobi memindainya, lalu Ilham memberi centang dan menumpuknya di sisi kiri">

        {{-- tanah & bayangan --}}
        <line class="gb-ground" x1="96" y1="273" x2="324" y2="273"/>
        <ellipse class="gb-sh" cx="200" cy="273" rx="42" ry="3.6"/>
        <ellipse class="gb-sh" data-p="shB" cx="270" cy="273" rx="18" ry="3" style="opacity:.07"/>

        {{-- ═════════ BOBI · melayang di belakang ═════════ --}}
        <g data-p="bobi">
            <g data-p="float">
                <g data-p="flame"><polygon class="gb-fl acc k" points="88,219 112,219 100,254" stroke-width="2.5"/></g>
                <ellipse cx="100" cy="214" rx="26" ry="5" class="acc k" stroke-width="2.5"/>
                <ellipse cx="100" cy="160" rx="42" ry="46" class="p k" stroke-width="2.5"/>
                <path d="M62 174 Q100 212 138 174 Q132 203 100 206 Q68 203 62 174Z" class="m"/>
                <circle cx="100" cy="164" r="6" class="acc k" stroke-width="2"/>
                <g data-p="bArmL"><circle cx="50" cy="160" r="10" class="p k" stroke-width="2.5"/></g>
                <g data-p="bArmR"><circle cx="150" cy="160" r="10" class="p k" stroke-width="2.5"/></g>
                <g data-p="bHead">
                    <ellipse cx="100" cy="121" rx="18" ry="5" class="acc k" stroke-width="2"/>
                    <rect x="40" y="30" width="120" height="90" rx="44" class="p k" stroke-width="2.5"/>
                    <path d="M48 96 Q100 136 152 96 Q150 118 100 120 Q50 118 48 96Z" class="m"/>
                    <rect x="56" y="50" width="88" height="54" rx="26" class="i"/>
                    <g data-p="bFace">
                        <line data-p="bScan" x1="64" y1="78" x2="136" y2="78" class="acc-s" style="stroke-width:3" opacity="0"/>
                        <g transform="translate(80 76)"><g data-p="bEyeL"><g class="gb-blink b"><circle class="eye-n acc" r="8"/><path class="eye-h acc-s" d="M-8 3 Q0 -9 8 3" style="display:none;stroke-width:4"/></g></g></g>
                        <g transform="translate(120 76)"><g data-p="bEyeR"><g class="gb-blink b"><circle class="eye-n acc" r="8"/><path class="eye-h acc-s" d="M-8 3 Q0 -9 8 3" style="display:none;stroke-width:4"/></g></g></g>
                        <g transform="translate(100 93)"><path data-p="bMouth" class="acc-s" d="M-7 0 Q0 6 7 0"/></g>
                    </g>
                </g>
            </g>
        </g>

        {{-- ═════════ Tumpukan kertas ═════════ --}}
        <g class="gb-rstack">
            @foreach($rightJitter as $i => $j)
                <rect class="gb-rs p k" data-i="{{ $i }}" x="{{ 244 + $j }}" y="{{ 271.5 - 5 * ($i + 1) }}" width="52" height="5" stroke-width="1.6"/>
            @endforeach
        </g>
        <g class="gb-lstack">
            @for($i = 0; $i < 6; $i++)
                <g class="gb-ls" data-i="{{ $i }}" opacity="{{ $i < 2 ? 1 : 0 }}">
                    <rect class="p k" x="119" y="{{ 271.5 - 5 * ($i + 1) }}" width="52" height="5" stroke-width="1.6"/>
                    <rect class="acc" x="123" y="{{ 271.5 - 5 * ($i + 1) + 1.4 }}" width="9" height="2.2" rx="1"/>
                </g>
            @endfor
        </g>

        {{-- ═════════ ILHAM · duduk di atas garis ═════════ --}}
        <g class="gb-ilham" transform="translate(120 86.7) scale(.8)">
            <g transform="translate(0 12)">
                {{-- kaki (dipendekkan & sedikit terbuka agar duduk) --}}
                <rect x="74" y="200" width="12" height="12" class="p k" stroke-width="4"/>
                <rect x="62" y="207" width="28" height="10" rx="4" class="p k" stroke-width="4"/>
                <rect x="114" y="200" width="12" height="12" class="p k" stroke-width="4"/>
                <rect x="110" y="207" width="28" height="10" rx="4" class="p k" stroke-width="4"/>
                {{-- bodi --}}
                <rect x="70" y="150" width="60" height="56" rx="24" class="p k" stroke-width="4"/>
                <rect x="118" y="158" width="8" height="40" rx="4" class="m"/>
                <polygon points="100,167 109,172 109,183 100,188 91,183 91,172" class="acc k" stroke-width="2.5"/>
                <circle cx="72" cy="162" r="4.5" class="acc k" stroke-width="2"/>
                <circle cx="128" cy="162" r="4.5" class="acc k" stroke-width="2"/>
                <rect x="90" y="138" width="20" height="16" class="m k" stroke-width="3"/>
                {{-- kepala + layar wajah --}}
                <g data-p="iHead">
                    <g class="gb-nod">
                        <rect x="34" y="28" width="132" height="114" rx="28" class="p k" stroke-width="4"/>
                        <rect x="150" y="42" width="11" height="86" rx="5" class="m"/>
                        <rect x="46" y="40" width="108" height="90" rx="18" class="i"/>
                        <g data-p="iFace">
                            <g transform="translate(77 58)"><line data-p="iBrowL" x1="-11" y1="0" x2="11" y2="0" class="acc-s" style="stroke-width:4"/></g>
                            <g transform="translate(123 58)"><line data-p="iBrowR" x1="-11" y1="0" x2="11" y2="0" class="acc-s" style="stroke-width:4"/></g>
                            <g transform="translate(77 82)"><g data-p="iEyeL"><g class="gb-blink"><rect class="eye-n acc" x="-9" y="-12" width="18" height="24" rx="8"/><path class="eye-h acc-s" d="M-10 5 Q0 -12 10 5" style="display:none;stroke-width:4"/></g></g></g>
                            <g transform="translate(123 82)"><g data-p="iEyeR"><g class="gb-blink"><rect class="eye-n acc" x="-9" y="-12" width="18" height="24" rx="8"/><path class="eye-h acc-s" d="M-10 5 Q0 -12 10 5" style="display:none;stroke-width:4"/></g></g></g>
                            <g transform="translate(100 112)"><path data-p="iMouth" class="acc-s" d="M-12 0 Q0 9 12 0"/></g>
                        </g>
                    </g>
                </g>
            </g>
        </g>

        {{-- ═════════ Lapisan depan: lengan, sinar pindai, kertas, tangan ═════════ --}}
        <g class="gb-fg">
            <path data-p="aL" class="gb-arm"/>
            <path data-p="aR" class="gb-arm"/>
            <circle cx="177.6" cy="225.9" r="3.6" class="acc k" stroke-width="1.6"/>
            <circle cx="222.4" cy="225.9" r="3.6" class="acc k" stroke-width="1.6"/>

            <polygon data-p="beam" class="gb-beam" points="0,0 0,0 0,0 0,0"/>

            <g data-p="paper" opacity="0">
                <rect data-p="pSh" class="i" x="-24" y="-31" width="52" height="66" rx="3" opacity="0"/>
                <rect data-p="pBody" class="p k" x="-26" y="-33" width="52" height="66" rx="3" stroke-width="2.2"/>
                <g data-p="pc">
                    <rect data-p="pHl" x="-26" y="-33" width="52" height="0" fill="var(--gb-lime)" opacity=".45"/>
                    <line x1="-19" y1="-24" x2="2" y2="-24" stroke="#1b1b1b" stroke-width="2.6" stroke-linecap="round"/>
                    <circle data-p="pBox" cx="16" cy="-24" r="6" fill="#f1f1ef" stroke="#1b1b1b" stroke-width="1.8"/>
                    <g stroke="#1b1b1b" stroke-width="1.8" stroke-linecap="round" opacity=".45">
                        <line x1="-19" y1="-12" x2="18" y2="-12"/>
                        <line x1="-19" y1="-5"  x2="20" y2="-5"/>
                        <line x1="-19" y1="2"   x2="12" y2="2"/>
                        <line x1="-19" y1="9"   x2="19" y2="9"/>
                        <line x1="-19" y1="16"  x2="15" y2="16"/>
                        <line x1="-19" y1="23"  x2="6"  y2="23"/>
                    </g>
                    <path data-p="pChkU" d="M-10 10 L-2 19 L15 -4" fill="none" stroke="var(--gb-lime)" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="41" stroke-dashoffset="41"/>
                    <path data-p="pChk"  d="M-10 10 L-2 19 L15 -4" fill="none" stroke="#1b1b1b" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="41" stroke-dashoffset="41"/>
                </g>
                <rect data-p="pScan" class="acc k" x="-27" y="-2" width="54" height="4" rx="1" stroke-width="1.2" opacity="0"/>
            </g>

            <circle data-p="hL" r="4.8" cx="162" cy="257" class="p k" stroke-width="2.4"/>
            <circle data-p="hR" r="4.8" cx="238" cy="257" class="p k" stroke-width="2.4"/>
        </g>
    </svg>
</div>

@verbatim
<script>
(() => {
  'use strict';
  const root = document.querySelector('[data-gb]');
  if (!root || root.dataset.gbInit) return;
  root.dataset.gbInit = '1';

  /* ═════════ Util ═════════ */
  const RM = matchMedia('(prefers-reduced-motion: reduce)');
  const clamp = (v, a, b) => Math.min(b, Math.max(a, v));
  const lerp = (a, b, t) => a + (b - a) * t;
  const sm = x => { x = clamp(x, 0, 1); return x * x * (3 - 2 * x); };
  const f = v => Math.round(v * 100) / 100;
  const q = n => root.querySelector('[data-p="' + n + '"]');

  /** Interpolasi kubik monoton (tanpa overshoot): halus di setiap kunci, diam total di bagian "tahan". */
  const spline = keys => {
    const n = keys.length, t = keys.map(a => a[0]), v = keys.map(a => a[1]);
    const d = [], m = new Array(n).fill(0);
    for (let i = 0; i < n - 1; i++) d[i] = (v[i + 1] - v[i]) / (t[i + 1] - t[i]);
    for (let i = 1; i < n - 1; i++) m[i] = d[i - 1] * d[i] <= 0 ? 0 : (d[i - 1] + d[i]) / 2;
    for (let i = 0; i < n - 1; i++) {
      if (d[i] === 0) { m[i] = m[i + 1] = 0; continue; }
      const a = m[i] / d[i], b = m[i + 1] / d[i], s = a * a + b * b;
      if (s > 9) { const u = 3 / Math.sqrt(s); m[i] = u * a * d[i]; m[i + 1] = u * b * d[i]; }
    }
    return p => {
      if (p <= t[0]) return v[0];
      if (p >= t[n - 1]) return v[n - 1];
      let i = 0; while (p > t[i + 1]) i++;
      const h = t[i + 1] - t[i], u = (p - t[i]) / h, u2 = u * u, u3 = u2 * u;
      return (2 * u3 - 3 * u2 + 1) * v[i] + (u3 - 2 * u2 + u) * h * m[i] + (-2 * u3 + 3 * u2) * v[i + 1] + (u3 - u2) * h * m[i + 1];
    };
  };

  /* ═════════ Dunia (koordinat panggung / viewBox) ═════════ */
  const T = 10;                         // detik per siklus
  const G = 271.5;                      // permukaan garis tanah
  const MAXL = 6;                       // tumpukan kiri penuh → direset
  const SH_L = [177.6, 229.1], SH_R = [222.4, 229.1];       // bahu Ilham
  const REST_L = [162, 257], REST_R = [238, 257];           // posisi tangan santai
  const STACK_R = [270, 210], FACE = [200, 164];
  const LAND = k => G - 5 * k - 2.5;                         // pusat lembar yang mendarat di tumpukan kiri

  /* ═════════ Lini masa (detik) ═════════
     0.0–1.1  Langkah 1: tangan kanan mengambil lembar teratas, Bobi mendekat di kanan atas bahu
     1.1–2.8  kertas dibalik & dibawa ke depan dada
     2.8–4.6  Langkah 2: Bobi di atas kepala Ilham memindai (sinar lime), Ilham menyimak
     4.4–5.6  Langkah 3: tangan kiri menggambar centang
     5.6–7.8  kertas dipindah ke tumpukan kiri, Bobi ikut bergeser ke kiri
     7.8–10   Langkah 4: tangan kembali, lembar baru muncul di tumpukan kanan, Bobi kembali ke kanan */
  const COM = {
    // Bobi
    bx: spline([[0,272],[.7,276],[1.4,268],[2,252],[2.7,224],[3.2,217],[4.4,216],[5.2,204],[5.9,184],[6.9,164],[7.8,152],[8.3,152],[9.1,236],[9.7,270],[10,272]]),
    by: spline([[0,120],[.7,124],[1.4,114],[2,108],[2.7,106],[3.2,104],[4.4,106],[5.2,110],[5.9,114],[6.9,120],[7.8,122],[8.3,122],[9.1,118],[9.7,120],[10,120]]),
    bt: spline([[0,6],[.7,9],[1.4,7],[2,3],[2.7,0],[4.4,0],[5.2,-3],[5.9,-6],[6.9,-9],[7.8,-9],[8.3,-8],[9.1,0],[9.7,6],[10,6]]),
    sw:   spline([[0,0],[2.7,0],[2.95,1],[4.4,1],[4.7,0],[10,0]]),      // mode pemindai (mata Bobi jadi bilah)
    beam: spline([[0,0],[2.95,0],[3.15,1],[4.3,1],[4.55,0],[10,0]]),    // sinar ke kertas
    arms: spline([[0,0],[2.8,0],[3.2,1],[4.4,1],[4.8,0],[10,0]]),       // tangan Bobi terangkat
    scanY: spline([[3,-31],[3.9,31],[4.5,-31]]),                        // garis pindai di kertas
    hl: spline([[3,0],[3.9,66],[10,66]]),                               // area terpindai (sorot lime)
    // ekspresi Ilham (& Bobi untuk happy)
    eo: spline([[0,1],[.7,1.08],[2,1.05],[2.8,.78],[4.4,.74],[5.4,1],[10,1]]),
    eb: spline([[0,0],[1,-2],[2.8,5],[4.4,6],[5.3,-3],[6,-4],[8,-2],[9.5,0],[10,0]]),
    em: spline([[0,.7],[2.8,.12],[4.4,0],[5.2,.3],[5.5,1.4],[6.8,1.25],[8.2,.9],[9.6,.7],[10,.7]]),
    eh: spline([[0,0],[5.35,0],[5.45,1],[6.5,1],[6.6,0],[10,0]]),
    // kertas
    px: spline([[1,270],[1.5,262],[2,242],[2.6,222],[2.8,218],[5.5,218],[6.2,196],[6.9,166],[7.4,150],[7.7,145],[10,145]]),
    ps: spline([[1,.0758],[1.3,.0758],[2.2,1],[5.9,1],[6.9,.75],[7.4,.35],[7.7,.0758],[10,.0758]]),
    pr: spline([[1,0],[1.6,6],[2.4,-2],[2.8,0],[5.5,0],[6.3,-6],[7.2,-3],[7.7,0],[10,0]]),
    // genggaman tangan
    wR: spline([[0,0],[.95,0],[1.12,1],[5.6,1],[6.2,0],[10,0]]),
    wL: spline([[0,0],[2.2,0],[2.55,1],[4.15,1],[4.4,0],[5.55,0],[5.8,1],[7.75,1],[8.15,0],[10,0]]),
    gRy: spline([[1,-33],[1.1,-33],[2.4,30],[10,30]]),                  // tangan kanan menyusuri tepi kiri kertas
    rx: spline([[0,238],[.8,248],[1.05,248],[2.6,194],[5.6,194],[6.4,238],[10,238]]),
    ry: spline([[0,257],[.8,209.5],[1.05,209.5],[2.6,256],[5.6,256],[6.4,257],[10,257]])
  };
  const cache = [];
  /** Jalur yang bergantung pada tinggi tumpukan kiri (k = jumlah lembar yang sudah ada). */
  const tracks = k => {
    if (cache[k]) return cache[k];
    const ly = LAND(k);
    return cache[k] = Object.assign({}, COM, {
      py: spline([[1,214],[1.5,196],[2,190],[2.6,224],[2.8,226],[5.5,226],[6.2,212],[6.9,ly - 18],[7.4,ly - 6],[7.7,ly],[10,ly]]),
      lx: spline([[0,162],[2.3,194],[4.2,194],[4.6,208],[4.75,208],[4.95,216],[5.25,233],[5.45,233],[5.75,194],[7.7,121],[8.1,121],[8.9,162],[10,162]]),
      ly: spline([[0,257],[2.3,224],[4.2,224],[4.6,236],[4.75,236],[4.95,245],[5.25,222],[5.45,220],[5.75,224],[7.7,ly],[8.1,ly],[8.9,257],[10,257]])
    });
  };

  /* ═════════ Elemen ═════════ */
  const el = {
    bobi: q('bobi'), flame: q('flame'), bHead: q('bHead'), bFace: q('bFace'), bScan: q('bScan'), bMouth: q('bMouth'),
    bEye: [q('bEyeL'), q('bEyeR')], bArmL: q('bArmL'), bArmR: q('bArmR'), shB: q('shB'),
    iHead: q('iHead'), iFace: q('iFace'), iEye: [q('iEyeL'), q('iEyeR')], iMouth: q('iMouth'), iBrowL: q('iBrowL'), iBrowR: q('iBrowR'),
    aL: q('aL'), aR: q('aR'), hL: q('hL'), hR: q('hR'), beam: q('beam'),
    paper: q('paper'), pSh: q('pSh'), pBody: q('pBody'), pc: q('pc'), pHl: q('pHl'), pScan: q('pScan'), pBox: q('pBox'), pChk: q('pChk'), pChkU: q('pChkU'),
    rTop: root.querySelector('.gb-rs[data-i="11"]'),
    ls: Array.from(root.querySelectorAll('.gb-ls'))
  };
  const st = {igx: 0, igy: 0, bgx: 0, bgy: 0, ih: null, bh: null};

  const setHappy = (eyes, h, key) => {
    if (st[key] === h) return; st[key] = h;
    eyes.forEach(g => { g.querySelector('.eye-n').style.display = h ? 'none' : ''; g.querySelector('.eye-h').style.display = h ? '' : 'none'; });
  };

  /** Lengan fleksibel: lengkung ke luar bila pendek, lurus (memanjang) bila jauh. */
  const arm = (path, S, H, side) => {
    const dx = H[0] - S[0], dy = H[1] - S[1], d = Math.max(18, Math.hypot(dx, dy)), L = 36;
    const h = d < L ? Math.min(14, Math.sqrt(L * L - d * d) / 2) : 0;
    let nx = -dy / d, ny = dx / d;
    if (nx * side + ny * .3 < 0) { nx = -nx; ny = -ny; }
    const mx = (S[0] + H[0]) / 2 + nx * h * 2, my = (S[1] + H[1]) / 2 + ny * h * 2;
    path.setAttribute('d', 'M' + f(S[0]) + ' ' + f(S[1]) + 'Q' + f(mx) + ' ' + f(my) + ' ' + f(H[0]) + ' ' + f(H[1]));
  };
  /** Titik genggam pada kertas (koordinat lokal kertas → panggung). */
  const grip = (P, gx, gy) => {
    const a = P.r * Math.PI / 180, c = Math.cos(a), s = Math.sin(a), y = gy * P.s;
    return [P.x + gx * c - y * s, P.y + gx * s + y * c];
  };

  /* ═════════ Render satu bingkai ═════════ */
  function render(clock, dt) {
    const c = Math.floor(clock / T), p = clock - c * T, k = c % MAXL, tr = tracks(k);
    const kk = 1 - Math.exp(-9 * dt);

    /* — kertas — */
    const pv = p >= 1 && p < 7.85;
    const P = {x: tr.px(p), y: tr.py(p) + 2.5 * Math.exp(-Math.pow((p - 5.3) / .09, 2)), s: tr.ps(p), r: tr.pr(p)};

    /* — tangan & lengan — */
    const wR = tr.wR(p), wL = tr.wL(p), gR = grip(P, -24, tr.gRy(p)), gL = grip(P, -24, -2);
    const HR = [lerp(tr.rx(p), gR[0], wR), lerp(tr.ry(p), gR[1], wR)];
    const HL = [lerp(tr.lx(p), gL[0], wL), lerp(tr.ly(p), gL[1], wL)];
    arm(el.aR, SH_R, HR, 1); arm(el.aL, SH_L, HL, -1);
    el.hR.setAttribute('cx', f(HR[0])); el.hR.setAttribute('cy', f(HR[1]));
    el.hL.setAttribute('cx', f(HL[0])); el.hL.setAttribute('cy', f(HL[1]));

    /* — Bobi — */
    const bx = tr.bx(p), by = tr.by(p);
    const vx = (tr.bx(p + .03) - tr.bx(p - .03)) / .06, vy = (tr.by(p + .03) - tr.by(p - .03)) / .06;
    const tilt = tr.bt(p) + clamp(vx * .045, -7, 7), sw = tr.sw(p), beam = tr.beam(p), happy = tr.eh(p) > .5;
    el.bobi.setAttribute('transform', 'translate(' + f(bx) + ' ' + f(by + 18.6) + ') rotate(' + f(tilt) + ') scale(.62) translate(-100 -150)');
    el.flame.setAttribute('transform', 'translate(100 219) scale(1 ' + f(clamp(.55 + Math.hypot(vx, vy) * .012, .5, 1.3)) + ') translate(-100 -219)');
    el.shB.setAttribute('cx', f(bx));

    /* tatapan: ke kertas, lalu ke tumpukan yang relevan */
    const tgt = pv ? [P.x, P.y] : (p >= 7.85 && p < 8.6 ? [150, LAND(k) - 8] : STACK_R);
    st.bgx += (clamp((tgt[0] - bx) / 60, -1, 1) - st.bgx) * kk;
    st.bgy += (clamp((tgt[1] - (by - 27)) / 70, -1, 1) - st.bgy) * kk;
    el.bHead.setAttribute('transform', 'rotate(' + f(st.bgx * 6) + ' 100 120)');
    el.bFace.setAttribute('transform', 'translate(' + f(st.bgx * 3) + ' ' + f(st.bgy * 2) + ')');
    setHappy(el.bEye, happy, 'bh');
    const bsx = lerp(1, 1.55, sw), bsy = lerp(.92, .34, sw);
    el.bEye.forEach(g => g.setAttribute('transform', 'translate(' + f(st.bgx * 6) + ' ' + f(st.bgy * 4) + ') scale(' + f(bsx) + ' ' + f(bsy) + ')'));
    el.bMouth.setAttribute('transform', 'scale(1 ' + f(lerp(.6, .05, sw) + (happy ? .9 : 0)) + ')');
    el.bScan.setAttribute('y1', f(78 + Math.sin(clock * 9) * 20)); el.bScan.setAttribute('y2', f(78 + Math.sin(clock * 9) * 20));
    el.bScan.setAttribute('opacity', f(sw));
    const ar = tr.arms(p);
    el.bArmL.setAttribute('transform', 'translate(' + f(8 * ar) + ' ' + f(-14 * ar) + ')');
    el.bArmR.setAttribute('transform', 'translate(' + f(-8 * ar) + ' ' + f(-14 * ar) + ')');

    /* sinar pemindai: dari layar Bobi ke tepi atas kertas */
    if (beam > .01 && pv) {
      const ty = P.y - 33 * P.s;
      el.beam.setAttribute('points', f(bx - 17) + ',' + f(by - 10) + ' ' + f(bx + 17) + ',' + f(by - 10) + ' ' + f(P.x + 26) + ',' + f(ty) + ' ' + f(P.x - 26) + ',' + f(ty));
      el.beam.style.fillOpacity = f(.26 * beam); el.beam.style.strokeOpacity = f(.9 * beam);
    } else { el.beam.style.fillOpacity = 0; el.beam.style.strokeOpacity = 0; }

    /* — Ilham: tatapan & ekspresi — */
    const it = pv ? [P.x, P.y] : (p >= 7.85 && p < 8.6 ? [145, LAND(k) - 8] : STACK_R);
    st.igx += (clamp((it[0] - FACE[0]) / 70, -1, 1) - st.igx) * kk;
    st.igy += (clamp((it[1] - FACE[1]) / 60, -1, 1) - st.igy) * kk;
    el.iHead.setAttribute('transform', 'translate(0 ' + f(st.igy * 1.5) + ') rotate(' + f(st.igx * 6) + ' 100 142)');
    el.iFace.setAttribute('transform', 'translate(' + f(st.igx * 5) + ' ' + f(st.igy * 3) + ')');
    setHappy(el.iEye, happy, 'ih');
    const eo = tr.eo(p), eb = tr.eb(p);
    el.iEye.forEach(g => g.setAttribute('transform', 'translate(' + f(st.igx * 4) + ' ' + f(st.igy * 3) + ') scale(1 ' + f(eo) + ')'));
    el.iBrowL.setAttribute('transform', 'translate(0 ' + f(eb * .6) + ') rotate(' + f(eb) + ')');
    el.iBrowR.setAttribute('transform', 'translate(0 ' + f(eb * .6) + ') rotate(' + f(-eb) + ')');
    el.iMouth.setAttribute('transform', 'scale(' + f(1 + Math.abs(tr.em(p)) * .1) + ' ' + f(Math.max(.05, tr.em(p))) + ')');

    /* — kertas di tangan — */
    if (pv) {
      const s = P.s, h = 66 * s;
      el.paper.setAttribute('opacity', 1);
      el.paper.setAttribute('transform', 'translate(' + f(P.x) + ' ' + f(P.y) + ') rotate(' + f(P.r) + ')');
      el.pBody.setAttribute('y', f(-33 * s)); el.pBody.setAttribute('height', f(h)); el.pBody.setAttribute('rx', f(Math.min(3, h / 2.2)));
      el.pSh.setAttribute('y', f(-33 * s + 2)); el.pSh.setAttribute('height', f(h)); el.pSh.setAttribute('opacity', f(clamp((s - .45) / .4, 0, 1)));
      el.pc.setAttribute('transform', 'scale(1 ' + f(s) + ')'); el.pc.setAttribute('opacity', f(clamp((s - .3) / .35, 0, 1)));
      el.pHl.setAttribute('height', f(tr.hl(p)));
      el.pScan.setAttribute('y', f(tr.scanY(p) * s - 2)); el.pScan.setAttribute('opacity', f(beam));
      const len = 12.04 * clamp((p - 4.75) / .2, 0, 1) + 28.6 * clamp((p - 4.95) / .3, 0, 1);
      el.pChk.setAttribute('stroke-dashoffset', f(41 - len)); el.pChkU.setAttribute('stroke-dashoffset', f(41 - len));
      el.pBox.setAttribute('fill', p > 5.25 ? 'var(--gb-lime)' : '#f1f1ef');
    } else el.paper.setAttribute('opacity', 0);

    /* — tumpukan kanan: lembar teratas diambil, lalu lembar baru turun (reset halus) — */
    let a = 1, dy = 0;
    if (p >= 1 && p < 8) a = 0;
    else if (p >= 8 && p < 8.7) { const u = sm((p - 8) / .7); a = u; dy = -14 * (1 - u); }
    el.rTop.setAttribute('opacity', f(a)); el.rTop.setAttribute('transform', 'translate(0 ' + f(dy) + ')');

    /* — tumpukan kiri: bertambah rapi; setelah penuh, tumpukan lama dibawa pergi — */
    const clearing = k === 0 && c > 0 && p < 1.3;
    const u = sm((p - .2) / .9);
    el.ls.forEach((g, i) => {
      let o = 0, tx = 0, ty = 0;
      if (clearing) { o = 1 - u; tx = -26 * u; ty = 4 * u; }
      else if (i < k) o = 1;
      else if (i === k && p >= 7.75) o = Math.min(1, (p - 7.75) / .12);
      g.setAttribute('opacity', f(o));
      g.setAttribute('transform', tx || ty ? 'translate(' + f(tx) + ' ' + f(ty) + ')' : '');
    });
  }

  /* ═════════ Putaran: berhenti saat di luar layar / tab tersembunyi ═════════ */
  let clock = 0, last = 0, raf = 0, running = false, inView = true;
  const frame = now => {
    raf = 0; if (!running) return;
    const dt = Math.min(.05, (now - last) / 1000); last = now; clock += dt;
    render(clock, dt);
    raf = requestAnimationFrame(frame);
  };
  const start = () => { if (running) return; running = true; root.classList.remove('gb-paused'); last = performance.now(); raf = requestAnimationFrame(frame); };
  const stop = () => { running = false; cancelAnimationFrame(raf); root.classList.add('gb-paused'); };
  const sync = () => {
    if (RM.matches) { stop(); root.classList.remove('gb-paused'); render(3 * T + 5.45, 1); return; }   // gerak dikurangi: satu pose statis
    (inView && !document.hidden) ? start() : stop();
  };

  render(0, 1);
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(es => { inView = es[es.length - 1].isIntersecting; sync(); }, {threshold: .05}).observe(root);
  }
  document.addEventListener('visibilitychange', sync);
  if (RM.addEventListener) RM.addEventListener('change', sync);
  sync();
})();
</script>
@endverbatim