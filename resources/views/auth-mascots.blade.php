{{--
    resources/views/auth-mascots.blade.php  (v2)

    Pakai:  @include('auth-mascots')   di login.blade.php dan register.blade.php
            (letakkan setelah @include('auth-glass'))

    Atribut opsional:
      data-mascot-ground     pada elemen garis/footer yang dijadikan pijakan (default: <footer>,
                             .footer, .site-footer; jika tidak ada, garis ink dibuat otomatis)
      data-mascot-skip       pada <form> -> submit native (tanpa fetch), tanpa selebrasi pra-redirect
      data-mascot-redirect   pada <form> -> tujuan bila server membalas 200/204 JSON tanpa redirect
    API:  AuthMascots.success() / .error()
--}}
@verbatim
<style>
/* ───────── Token & tata letak ───────── */
.mascots, .m-ground{
  --m-ink:#1b1b1b; --m-paper:#ffffff; --m-mist:#f1f1ef; --m-accent:#c4f55d;
  --m-ground:rgba(27,27,27,.16); --m-line:#1b1b1b;
}
.mascots{position:fixed; inset:0; z-index:0; pointer-events:none; overflow:hidden; visibility:hidden}
.mascots.ready{visibility:visible}
.mascots[data-mood="error"], .mascots[data-mood="sad"]{--m-accent:#ff6b5e}
.mascots[data-mood="caps"]{--m-accent:#ffc94d}
html[data-theme="dark"] .mascots, html.dark .mascots, html[data-bs-theme="dark"] .mascots,
html[data-theme="dark"] .m-ground, html.dark .m-ground, html[data-bs-theme="dark"] .m-ground{
  --m-mist:#d6d6d1; --m-ground:rgba(0,0,0,.5); --m-line:#f1f1ef;
}
@media (prefers-color-scheme:dark){
  html:not([data-theme="light"]):not(.light) .mascots,
  html:not([data-theme="light"]):not(.light) .m-ground{--m-mist:#d6d6d1; --m-ground:rgba(0,0,0,.5); --m-line:#f1f1ef}
}
.auth{position:relative; z-index:1}

/* Garis pijakan cadangan (hanya dibuat jika halaman tidak punya footer) */
.m-ground{height:3px; margin:28px auto 0; width:calc(100% - 32px); max-width:1200px; border-radius:2px; background:var(--m-line)}

.mascot{position:absolute; left:0; top:0; width:190px; height:228px; pointer-events:auto; cursor:pointer; will-change:transform; -webkit-tap-highlight-color:transparent; transform-origin:95px 219.5px}
.mascot.flying{pointer-events:none}
.mascot svg{display:block; width:100%; height:100%; overflow:visible}
.m-shadow{position:absolute; left:0; top:0; width:84px; height:9px; border-radius:50%; background:var(--m-ground); will-change:transform,opacity}

/* ───────── Bahasa bentuk SVG ───────── */
.mascots .p{fill:var(--m-paper)}
.mascots .m{fill:var(--m-mist)}
.mascots .i{fill:var(--m-ink)}
.mascots .k{stroke:var(--m-ink); stroke-linejoin:round; stroke-linecap:round}
.mascots .acc{fill:var(--m-accent); transition:fill .25s}
.mascots .acc-s{fill:none; stroke:var(--m-accent); stroke-width:3.4; stroke-linecap:round; vector-effect:non-scaling-stroke; transition:stroke .25s}

/* ───────── Percikan selebrasi ───────── */
.spark{opacity:0; transform-box:fill-box; transform-origin:center}
.celebrate .spark{animation:m-spark 1s cubic-bezier(.2,.7,.3,1) both}
.celebrate .spark:nth-child(even){animation-delay:.08s}
@keyframes m-spark{
  0%{opacity:1; transform:translate(0,0) scale(.3) rotate(0)}
  100%{opacity:0; transform:translate(var(--dx),var(--dy)) scale(1) rotate(200deg)}
}
@media (prefers-reduced-motion:reduce){.celebrate .spark{animation-duration:.01s}}
</style>

<div class="mascots" id="mascots" data-mood="idle" aria-hidden="true">
  <div class="m-shadow" id="ilham-shadow"></div>
  <div class="m-shadow" id="bobi-shadow"></div>

  <!-- ILHAM · robot wajah layar, berjalan di tanah -->
  <div class="mascot" id="ilham">
    <svg viewBox="0 0 200 240" focusable="false">
      <g data-p="fig">
        <g data-p="legL" style="transform-origin:86px 204px">
          <rect x="80" y="200" width="12" height="26" class="p k" stroke-width="4"/>
          <rect x="68" y="221" width="28" height="10" rx="4" class="p k" stroke-width="4"/>
        </g>
        <g data-p="legR" style="transform-origin:114px 204px">
          <rect x="108" y="200" width="12" height="26" class="p k" stroke-width="4"/>
          <rect x="104" y="221" width="28" height="10" rx="4" class="p k" stroke-width="4"/>
        </g>
        <g data-p="upper" style="transform-origin:100px 206px">
          <g data-p="armL" style="transform-origin:72px 166px">
            <line x1="72" y1="166" x2="52" y2="188" class="k" stroke-width="4"/>
            <circle cx="50" cy="190" r="6" class="p k" stroke-width="3"/>
          </g>
          <g data-p="armR" style="transform-origin:128px 166px">
            <line x1="128" y1="166" x2="148" y2="188" class="k" stroke-width="4"/>
            <circle cx="150" cy="190" r="6" class="p k" stroke-width="3"/>
          </g>
          <rect x="70" y="150" width="60" height="56" rx="24" class="p k" stroke-width="4"/>
          <rect x="118" y="158" width="8" height="40" rx="4" class="m"/>
          <polygon points="100,167 109,172 109,183 100,188 91,183 91,172" class="acc k" stroke-width="2.5"/>
          <circle cx="72" cy="162" r="4.5" class="acc k" stroke-width="2"/>
          <circle cx="128" cy="162" r="4.5" class="acc k" stroke-width="2"/>
          <rect x="90" y="138" width="20" height="16" class="m k" stroke-width="3"/>
          <g data-p="head" style="transform-origin:100px 142px">
            <rect x="34" y="28" width="132" height="114" rx="28" class="p k" stroke-width="4"/>
            <rect x="150" y="42" width="11" height="86" rx="5" class="m"/>
            <rect x="46" y="40" width="108" height="90" rx="18" class="i"/>
            <g data-p="face" style="transform-origin:100px 85px">
              <g transform="translate(77 58)"><line data-p="browL" x1="-11" y1="0" x2="11" y2="0" class="acc-s" style="stroke-width:4"/></g>
              <g transform="translate(123 58)"><line data-p="browR" x1="-11" y1="0" x2="11" y2="0" class="acc-s" style="stroke-width:4"/></g>
              <g transform="translate(77 82)"><g data-p="eyeL"><rect class="eye-n acc" x="-9" y="-12" width="18" height="24" rx="8"/><path class="eye-h acc-s" d="M-10 5 Q0 -12 10 5" style="display:none;stroke-width:4"/></g></g>
              <g transform="translate(123 82)"><g data-p="eyeR"><rect class="eye-n acc" x="-9" y="-12" width="18" height="24" rx="8"/><path class="eye-h acc-s" d="M-10 5 Q0 -12 10 5" style="display:none;stroke-width:4"/></g></g>
              <g transform="translate(100 112)"><path data-p="mouth" class="acc-s" d="M-12 0 Q0 9 12 0"/></g>
            </g>
            <g transform="translate(160 26)"><g data-p="bang" style="transform:scale(0)"><circle r="13" class="i"/><rect x="-2.6" y="-8" width="5.2" height="10" rx="2.6" class="acc"/><circle cy="6.5" r="3" class="acc"/></g></g>
          </g>
        </g>
      </g>
      <g transform="translate(100 80)">
        <rect class="spark acc k" x="-4" y="-4" width="8" height="8" rx="2" stroke-width="1.5" style="--dx:-80px;--dy:-50px"/>
        <rect class="spark acc k" x="-4" y="-4" width="8" height="8" rx="2" stroke-width="1.5" style="--dx:-55px;--dy:-85px"/>
        <rect class="spark acc k" x="-4" y="-4" width="8" height="8" rx="2" stroke-width="1.5" style="--dx:-20px;--dy:-100px"/>
        <rect class="spark acc k" x="-4" y="-4" width="8" height="8" rx="2" stroke-width="1.5" style="--dx:25px;--dy:-95px"/>
        <rect class="spark acc k" x="-4" y="-4" width="8" height="8" rx="2" stroke-width="1.5" style="--dx:60px;--dy:-80px"/>
        <rect class="spark acc k" x="-4" y="-4" width="8" height="8" rx="2" stroke-width="1.5" style="--dx:85px;--dy:-45px"/>
        <rect class="spark acc k" x="-4" y="-4" width="8" height="8" rx="2" stroke-width="1.5" style="--dx:-95px;--dy:-10px"/>
        <rect class="spark acc k" x="-4" y="-4" width="8" height="8" rx="2" stroke-width="1.5" style="--dx:95px;--dy:-12px"/>
      </g>
    </svg>
  </div>

  <!-- BOBI · robot terbang, mendarat di garis saat idle awal -->
  <div class="mascot" id="bobi">
    <svg viewBox="0 0 200 240" focusable="false">
      <g transform="translate(0 12)">
        <g data-p="float" style="transform-origin:100px 150px">
          <g data-p="flame" style="transform-origin:100px 219px;transform:scaleY(.001)">
            <polygon points="88,219 112,219 100,254" class="acc k" stroke-width="2.5"/>
          </g>
          <ellipse cx="100" cy="214" rx="26" ry="5" class="acc k" stroke-width="2.5"/>
          <g data-p="body" style="transform-origin:100px 206px">
            <ellipse cx="100" cy="160" rx="42" ry="46" class="p k" stroke-width="2.5"/>
            <path d="M62 174 Q100 212 138 174 Q132 203 100 206 Q68 203 62 174Z" class="m"/>
            <circle cx="100" cy="164" r="6" class="acc k" stroke-width="2"/>
          </g>
          <g data-p="armL"><circle cx="50" cy="160" r="10" class="p k" stroke-width="2.5"/></g>
          <g data-p="armR"><circle cx="150" cy="160" r="10" class="p k" stroke-width="2.5"/></g>
          <g data-p="head" style="transform-origin:100px 120px">
            <ellipse cx="100" cy="121" rx="18" ry="5" class="acc k" stroke-width="2"/>
            <rect x="40" y="30" width="120" height="90" rx="44" class="p k" stroke-width="2.5"/>
            <path d="M48 96 Q100 136 152 96 Q150 118 100 120 Q50 118 48 96Z" class="m"/>
            <rect x="56" y="50" width="88" height="54" rx="26" class="i"/>
            <g data-p="face">
              <g transform="translate(80 76)"><g data-p="eyeL"><circle class="eye-n acc" r="8"/><path class="eye-h acc-s" d="M-8 3 Q0 -9 8 3" style="display:none;stroke-width:4"/></g></g>
              <g transform="translate(120 76)"><g data-p="eyeR"><circle class="eye-n acc" r="8"/><path class="eye-h acc-s" d="M-8 3 Q0 -9 8 3" style="display:none;stroke-width:4"/></g></g>
              <g transform="translate(100 93)"><path data-p="mouth" class="acc-s" d="M-7 0 Q0 6 7 0"/></g>
            </g>
            <g transform="translate(152 32)"><g data-p="bang" style="transform:scale(0)"><circle r="13" class="i"/><rect x="-2.6" y="-8" width="5.2" height="10" rx="2.6" class="acc"/><circle cy="6.5" r="3" class="acc"/></g></g>
          </g>
          <g transform="translate(100 70)">
            <rect class="spark acc k" x="-4" y="-4" width="8" height="8" rx="2" stroke-width="1.5" style="--dx:-80px;--dy:-40px"/>
            <rect class="spark acc k" x="-4" y="-4" width="8" height="8" rx="2" stroke-width="1.5" style="--dx:-50px;--dy:-75px"/>
            <rect class="spark acc k" x="-4" y="-4" width="8" height="8" rx="2" stroke-width="1.5" style="--dx:-15px;--dy:-90px"/>
            <rect class="spark acc k" x="-4" y="-4" width="8" height="8" rx="2" stroke-width="1.5" style="--dx:30px;--dy:-85px"/>
            <rect class="spark acc k" x="-4" y="-4" width="8" height="8" rx="2" stroke-width="1.5" style="--dx:62px;--dy:-65px"/>
            <rect class="spark acc k" x="-4" y="-4" width="8" height="8" rx="2" stroke-width="1.5" style="--dx:85px;--dy:-30px"/>
            <rect class="spark acc k" x="-4" y="-4" width="8" height="8" rx="2" stroke-width="1.5" style="--dx:-92px;--dy:0px"/>
            <rect class="spark acc k" x="-4" y="-4" width="8" height="8" rx="2" stroke-width="1.5" style="--dx:92px;--dy:-2px"/>
          </g>
        </g>
      </g>
    </svg>
  </div>
</div>

<script>
(() => {
  'use strict';
  const root = document.getElementById('mascots');
  if (!root) return;

  /* ═════════ 1. Util ═════════ */
  const RM = matchMedia('(prefers-reduced-motion: reduce)');
  const INPUT = '.auth form input:not([type=checkbox]):not([type=hidden]):not([type=submit])';
  const now = () => performance.now();
  const clamp = (v, a, b) => Math.min(b, Math.max(a, v));
  const damp = (r, dt) => 1 - Math.exp(-r * dt);
  const rand = (a, b) => a + Math.random() * (b - a);
  const f2 = v => v.toFixed(2);
  const sleep = ms => new Promise(r => setTimeout(r, ms));

  /** Pegas kenyal: x mengejar t dengan kekakuan k dan redaman c. */
  class Spring {
    constructor(k, c) { this.k = k; this.c = c; this.x = 0; this.v = 0; this.t = 0; }
    step(dt) { this.v += (this.k * (this.t - this.x) - this.c * this.v) * dt; this.x += this.v * dt; return this.x; }
    kick(v) { if (!RM.matches) this.v += v; }
  }

  /* ═════════ 2. Dunia: garis pijakan, kartu form, jalur gerak ═════════ */
  const World = {
    ground: 0, ceil: 0, gDoc: 0, live: false, el: null, shell: null, hd: null,
    cardL: 0, cardR: 0, midX: 0, boxL: 0, boxR: 0, sc: 1, fits: false, clip: '', radius: '0px',
    pick() {
      for (const s of ['[data-mascot-ground]', 'footer', '.footer', '.site-footer']) {
        const e = document.querySelector(s);
        if (e && e.getClientRects().length) return e;
      }
      let line = document.querySelector('.m-ground');
      if (!line) {
        line = document.createElement('div'); line.className = 'm-ground';
        const a = document.querySelector('.auth');
        if (a) a.insertAdjacentElement('afterend', line); else document.body.appendChild(line);
      }
      return line;
    },
    /** Kotak putih tempat maskot tinggal: [data-mascot-box] atau .shell; bila tidak ada, seluruh layar. */
    box() {
      for (const s of ['[data-mascot-box]', '.shell']) {
        const e = document.querySelector(s);
        if (e && e.getClientRects().length) return e;
      }
      return null;
    },
    measure() {
      this.el = this.pick();
      const pos = getComputedStyle(this.el).position;
      this.live = pos === 'fixed' || pos === 'sticky';
      this.gDoc = this.el.getBoundingClientRect().top + scrollY;
      const card = document.querySelector('.auth-card') || document.querySelector('.auth .card') || document.querySelector('.auth');   // paling spesifik dulu (querySelector gabungan memilih induk lebih dulu)
      const r = card ? card.getBoundingClientRect() : {left: innerWidth / 2 - 220, right: innerWidth / 2 + 220};
      this.cardL = r.left; this.cardR = r.right; this.midX = (r.left + r.right) / 2;

      /* Batas luar = tepi kotak putih. Jalur kiri (Ilham) = tepi kotak → tepi kartu; jalur kanan (Bobi) = tepi kartu → tepi kotak. */
      this.shell = this.box();
      this.hd = this.shell && (this.shell.querySelector('[data-mascot-top], header, .topbar, .site-header, nav') || null);
      if (this.hd && this.hd.closest('.auth')) this.hd = null;
      const b = this.shell ? this.shell.getBoundingClientRect() : {left: 0, right: innerWidth};
      this.boxL = Math.max(0, b.left); this.boxR = Math.min(innerWidth, b.right);
      this.radius = this.shell ? getComputedStyle(this.shell).borderTopLeftRadius : '0px';
      /* Jalur sempit → maskot mengecil (menempel di kaki) agar tetap muat; terlalu sempit → disembunyikan. */
      const lane = Math.min(this.cardL - this.boxL, this.boxR - this.cardR);
      this.sc = clamp(lane / 220, 0, 1);
      this.fits = this.sc >= .6;
      this.update();
    },
    /** Dipanggil tiap frame: pijakan, langit-langit, dan potongan (clip) mengikuti kotak putih saat digulir. */
    update() {
      const top = this.live ? this.el.getBoundingClientRect().top : this.gDoc - scrollY;
      let ceil = 0, floor = innerHeight - 24, clip = '';
      if (this.shell) {
        const b = this.shell.getBoundingClientRect();
        ceil = Math.max(0, b.top) + 8;
        floor = Math.min(floor, b.bottom - 10);
        if (this.hd) {                                             // jangan terbang menimpa header/navigasi di dalam kotak
          const h = this.hd.getBoundingClientRect();
          if (h.bottom > b.top && h.bottom - b.top < 220) ceil = Math.max(ceil, h.bottom + 6);
        }
        const rt = b.top > 0 ? this.radius : '0px', rb = b.bottom < innerHeight ? this.radius : '0px';
        clip = `inset(${f2(Math.max(0, b.top))}px ${f2(innerWidth - this.boxR)}px ${f2(Math.max(0, innerHeight - b.bottom))}px ${f2(this.boxL)}px round ${rt} ${rt} ${rb} ${rb})`;
      }
      this.ground = Math.min(top, floor);
      this.ceil = ceil;
      if (clip !== this.clip) { this.clip = clip; root.style.clipPath = clip; }   // jaring pengaman: tidak ada piksel di luar kotak
    }
  };

  /* ═════════ 3. Ekspresi & suasana ═════════ */
  const EX = {
    idle:       {open: 1,    sx: 1,    mouth: .7,   brow: 0,  arms: 0, bang: 0, happy: 0},
    focus:      {open: 1.12, sx: 1.08, mouth: .9,   brow: -3, arms: 0, bang: 0, happy: 0},
    read:       {open: .72,  sx: 1,    mouth: .15,  brow: 4,  arms: 0, bang: 0, happy: 0},
    guard:      {open: .5,   sx: 1,    mouth: 0,    brow: 7,  arms: 0, bang: 0, happy: 0},
    peek:       {open: 1.3,  sx: 1.2,  mouth: -.2,  brow: -5, arms: 0, bang: 0, happy: 0},
    processing: {open: .6,   sx: 1,    mouth: .1,   brow: 3,  arms: 0, bang: 0, happy: 0},
    error:      {open: 1.35, sx: 1.2,  mouth: -1,   brow: -8, arms: 0, bang: 1, happy: 0},
    sad:        {open: .82,  sx: 1,    mouth: -1.1, brow: -9, arms: 0, bang: 0, happy: 0},
    caps:       {open: 1.25, sx: 1.1,  mouth: -.5,  brow: -6, arms: 0, bang: 1, happy: 0},
    success:    {open: 1,    sx: 1,    mouth: 1.4,  brow: -4, arms: 1, bang: 0, happy: 1},
    giggle:     {open: 1,    sx: 1,    mouth: 1.2,  brow: -3, arms: 0, bang: 0, happy: 1},
    sleepy:     {open: .18,  sx: 1,    mouth: .2,   brow: 0,  arms: 0, bang: 0, happy: 0}
  };

  const ctx = {
    t: 0, mood: 'idle', typing: 0, caps: false, processing: false, submitting: false,
    last: now(), successAt: 0, focus: null, peek: null,
    ptr: {x: 0, y: 0, inside: false},
    until: {success: 0, error: 0, sad: 0},
    get zone() { return this.ptr.inside ? (this.ptr.x < World.midX ? 'left' : 'right') : null; },
    get point() { return this.focus || (this.peek && now() < this.peek.until ? this.peek : null); }
  };
  const poke = () => { ctx.last = now(); };

  /** Satu-satunya tempat suasana ditentukan. Selebrasi hanya dari `until.success`. */
  function decideMood(n) {
    const u = ctx.until;
    if (n < u.success) return 'success';
    if (n < u.error) return 'error';
    if (n < u.sad) return 'sad';
    if (ctx.processing) return 'processing';
    if (ctx.caps && ctx.focus) return 'caps';
    if (ctx.peek && n < ctx.peek.until) return 'peek';
    if (ctx.focus) {
      if (ctx.typing <= .05) return 'focus';
      return ctx.focus.el.type === 'password' ? 'guard' : 'read';
    }
    return n - ctx.last > 25000 ? 'sleepy' : 'idle';
  }

  /* ═════════ 4. Basis maskot: tatapan, wajah, penempatan ═════════ */
  class Mascot {
    constructor(el, shadow, side, phase) {
      this.el = el; this.sh = shadow; this.side = side; this.phase = phase;
      this.q = n => el.querySelector('[data-p="' + n + '"]');
      this.ex = {open: 1, sx: 1, mouth: .7, brow: 0, arms: 0, bang: 0};
      this.gx = new Spring(240, 22); this.gy = new Spring(240, 22);
      this.nod = new Spring(220, 14);
      this.eyes = [this.q('eyeL'), this.q('eyeR')];
      this.eyeN = el.querySelectorAll('.eye-n'); this.eyeH = el.querySelectorAll('.eye-h');
      this.mouth = this.q('mouth'); this.bang = this.q('bang');
      this.browL = this.q('browL'); this.browR = this.q('browR');
      this.armL = this.q('armL'); this.armR = this.q('armR');
      this.happy = false; this.giggleUntil = 0; this.ready = false;
      this.ax = 0; this.ay = 0; this.half = 66; this.rise = 190;
      this.nextBlink = now() + rand(1500, 3500); this.blinkAt = 0;
      this.wander = {x: 0, y: 0, next: 0};
      el.addEventListener('click', () => this.boop());
    }
    boop() { this.giggleUntil = now() + 800; this.nod.kick(300); poke(); }

    /** Rentang sumbu X titik kontak yang aman: di dalam kotak putih dan di luar kartu form (jejak diskalakan World.sc). */
    lane() {
      const p = this.half * World.sc;
      return this.side === 'left'
        ? {min: World.boxL + p, max: Math.max(World.boxL + p, World.cardL - p - 6)}
        : {min: Math.min(World.boxR - p, World.cardR + p + 6), max: World.boxR - p};
    }

    /** Tatapan presisi: arah (dx,dy) dari pusat kepala ke target, jenuh pada jarak 140px. */
    gaze(dt, n) {
      const mood = ctx.mood;
      if (mood === 'processing') { this.gx.t = Math.sin(ctx.t * 4.5) * .9; this.gy.t = -.05; }
      else if (mood === 'sleepy') { this.gx.t = 0; this.gy.t = .9; }
      else {
        const p = ctx.point, tgt = p ? p : (ctx.zone === this.side ? ctx.ptr : null);
        if (tgt) {
          const px = tgt.px !== undefined ? tgt.px : tgt.x, py = tgt.py !== undefined ? tgt.py : tgt.y;
          const dx = px - this.ax, dy = py - (this.ay - 138 * World.sc), d = Math.hypot(dx, dy) || 1, m = Math.min(1, d / 140);
          this.gx.t = dx / d * m; this.gy.t = dy / d * m;
          if (ctx.typing > .05) this.gx.t = clamp(this.gx.t + Math.sin(ctx.t * 26) * .1 * ctx.typing, -1, 1);
        } else {
          const w = this.wander;
          if (n > w.next) {
            const toForm = Math.random() < .35 ? (this.side === 'left' ? .55 : -.55) : 0;
            w.x = clamp(rand(-.5, .5) + toForm, -1, 1); w.y = rand(-.3, .3); w.next = n + rand(2500, 6000);
          }
          this.gx.t = w.x; this.gy.t = w.y;
        }
      }
      this.gx.step(dt); this.gy.step(dt);
    }

    /** Mata, mulut, alis, tanda seru, kedip. ox/oy = jangkauan pupil. */
    express(dt, n, mood, ox, oy) {
      const T = EX[mood], e = this.ex, k = damp(14, dt);
      for (const key in e) e[key] += (T[key] - e[key]) * k;
      const h = !!T.happy;
      if (h !== this.happy) {
        this.happy = h;
        this.eyeN.forEach(x => x.style.display = h ? 'none' : '');
        this.eyeH.forEach(x => x.style.display = h ? '' : 'none');
      }
      let b = 1;
      if (!h && mood !== 'sleepy') {
        if (n > this.nextBlink) {
          this.blinkAt = n;
          this.nextBlink = n + (mood === 'processing' ? rand(380, 520) : rand(2200, 5200));
        }
        const p = (n - this.blinkAt) / 150;
        if (p >= 0 && p < 1) b = 1 - Math.sin(p * Math.PI) * .92;
      }
      const sy = Math.max(.06, e.open * b), tx = this.gx.x * ox, ty = this.gy.x * oy;
      for (const g of this.eyes) g.style.transform = `translate(${f2(tx)}px,${f2(ty)}px) scale(${f2(e.sx)},${sy.toFixed(3)})`;
      this.mouth.style.transform = `scale(${(1 + Math.abs(e.mouth) * .15).toFixed(3)},${e.mouth.toFixed(3)})`;
      this.bang.style.transform = `scale(${Math.max(e.bang, .001).toFixed(3)})`;
      if (this.browL) {
        this.browL.style.transform = `translateY(${f2(e.brow * .6)}px) rotate(${f2(e.brow)}deg)`;
        this.browR.style.transform = `translateY(${f2(e.brow * .6)}px) rotate(${f2(-e.brow)}deg)`;
      }
    }

    /** Titik kontak (kaki/pad) ax,ay → posisi viewport. Bayangan tetap di garis. */
    place(shScale, shAlpha) {
      const sc = World.sc;                                   // pivot skala = titik kontak (transform-origin di CSS)
      this.el.style.transform = `translate3d(${f2(this.ax - 95)}px,${f2(this.ay - 219.5)}px,0) scale(${f2(sc)})`;
      this.sh.style.transform = `translate3d(${f2(this.ax - 42)}px,${f2(World.ground - 7)}px,0) scale(${f2(shScale * sc)},${f2(sc)})`;
      this.sh.style.opacity = shAlpha.toFixed(2);
    }
  }

  /* ═════════ 5. Ilham — berjalan di tanah, kepala mendongak/menoleh ═════════ */
  class Ilham extends Mascot {
    constructor(el, sh) {
      super(el, sh, 'left', 0);
      this.half = 92; this.rise = 200;                       // jejak horizontal & tinggi kepala dari kaki (px, skala 1)
      this.fig = this.q('fig'); this.upper = this.q('upper'); this.head = this.q('head'); this.face = this.q('face');
      this.legL = this.q('legL'); this.legR = this.q('legR');
      this.lean = new Spring(120, 12); this.str = new Spring(120, 12); this.hop = new Spring(170, 10);
      this.vx = 0; this.amp = 0; this.stride = 0; this.shakeT = 0; this.beat = -1;
      this.stroll = {x: 0, next: 0};
    }
    onError() { this.shakeT = .6; this.hop.kick(-110); }
    onSuccess() { this.beat = -1; }
    update(dt, n, mood) {
      const t = ctx.t, calm = RM.matches ? 0 : 1, sc = World.sc, {min, max} = this.lane();
      if (!this.ready) { this.ax = max; this.ready = true; this.stroll.next = n + 2500; }
      this.ay = World.ground;

      /* tujuan jalan: sejajar kursor (zona kiri) · dekat kartu saat menyimak · jalan santai saat diam */
      let target = this.ax, lt = 0, st = 0, vmax = 300;
      if (mood === 'success') { /* melompat di tempat */ }
      else if (mood === 'sleepy') st = -.6;
      else if (ctx.point || mood === 'processing') { target = max; lt = 4; st = -.2; vmax = 240; }
      else if (ctx.zone === 'left') {
        target = ctx.ptr.x;                                                    // berjalan sejajar koordinat X kursor
        st = clamp((this.ay - 138 * sc - ctx.ptr.y) / (innerHeight * .6), -1, 1);
        lt = clamp((ctx.ptr.x - this.ax) / 320, -1, 1) * 2;
      } else if (mood === 'idle') {
        if (n > this.stroll.next) { this.stroll.x = rand(min, max); this.stroll.next = n + rand(4500, 9000); }
        target = this.stroll.x; vmax = 110;                                    // mondar-mandir pelan di jalurnya
      }
      target = clamp(target, min, max);

      /* langkah kaki: kecepatan terbatas, fase melangkah mengikuti jarak tempuh; tidak pernah keluar dari jalur */
      const err = target - this.ax;
      const want = Math.abs(err) < 6 ? 0 : clamp(err * 3.4, -vmax, vmax);
      this.vx += (want - this.vx) * damp(9, dt);
      const nx = this.ax + this.vx * dt;
      this.ax = clamp(nx, min, max);
      if (this.ax !== nx) this.vx = 0;                                         // membentur tepi jalur
      this.amp += (clamp(Math.abs(this.vx) / 70, 0, 1) - this.amp) * damp(12, dt);
      this.stride += this.vx * dt * .09;
      const s = Math.sin(this.stride), amp = this.amp;
      this.legL.style.transform = `translateY(${f2(-Math.max(0, s) * 4 * amp)}px) rotate(${f2(s * 30 * amp)}deg)`;
      this.legR.style.transform = `translateY(${f2(-Math.max(0, -s) * 4 * amp)}px) rotate(${f2(-s * 30 * amp)}deg)`;

      /* lompatan bersama saat sukses */
      if (mood === 'success') {
        const b = Math.floor((n - ctx.successAt) / 620);
        if (b !== this.beat) { this.beat = b; this.hop.kick(-330); }
      }
      const hop = this.hop.step(dt);
      this.fig.style.transform = `translateY(${f2(hop - Math.abs(s) * 3 * amp * (calm || .5))}px)`;

      /* badan: condong ke arah jalan + memanjang/merunduk sesuai tinggi kursor */
      this.lean.t = lt + clamp(this.vx / 230, -1, 1) * 4; this.str.t = st;
      const L = this.lean.step(dt), S = this.str.step(dt);
      const breath = Math.sin(t * 1.6 + this.phase) * .012 * calm;
      let shake = 0;
      if (this.shakeT > 0) { this.shakeT -= dt; shake = Math.sin(t * 60) * 3.5 * Math.max(0, this.shakeT / .6) * calm; }
      this.upper.style.transform = `rotate(${f2(L + shake)}deg) scale(${(1 - S * .015).toFixed(4)},${(1 + S * .045 + breath).toFixed(4)})`;

      /* kepala: mendongak, menoleh, wajah digital bergeser (tetap fokus ke kursor selagi berjalan) */
      const gx = this.gx.x, gy = this.gy.x;
      this.head.style.transform = `translateY(${f2(gy * 3 + this.nod.step(dt))}px) rotate(${f2(gx * 8 - this.vx * .006)}deg) scale(${(1 - Math.abs(gx) * .05).toFixed(3)},1)`;
      this.face.style.transform = `translate(${f2(gx * 14)}px,${f2(gy * 11)}px) skewX(${f2(-gx * 6)}deg)`;

      /* lengan: ayun jalan berlawanan dengan kaki, ketik, angkat saat sukses */
      const a = this.ex.arms, sw = Math.sin(t * 1.6 + this.phase + 1) * 2 * calm, wig = Math.sin(t * 30) * ctx.typing * 5 * calm, walk = -s * 20 * amp;
      this.armL.style.transform = `rotate(${f2(a * 100 + sw + wig + walk)}deg)`;
      this.armR.style.transform = `rotate(${f2(-a * 100 - sw - wig + walk)}deg)`;

      this.express(dt, n, mood, 4, 3);
      this.place(clamp(1 + hop / 120, .6, 1), 1);
    }
  }

  /* ═════════ 6. Bobi — mendarat di garis, terbang dengan pegas kenyal ═════════ */
  class Bobi extends Mascot {
    constructor(el, sh) {
      super(el, sh, 'right', Math.PI);
      this.half = 104; this.rise = 198;                       // jejak horizontal (termasuk miring badan) & tinggi kepala dari pad (px, skala 1)
      this.float = this.q('float'); this.head = this.q('head'); this.body = this.q('body'); this.flame = this.q('flame');
      this.sx = new Spring(38, 6.5); this.hgt = new Spring(38, 6.5);
      this.spin = new Spring(90, 7); this.hr = new Spring(150, 12);
      this.axs = new Spring(60, 8); this.ays = new Spring(60, 8);
      this.mode = 'land'; this.hoverUntil = 0; this.thrust = 0; this.fl = false; this.beat = -1;
    }
    onError() { this.sx.kick(Math.random() < .5 ? 520 : -520); this.spin.kick(320); }
    onSuccess() { this.beat = -1; this.spin.kick(640); }
    update(dt, n, mood) {
      const t = ctx.t, calm = RM.matches ? 0 : 1, sc = World.sc;
      const {min: xMin, max: xMax} = this.lane();                        // jalur kanan: tepi kartu → tepi kotak putih
      const home = Math.min(xMin + 8, xMax);
      const hMax = Math.max(0, World.ground - (World.ceil + this.rise * sc));   // naik setinggi-tingginya: kepala menyentuh langit-langit kotak
      if (!this.ready) { this.sx.x = this.sx.t = home; this.ready = true; }

      /* mode: land (idle awal) · hover · attend (menyimak form) · fly (ikut kursor, bebas X dan Y) · party */
      let mode;
      if (mood === 'success') mode = 'party';
      else if (ctx.point || mood === 'processing') mode = 'attend';
      else if (ctx.zone === 'right') mode = 'fly';
      else mode = n < this.hoverUntil ? 'hover' : 'land';
      if (mode !== 'land' && mode !== 'hover') this.hoverUntil = n + 6000;
      if (mode === 'fly' && this.mode !== 'fly' && this.hgt.x < 8) this.hgt.kick(420);   // lepas landas
      this.mode = mode;

      let tx = home, th = 0, thr = 0;
      if (mode === 'fly') {
        /* mengikuti kursor di kedua sumbu; ayunan lembut membuatnya terasa melayang. Batas dipaksa di bawah. */
        const sway = calm * (Math.sin(t * 1.3 + this.phase) * 12 + Math.sin(t * 2.3) * 5);
        tx = ctx.ptr.x + sway;
        th = World.ground - (ctx.ptr.y + 66 * sc) + calm * Math.sin(t * 1.7 + this.phase) * 9;
        thr = .75;
      } else if (mode === 'attend') { th = 26 + Math.sin(t * 1.5) * 3 * calm; thr = .45; }
      else if (mode === 'party')    { tx = xMin + (xMax - xMin) * .35; th = 60; thr = 1; }   // berputar agak jauh dari kartu
      else if (mode === 'hover')    { th = 16 + Math.sin(t * 1.3) * 5 * calm; thr = .4; }
      tx = clamp(tx, xMin, xMax); th = clamp(th, 0, hMax);

      if (mode === 'party') {
        const b = Math.floor((n - ctx.successAt) / 620);
        if (b !== this.beat) { this.beat = b; this.hgt.kick(240); }
      }

      /* pegas kenyal di kedua sumbu, lalu dinding keras: tak pernah melewati jalur walau pegas overshoot */
      this.sx.t = tx; this.hgt.t = th;
      let h = this.hgt.step(dt);
      if (h < 0) { this.hgt.x = h = 0; this.hgt.v = Math.abs(this.hgt.v) * .25; }                  // pantulan mendarat
      else if (h > hMax) { this.hgt.x = h = hMax; this.hgt.v = -Math.abs(this.hgt.v) * .3; }       // pantulan langit-langit
      if (mode === 'land' && h < 1.5 && Math.abs(this.hgt.v) < 25) { this.hgt.x = h = 0; this.hgt.v = 0; }
      let x = this.sx.step(dt);
      if (x < xMin) { this.sx.x = x = xMin; this.sx.v = Math.abs(this.sx.v) * .3; }               // pantulan tepi kartu
      else if (x > xMax) { this.sx.x = x = xMax; this.sx.v = -Math.abs(this.sx.v) * .3; }          // pantulan tepi kotak
      this.ax = x; this.ay = World.ground - h;
      const vx = this.sx.v, vh = this.hgt.v;

      this.thrust += ((h > 3 ? Math.max(thr, .3) : thr) + clamp(Math.abs(vx) / 600 + Math.abs(vh) / 500, 0, .4) - this.thrust) * damp(8, dt);
      this.flame.style.transform = `scaleY(${Math.max(.001, this.thrust * (1 + Math.sin(t * 40) * .1 * calm)).toFixed(3)})`;

      /* seluruh badan: miring organik ke arah gerak, memanjang saat bergerak vertikal */
      const roll = clamp(vx * .025, -12, 12), stretch = clamp(Math.abs(vh) * .00022, 0, .08);
      this.float.style.transform = `rotate(${f2(roll + this.spin.step(dt))}deg) scale(${(1 - stretch * .5).toFixed(3)},${(1 + stretch).toFixed(3)})`;
      this.hr.t = this.gx.x * 10 + roll * .3;
      this.head.style.transform = `translateY(${f2(this.nod.step(dt))}px) rotate(${f2(this.hr.step(dt))}deg)`;
      this.body.style.transform = `scaleY(${(1 + Math.sin(t * 1.9 + this.phase) * .014 * calm).toFixed(4)})`;

      /* tangan terlepas: tertinggal oleh gerak, terangkat saat sukses */
      this.axs.t = clamp(-vx * .03, -14, 14); this.ays.t = clamp(vh * .02, -12, 12);
      const a = this.ex.arms, sw = Math.sin(t * 1.7 + this.phase + 1) * 2 * calm, wig = Math.sin(t * 30) * ctx.typing * 3 * calm;
      const ox = this.axs.step(dt), oy = this.ays.step(dt);
      this.armL.style.transform = `translate(${f2(ox - a * 8)}px,${f2(oy - a * 46 + sw + wig)}px)`;
      this.armR.style.transform = `translate(${f2(ox + a * 8)}px,${f2(oy - a * 46 - sw - wig)}px)`;

      const fl = h > 10 || mode === 'fly';
      if (fl !== this.fl) { this.fl = fl; this.el.classList.toggle('flying', fl); }

      this.express(dt, n, mood, 8, 6.5);
      this.place(clamp(1 - h / 380, .35, 1), clamp(1 - h / 420, .25, 1));
    }
  }

  const ilham = new Ilham(document.getElementById('ilham'), document.getElementById('ilham-shadow'));
  const bobi = new Bobi(document.getElementById('bobi'), document.getElementById('bobi-shadow'));
  const mascots = [ilham, bobi];

  /* ═════════ 7. Loop 60 FPS (hanya transform/opacity) ═════════ */
  let running = false, prev = 0;
  function tick(ts) {
    if (!World.fits) { running = false; root.classList.remove('ready'); return; }
    const dt = Math.min((ts - (prev || ts)) / 1000, .033); prev = ts; ctx.t += dt;
    ctx.typing = Math.max(0, ctx.typing - dt * 1.8);
    World.update();
    const mood = decideMood(ts);
    if (mood !== ctx.mood) onMood(mood);
    const solo = mood === 'error' || mood === 'sad' || mood === 'success' || mood === 'caps' || mood === 'processing';
    for (const m of mascots) {
      const mm = (!solo && ts < m.giggleUntil) ? 'giggle' : mood;
      m.gaze(dt, ts); m.update(dt, ts, mm);
    }
    root.classList.add('ready');
    requestAnimationFrame(tick);
  }
  function run() { if (running || !World.fits) return; running = true; prev = 0; requestAnimationFrame(tick); }

  function onMood(mood) {
    const was = ctx.mood; ctx.mood = mood; root.dataset.mood = mood;
    if (mood === 'success') ctx.successAt = now();
    mascots.forEach(m => {
      m.el.classList.toggle('celebrate', mood === 'success');
      if (mood === 'success') m.onSuccess();
      if (mood === 'error') m.onError();
      if (mood === 'caps') m.nod.kick(-100);
      if (mood === 'focus' && was === 'idle') m.nod.kick(-140);
    });
  }

  /* ═════════ 8. Pemicu: pointer & formulir ═════════ */
  addEventListener('pointermove', e => { ctx.ptr.x = e.clientX; ctx.ptr.y = e.clientY; ctx.ptr.inside = true; poke(); }, {passive: true});
  document.documentElement.addEventListener('mouseleave', () => { ctx.ptr.inside = false; });
  document.documentElement.addEventListener('mouseenter', () => { ctx.ptr.inside = true; });

  function focusPoint(el) {
    const r = el.getBoundingClientRect();
    return {el, px: r.left + clamp(16 + el.value.length * 8, 16, Math.max(16, r.width - 44)), py: r.top + r.height / 2};
  }
  document.addEventListener('focusin', e => {
    if (e.target.matches && e.target.matches(INPUT)) { ctx.focus = focusPoint(e.target); poke(); }
  });
  document.addEventListener('focusout', () => setTimeout(() => {
    const a = document.activeElement;
    if (!(a && a.matches && a.matches(INPUT))) { ctx.focus = null; ctx.caps = false; }
  }, 0));
  document.addEventListener('input', e => {
    if (!(e.target.matches && e.target.matches(INPUT))) return;
    ctx.focus = focusPoint(e.target); ctx.typing = 1; poke();
    ctx.until.error = ctx.until.sad = 0;                       // mengetik lagi = tenang kembali, tanpa selebrasi
    mascots.forEach(m => m.nod.kick(110));
  });
  ['keydown', 'keyup'].forEach(ev => document.addEventListener(ev, e => {
    poke();
    if (ctx.focus && ctx.focus.el.type === 'password' && e.getModifierState) ctx.caps = e.getModifierState('CapsLock');
  }));
  addEventListener('scroll', () => { if (ctx.focus) ctx.focus = focusPoint(ctx.focus.el); }, {passive: true});

  document.addEventListener('click', e => {
    const b = e.target.closest && e.target.closest('.pw-toggle');
    if (!b) return;
    setTimeout(() => {
      const inp = document.getElementById(b.dataset.target);
      if (!inp) return;
      if (inp.type === 'text') {
        const r = inp.getBoundingClientRect();
        ctx.peek = {px: r.left + r.width / 2, py: r.top + r.height / 2, until: now() + 1500};
      } else ctx.peek = null;
      poke();
    }, 0);
  });
  document.addEventListener('change', e => {
    if (e.target.matches && e.target.matches('.consent input[type=checkbox]') && e.target.checked) mascots.forEach(m => m.nod.kick(200));
  });

  /* ═════════ 9. Alur autentikasi: memproses → error ATAU sukses ═════════ */
  const trigger = {
    error()   { const n = now(); ctx.until.error = n + 900; ctx.until.sad = n + 3600; poke(); },
    success() { ctx.until.success = now() + 2000; poke(); }
  };
  let lastInvalid = 0;
  document.addEventListener('invalid', () => {
    const n = now();
    if (n - lastInvalid > 1000) { lastInvalid = n; trigger.error(); }
  }, true);

  const MSG = {
    server: 'Terjadi kesalahan pada server. Coba lagi sebentar lagi.',
    generic: 'Periksa kembali data Anda lalu coba lagi.'
  };
  const setBusy = (f, on) => {
    f.setAttribute('aria-busy', on ? 'true' : 'false');
    f.querySelectorAll('button[type=submit]:not([name])').forEach(b => { b.disabled = on; });
  };
  function showErrors(f, list) {
    let box = f.querySelector('.mascot-errors');
    if (!box) {
      box = document.createElement('div'); box.className = 'auth-errors mascot-errors'; box.setAttribute('role', 'alert');
      f.prepend(box);
    }
    box.textContent = '';
    list.forEach(m => { const d = document.createElement('div'); d.className = 'error'; d.textContent = m; box.appendChild(d); });
  }
  const clearErrors = f => { const b = f.querySelector('.mascot-errors'); if (b) b.textContent = ''; };
  const fromJson = d => {
    if (!d) return null;
    const m = d.errors ? Object.values(d.errors).flat() : (d.message ? [d.message] : []);
    return m.length ? m : null;
  };
  function scrape(html) {
    if (!html) return null;
    const d = new DOMParser().parseFromString(html, 'text/html');
    const m = [...d.querySelectorAll('.auth .error, .auth-errors .error')].map(e => e.textContent.trim()).filter(Boolean);
    return m.length ? [...new Set(m)] : null;
  }

  /** Kirim form via fetch lalu simpulkan hasil: {ok,url} | {ok:false,msgs} | {native:true}. */
  async function send(f) {
    const res = await fetch(f.action, {
      method: (f.method || 'POST').toUpperCase(), body: new FormData(f), credentials: 'same-origin', redirect: 'follow',
      headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json, text/html;q=0.8'}
    });
    if (res.status === 419) return {native: true};
    const isJson = (res.headers.get('content-type') || '').includes('json');
    const data = isJson ? await res.json().catch(() => null) : null;
    if (!res.ok) return {ok: false, msgs: fromJson(data) || [res.status >= 500 ? MSG.server : MSG.generic]};
    if (res.status === 204) return {ok: true, url: f.dataset.mascotRedirect || '/'};
    const dest = data && (data.redirect || data.redirect_url || data.url);
    if (dest) return {ok: true, url: dest};
    const html = isJson ? '' : await res.text();
    if (res.redirected) {
      if (new URL(res.url, location.href).pathname !== location.pathname) return {ok: true, url: res.url};
      return {ok: false, msgs: scrape(html) || [MSG.generic]};       // dikembalikan ke halaman ini = gagal
    }
    if (data) return {ok: true, url: f.dataset.mascotRedirect || '/'};
    const msgs = scrape(html);
    return msgs ? {ok: false, msgs} : {ok: true, url: res.url};
  }

  document.addEventListener('submit', async e => {
    const f = e.target;
    if (!(f.matches && f.matches('.auth form')) || f.hasAttribute('data-mascot-skip') || ctx.submitting || !World.fits || !window.fetch) return;
    const s = e.submitter;
    if (s && (s.hasAttribute('formaction') || s.name)) return;                 // tombol Google dll → native
    if (/google/i.test(f.getAttribute('action') || '')) return;

    e.preventDefault();
    ctx.submitting = ctx.processing = true; ctx.until.error = ctx.until.sad = 0;
    clearErrors(f); setBusy(f, true); poke();

    let out;
    try { [out] = await Promise.all([send(f), sleep(700)]); } catch (_) { out = {native: true}; }
    ctx.processing = false;

    if (out.native) { setBusy(f, false); ctx.submitting = false; f.submit(); return; }
    if (out.ok) {
      trigger.success();
      await sleep(RM.matches ? 300 : 1700);                                    // selebrasi dulu, baru pindah halaman
      location.assign(out.url);
    } else {
      showErrors(f, out.msgs); setBusy(f, false); ctx.submitting = false; trigger.error();
    }
  });
  addEventListener('pageshow', () => { ctx.submitting = ctx.processing = false; ctx.until.success = 0; });

  /* ═════════ 10. Siklus hidup ═════════ */
  let rq = 0;
  const remeasure = () => { cancelAnimationFrame(rq); rq = requestAnimationFrame(() => { World.measure(); run(); }); };

  function boot() {
    World.measure();
    if (document.querySelector('.auth .error')) setTimeout(trigger.error, 600);   // error dari server (Laravel $errors)
    if ('ResizeObserver' in window) new ResizeObserver(remeasure).observe(document.documentElement);
    addEventListener('resize', remeasure);
    addEventListener('load', remeasure);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(remeasure);
    document.addEventListener('visibilitychange', () => { prev = 0; });
    run();
  }
  window.AuthMascots = trigger;
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
</script>
@endverbatim
