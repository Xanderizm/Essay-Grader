@extends('layout')
@section('title', 'Monitoring sistem · Essay Grader')

@push('styles')
    @include('bento-assets')
    @include('bento-glass')
    @include('bento-responsive')
    <style>
    /* ===== Grid bento 10 kolom (CSS murni, tidak bergantung Tailwind). gap 20px = gap-5 ===== */
    .adm-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; }
    .adm-cols3 { display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; }
    .adm-cols3.mid { align-items: center; }
    .adm-card { display: flex; flex-direction: column; }
    .adm-hd { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 12px; }
    .adm-fcol { display: flex; flex-direction: column; flex: 1 1 auto; }
    .adm-fcol.end { justify-content: flex-end; }
    .adm-bar { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 12px; margin-top: auto; padding-top: 14px; }
    .adm-btns { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
    .adm-btns form { margin: 0; }
    .adm-btns .btn-pill { white-space: nowrap; }

    /* ===== Kartu Sistem & integrasi ===== */
    .adm-sys { display: flex; flex-direction: column; justify-content: space-between; gap: 16px; flex: 1 1 auto; }
    .adm-sys > div { padding-bottom: 14px; border-bottom: 1px solid var(--a-line); }
    .adm-sys > div:last-child { padding-bottom: 0; border-bottom: 0; }
    .adm-sys .hd { display: flex; align-items: center; justify-content: space-between; gap: 10px; font: 600 13px var(--mono); }
    .adm-sys .adm-sub { margin: 6px 0 0; }
    .adm-meter { height: 8px; border-radius: 99px; background: var(--a-line); overflow: hidden; margin-top: 10px; }
    .adm-meter i { display: block; height: 100%; border-radius: 99px; background: var(--a-lime); }
    .adm-sys form { margin: 10px 0 0; }
    
    /* Mobile: Tes + Nonaktifkan berdampingan (2 kolom), Hapus lebar penuh di bawahnya */
    @media (max-width: 767px) {
        .adm .adm-bar { flex-direction: column; align-items: stretch; gap: 12px; }
        .adm .adm-bar > .adm-sub { text-align: left; }
        .adm .adm-btns { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; width: 100%; }
        .adm .adm-btns form { width: 100%; min-width: 0; flex: none; }
        .adm .adm-btns form:last-child { grid-column: 1 / -1; }
        .adm .adm-btns .btn-pill { width: 100%; min-height: 46px; padding: 12px 10px; }
    }
    /* Layar sangat sempit: semua tombol 1 kolom */
    @media (max-width: 340px) {
        .adm .adm-btns { grid-template-columns: minmax(0, 1fr); }
    }
    @media (min-width: 768px) {
        .adm-grid { grid-template-columns: repeat(10, minmax(0, 1fr)); }
        .adm-grid > .adm-c4 { grid-column: span 4 / span 4; }
        .adm-grid > .adm-c6 { grid-column: span 6 / span 6; }
        .adm-grid > .adm-c10 { grid-column: span 10 / span 10; }
        .adm-cols3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .adm-cols3 > .adm-span2 { grid-column: span 2 / span 2; }
    }

    /* ===== Monitoring user: accordion di mobile ===== */
    .adm-ulist { display: none; }
    .adm .adm-ui > button { display: flex; width: 100%; align-items: center; justify-content: space-between; gap: 10px; min-height: 48px; padding: 12px 0; border: 0; border-radius: 0; background: transparent; color: var(--a-ink); font: 600 12.5px var(--mono); text-align: left; }
    .adm-ui { border-bottom: 1px solid var(--a-line); }
    .adm-ui:last-child { border-bottom: 0; }
    .adm-ui .em { min-width: 0; overflow-wrap: anywhere; }
    .adm-chev { flex: 0 0 auto; width: 18px; height: 18px; color: var(--a-mut); transition: transform .25s ease; }
    .adm-ui.open .adm-chev { transform: rotate(180deg); }
    /* grid 0fr -> 1fr: tinggi beranimasi mulus, elemen di bawahnya ikut bergeser */
    .adm-ubody { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .3s ease; }
    .adm-ui.open .adm-ubody { grid-template-rows: 1fr; }
    .adm-ubody > div { overflow: hidden; min-height: 0; }
    .adm-ui.open .adm-ubody > div { padding-bottom: 10px; }
    @media (max-width: 767px) {
        .adm-utable { display: none; }
        .adm-ulist { display: block; }
    }
    @media (prefers-reduced-motion: reduce) { .adm-ubody, .adm-chev { transition: none; } }

    @media (max-width: 820px) {
        .adm-card { padding: 16px; }
        .adm-t { font-size: 12.5px; }
        .adm-big { font-size: 26px !important; }
        .adm-sub { font-size: 11.5px; line-height: 1.5; }
        .adm-chip { font-size: 10px; padding: 2px 8px; }
        .adm-row, .adm-row .k, .adm-row b { font-size: 12px; }
        .adm-kv dt, .adm-kv dd { font-size: 11.5px; }
        .adm-act .tx { font-size: 12.5px; }
        .adm-act .tx small, .adm-act .rt small { font-size: 10.5px; }
        .adm-act .rt { font-size: 13px; }
        .adm-foot { font-size: 10px; }
        .adm-kc { font-size: 11px; }
        .adm-kc .r2 { font-size: 13px; }
        .adm-pills button { font-size: 11px; padding: 6px 10px; }
        .adm-table th, .adm-table td { font-size: 12px; }
        .btn-pill { font-size: 12.5px; min-height: 40px; }
        .adm [style*="font:700 clamp(20px"] { font-size: 18px !important; }
        .adm section p[style*="13px/1.7"] { font-size: 12px !important; }
        .adm .field label { font-size: 12px; }
        .adm .field input { font-size: 13px; min-height: 44px; }
        .adm .field input::placeholder { font-size: 12px; }
        .adm .consent label { font-size: 11.5px; }
    }
    </style>
@endpush

@section('content')
@php
    $n = fn ($v, $dec = 0) => number_format((float) $v, $dec, ',', '.');
    $pct = fn ($a, $b) => $b > 0 ? round($a / $b * 100, 1) : 0;
    $u = $d['users']; $e = $d['exams']; $a = $d['ans']; $ai = $d['ai']; $k = $d['keys']; $rows = $d['userRows'];
    $procMax = max(array_merge($a['trend'], [1]));
    $signMax = max(array_merge($u['signups'], [1]));
    $tokLast14 = array_slice($ai['trend'], -14);
    $tokLastMax = max(array_merge($tokLast14, [1]));
    $cards = [
        'background:linear-gradient(135deg,#c8f966,#7be0a0)',
        'background:linear-gradient(135deg,#c9b3ff,#a68cff)',
        'background:linear-gradient(135deg,#ffc2a8,#ff9b7a)',
    ];
    $keyErr = $errors->has('label') || $errors->has('api_key');
@endphp

<div class="adm">
<div class="adm-grid">

    {{-- ===== BARIS 1 : Greeting (6/10) + Aktivitas sistem (4/10) ===== --}}
    <section class="adm-card adm-c6" style="--i:0">
        <div class="adm-hd">
            <div>
                <div style="font:700 clamp(20px,2.4vw,26px)/1.1 var(--mono)">Halo, <span class="adm-acc">{{ $d['greet'] }}</span></div>
                <div class="adm-sub">Ringkasan platform {{ count($d['labels']) }} hari terakhir.<br>{{ $n($u['active7']) }} user aktif 7 hari · {{ $n($u['total']) }} terdaftar.</div>
            </div>
            <div style="text-align:right">
                <div class="adm-sub" style="margin:0">Total esai diproses</div>
                <div class="adm-big" data-count="{{ $a['success'] }}">{{ $n($a['success']) }}</div>
                <div class="adm-sub" style="margin-top:4px"><span class="adm-chip {{ ($a['delta'] ?? 0) < 0 ? 'bad' : '' }}">{{ $a['delta'] === null ? '—' : (($a['delta'] >= 0 ? '+' : '') . $n($a['delta'], 1) . '%') }}</span> · 30 hari</div>
            </div>
        </div>
        <div class="adm-fcol end">
            <div class="adm-strip" style="margin-top:18px">
                @foreach($a['trend'] as $i => $v)
                    <i style="height:{{ max(4, round($v / $procMax * 100)) }}%; background:{{ intdiv($i, 4) % 2 === 0 ? 'var(--a-lime)' : 'var(--a-vio)' }}; opacity:{{ $v > 0 ? ($i === count($a['trend']) - 1 ? 1 : .82) : .25 }}; animation-delay:{{ $i * 20 }}ms"
                       title="{{ $d['labels'][$i] }}: {{ $n($v) }} esai"></i>
                @endforeach
            </div>
            <div class="adm-foot"><span>{{ $d['labels'][0] }}</span><span style="color:var(--a-ink)">Harian · esai diproses</span><span>Hari ini</span></div>
        </div>
    </section>

    <section class="adm-card adm-c4" style="--i:1">
        <h2 class="adm-t">Aktivitas sistem <span class="adm-chip">Live</span></h2>
        <ul class="adm-act">
            <li><span class="ic">◉</span><span class="tx">Pengguna<small>{{ $n($u['verified']) }} terverifikasi · {{ $n($u['admins']) }} admin</small></span><span class="rt">{{ $n($u['total']) }}<small style="color:var(--a-mut)">akun</small></span></li>
            <li><span class="ic">▶</span><span class="tx">Ujian aktif<small>{{ $n($e['draft']) }} draf · {{ $n($e['closed']) }} ditutup</small></span><span class="rt">{{ $n($e['active']) }}<small class="adm-acc">live</small></span></li>
            <li><span class="ic">✓</span><span class="tx">Esai berhasil diproses<small>log AI: {{ $n($a['logged']) }}</small></span><span class="rt">{{ $n($a['success']) }}<small class="adm-acc">selesai</small></span></li>
            <li><span class="ic">…</span><span class="tx">Antrean penilaian<small>menunggu giliran</small></span><span class="rt">{{ $n($a['pending']) }}<small style="color:var(--a-mut)">pending</small></span></li>
            <li><span class="ic">!</span><span class="tx">Esai gagal<small>melewati batas percobaan</small></span><span class="rt">{{ $n($a['failed']) }}<small style="color:var(--a-bad)">failed</small></span></li>
        </ul>
    </section>

    {{-- ===== BARIS 2 : Tambah kunci (4/10) + API Key Pool (6/10) ===== --}}
    <section class="adm-card adm-c4" id="tambah-kunci" style="--i:2">
        <h2 class="adm-t">Tambah Kunci Layanan AI</h2>
        <form method="POST" action="{{ route('keys.store') }}" autocomplete="off" class="adm-fcol">
            @csrf
            @if($keyErr)
                <div style="margin-bottom:12px">@foreach(['label','api_key'] as $f)@foreach($errors->get($f) as $msg)<div class="error">{{ $msg }}</div>@endforeach @endforeach</div>
            @endif
            <div class="field"><label for="k-label">Label (nama bebas, mis. "akun-gmail-2")</label><input id="k-label" type="text" name="label" value="{{ old('label') }}" required maxlength="100"></div>
            <div class="field"><label for="k-key">API key (diawali gsk_)</label><input id="k-key" type="password" name="api_key" autocomplete="off" required></div>
            <p class="adm-sub">Batas Groq dihitung per akun/organisasi; rotasi baru terasa jika key berasal dari akun berbeda. Kunci disimpan terenkripsi dan tidak pernah ditampilkan utuh.</p>
            <div style="margin-top:auto; padding-top:14px"><button type="submit" class="btn-pill">Simpan Kunci</button></div>
        </form>
    </section>

    <section class="adm-card adm-c6" style="--i:3" x-data='keyStack(@json($k["list"]), @json(url("/keys")))'>
        <h2 class="adm-t">API Key Pool <span class="adm-chip {{ $k['active'] > 0 ? '' : 'bad' }}">{{ $k['active'] }}/{{ $k['total'] }} aktif</span></h2>

        @if(count($k['list']))
            <div class="adm-stack">
                @foreach($k['list'] as $i => $kc)
                    <button type="button" class="adm-kc" @click="a = {{ $i }}"
                        :style="`top:${pos({{ $i }}).top}px; transform:scale(${pos({{ $i }}).scale}); z-index:${pos({{ $i }}).z}; opacity:${pos({{ $i }}).o}; pointer-events:${pos({{ $i }}).o ? 'auto' : 'none'}; {{ $cards[$i % 3] }}`"
                        aria-label="Pilih {{ $kc['label'] }}">
                        <span class="r1"><span>{{ $kc['label'] }} · {{ $kc['scope'] }}</span><span>{{ $kc['status'] }}</span></span>
                        <span class="r2">{{ $kc['mask'] }}</span>
                        <span class="r3"><span>Hari ini: {{ $n($kc['usage']) }} permintaan</span><span>#{{ $kc['id'] }}</span></span>
                    </button>
                @endforeach
            </div>
            <div class="adm-detail">
                <div class="adm-row"><span class="k">Terakhir dipakai</span><b x-text="cur.last"></b></div>
                <div class="adm-row"><span class="k">Status</span><b x-text="cur.cool ? 'Cooldown · ' + cur.cool : cur.status"></b></div>
                <div class="adm-row"><span class="k">Ditambahkan</span><b x-text="cur.since || '–'"></b></div>
            </div>

            {{-- Pojok kanan bawah: info status (kiri) sejajar dengan 3 tombol aksi (kanan) --}}
            <div class="adm-bar">
                <div class="adm-sub" style="margin:0">{{ $k['shared'] }} bersama · {{ $k['personal'] }} pribadi · {{ $k['cooling'] }} cooldown</div>
                <div class="adm-btns">
                    <form method="POST" :action="base + '/' + cur.id + '/test'">@csrf<button type="submit" class="btn-pill ghost">Tes</button></form>
                    <form method="POST" :action="base + '/' + cur.id + '/toggle'">@csrf<button type="submit" class="btn-pill ghost" x-text="cur.state === 'bad' ? 'Aktifkan' : 'Nonaktifkan'"></button></form>
                    <form method="POST" :action="base + '/' + cur.id" data-confirm="Hapus key ini?">@csrf @method('DELETE')<button type="submit" class="btn-pill dark">Hapus</button></form>
                </div>
            </div>
        @else
            <p class="adm-sub">Belum ada kunci API terdaftar.</p>
        @endif
    </section>

    {{-- ===== BARIS 3 : 3 kolom metrik (lebar & tinggi sama) ===== --}}
    <div class="adm-c10 adm-cols3">

        <div class="adm-card" style="--i:4" x-data='gaugeCard(@json($d["gauge"]))'>
            <h2 class="adm-t">Kesehatan AI <span class="adm-chip" :class="cur.reqs && cur.err >= 20 ? 'bad' : ''" x-text="cur.reqs ? (cur.err >= 20 ? 'Perlu dicek' : 'Sehat') : 'No data'"></span></h2>
            <svg class="adm-gauge" viewBox="0 0 200 118" role="img" aria-label="Gauge kesehatan sistem">
                <path class="tr" d="M20 100 A80 80 0 0 1 180 100" pathLength="100"/>
                <path class="va" d="M20 100 A80 80 0 0 1 180 100" pathLength="100" :stroke-dasharray="cur.health + ' 100'"/>
                <line class="nd" x1="100" y1="100" x2="100" y2="38" :style="`transform:rotate(${-90 + cur.health * 1.8}deg)`"/>
                <circle class="hub" cx="100" cy="100" r="6"/>
            </svg>
            <div style="text-align:center; margin-top:6px">
                <div class="adm-big" style="font-size:clamp(24px,2.6vw,32px)" x-text="cur.health.toLocaleString('id-ID') + '%'"></div>
                <div class="adm-sub">sukses · error <span x-text="cur.err.toLocaleString('id-ID')"></span>% · <span x-text="fmt(cur.reqs)"></span> permintaan</div>
                <div class="adm-sub" style="margin-top:2px"><span x-text="fmt(cur.tt)"></span> token<span x-show="p === 'peak'"> · hari <span x-text="cur.date"></span></span></div>
            </div>
            <div style="display:flex; justify-content:center; margin-top:auto; padding-top:12px">
                <div class="adm-pills" role="tablist">
                    <button type="button" :class="{ on: p === 'normal' }" @click="p = 'normal'">Normal</button>
                    <button type="button" :class="{ on: p === 'peak' }" @click="p = 'peak'">Puncak</button>
                    <button type="button" :class="{ on: p === 'total' }" @click="p = 'total'">Total</button>
                </div>
            </div>
        </div>

        <div class="adm-card" style="--i:5">
            <div class="adm-hd">
                <div>
                    <h2 class="adm-t" style="margin-bottom:6px">Konsumsi token global · {{ count($d['labels']) }} hari</h2>
                    <div class="adm-big" data-count="{{ $ai['sum30'] }}">{{ $n($ai['sum30']) }}</div>
                </div>
                <span class="adm-chip {{ ($ai['delta'] ?? 0) < 0 ? 'mut' : '' }}">{{ $ai['delta'] === null ? '—' : (($ai['delta'] >= 0 ? '+' : '') . $n($ai['delta'], 1) . '%') }}</span>
            </div>
            <div style="margin-top:auto; padding-top:16px">
                @php
                    $w = 640; $h = 300; $L = 6; $R = 44; $T = 12; $B = 24; $base = $h - $B;
                    $tmax = max(array_merge($ai['trend'], [1]));
                    $nn = count($ai['trend']); $stp = ($w - $L - $R) / max($nn - 1, 1);
                    $pts = [];
                    foreach ($ai['trend'] as $i => $v) { $pts[] = [$L + $i * $stp, $T + (1 - $v / $tmax) * ($base - $T)]; }
                    $line = '';
                    foreach ($pts as $q => $p) {
                        if ($q === 0) { $line .= sprintf('M%.1F %.1F', $p[0], $p[1]); continue; }
                        $pp = $pts[$q - 1]; $mx = ($pp[0] + $p[0]) / 2;
                        $line .= sprintf(' C%.1F %.1F %.1F %.1F %.1F %.1F', $mx, $pp[1], $mx, $p[1], $p[0], $p[1]);
                    }
                    $lastP = $pts[count($pts) - 1];
                    $areaP = $line . sprintf(' L%.1F %.1F L%.1F %.1F Z', $lastP[0], $base, $pts[0][0], $base);
                    $ab = fn ($v) => $v >= 1000000 ? rtrim(rtrim(number_format($v / 1000000, 1, '.', ''), '0'), '.') . 'M' : ($v >= 1000 ? rtrim(rtrim(number_format($v / 1000, 1, '.', ''), '0'), '.') . 'K' : (string) (int) round($v));
                @endphp
                <svg viewBox="0 0 {{ $w }} {{ $h }}" style="display:block; width:100%; height:auto" role="img" aria-label="Grafik token harian">
                    <defs><linearGradient id="adm-area" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#c8f966" stop-opacity=".45"/><stop offset="1" stop-color="#c8f966" stop-opacity="0"/></linearGradient></defs>
                    @foreach([1, .66, .33] as $g)
                        <line x1="{{ $L }}" x2="{{ $w - $R + 6 }}" y1="{{ $T + (1 - $g) * ($base - $T) }}" y2="{{ $T + (1 - $g) * ($base - $T) }}" stroke="var(--a-line)" stroke-dasharray="2 4"/>
                        <text x="{{ $w - 2 }}" y="{{ $T + (1 - $g) * ($base - $T) + 3 }}" text-anchor="end" style="fill:var(--a-mut); font:400 10px var(--mono)">{{ $ab($tmax * $g) }}</text>
                    @endforeach
                    <path d="{{ $areaP }}" fill="url(#adm-area)"/>
                    <path d="{{ $line }}" fill="none" stroke="var(--a-lime)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="{{ $lastP[0] }}" cy="{{ $lastP[1] }}" r="4.5" fill="var(--a-lime)"/>
                    @foreach($ai['trend'] as $i => $v)
                        <rect x="{{ $L + $i * $stp - $stp / 2 }}" y="0" width="{{ max($stp, 6) }}" height="{{ $base }}" fill="transparent"><title>{{ $d['labels'][$i] }}: {{ $n($v) }} token</title></rect>
                    @endforeach
                    <text x="{{ $L }}" y="{{ $h - 6 }}" style="fill:var(--a-mut); font:400 10px var(--mono)">{{ $d['labels'][0] }}</text>
                    <text x="{{ ($w - $R) / 2 }}" y="{{ $h - 6 }}" text-anchor="middle" style="fill:var(--a-mut); font:400 10px var(--mono)">{{ $d['labels'][(int) floor($nn / 2)] }}</text>
                    <text x="{{ $w - $R }}" y="{{ $h - 6 }}" text-anchor="end" style="fill:var(--a-mut); font:400 10px var(--mono)">{{ $d['labels'][$nn - 1] }}</text>
                </svg>
            </div>
        </div>

        <div class="adm-card adm-lime" style="--i:6">
            <h2 class="adm-t">Token AI total <span class="adm-chip" style="background:#14170a; color:var(--a-lime)">Log lokal</span></h2>
            <div class="adm-big" data-count="{{ $ai['tt'] }}">{{ $n($ai['tt']) }}</div>
            <div class="adm-sub">akumulasi seluruh log lokal</div>
            <dl class="adm-kv" style="margin-top:14px">
                <div><dt>Kunci bersama</dt><dd>{{ $n($ai['shared_tt']) }}</dd></div>
                <div><dt>Kunci pribadi</dt><dd>{{ $n($ai['tt'] - $ai['shared_tt']) }}</dd></div>
                <div><dt>Permintaan</dt><dd>{{ $n($ai['reqs']) }}</dd></div>
                <div><dt>Error rate</dt><dd>{{ $n($ai['err_rate'], 1) }}%</dd></div>
                <div><dt>Rata-rata respons</dt><dd>{{ $ai['lat'] > 0 ? $n($ai['lat']) . ' ms' : '–' }}</dd></div>
            </dl>
            <div style="display:flex; align-items:flex-end; gap:4px; height:60px; margin-top:auto; padding-top:16px">
                @foreach($tokLast14 as $i => $v)
                    <i style="flex:1; display:block; height:{{ max(8, round($v / $tokLastMax * 100)) }}%; border-radius:3px; background:#14170a; opacity:{{ $i === count($tokLast14) - 1 ? 1 : .22 + $i / 24 }}" title="{{ $n($v) }} token"></i>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ===== BARIS 4 : Status ujian ===== --}}
    <section class="adm-card adm-c10" style="--i:7">
        <h2 class="adm-t">Status ujian <span>{{ $n($e['total']) }} total</span></h2>
        <div class="adm-cols3 mid">
            <div>
                <div class="adm-big" data-count="{{ $e['active'] }}">{{ $n($e['active']) }}</div>
                <div class="adm-sub">ujian aktif saat ini</div>
            </div>
            <div class="adm-span2">
                <div class="adm-seg">
                    <i style="flex:{{ max($e['active'], .001) }}; background:var(--a-lime)"></i>
                    <i style="flex:{{ max($e['draft'], .001) }}; background:var(--a-vio)"></i>
                    <i style="flex:{{ max($e['closed'], .001) }}; background:var(--a-line)"></i>
                </div>
                <div class="adm-row"><span class="k"><i class="adm-dot" style="background:var(--a-lime)"></i>Aktif</span><b>{{ $n($e['active']) }} · {{ $pct($e['active'], $e['total']) }}%</b></div>
                <div class="adm-row"><span class="k"><i class="adm-dot" style="background:var(--a-vio)"></i>Draf</span><b>{{ $n($e['draft']) }} · {{ $pct($e['draft'], $e['total']) }}%</b></div>
                <div class="adm-row"><span class="k"><i class="adm-dot" style="background:var(--a-line)"></i>Ditutup</span><b>{{ $n($e['closed']) }} · {{ $pct($e['closed'], $e['total']) }}%</b></div>
            </div>
        </div>
    </section>

    {{-- ===== BARIS 5 : Galat teratas ===== --}}
    <section class="adm-card adm-c10" style="--i:8">
        <h2 class="adm-t">Galat teratas <span class="adm-chip {{ $ai['err_rate'] >= 20 ? 'bad' : 'mut' }}">7 hari</span></h2>
        @forelse($ai['errors'] as $err)
            <div class="adm-row"><span class="k"><code>{{ $err->code }}</code></span><b>{{ $n($err->c) }}×</b></div>
        @empty
            <p class="adm-sub">Tidak ada galat tercatat.</p>
        @endforelse
    </section>

    {{-- ===== BARIS 6 : Pendaftaran baru ===== --}}
    <section class="adm-card adm-c10" style="--i:9">
        <div class="adm-hd">
            <div><h2 class="adm-t" style="margin-bottom:6px">Pendaftaran baru · {{ count($d['labels']) }} hari</h2><div class="adm-big" data-count="{{ $u['week'] }}">{{ $n($u['week']) }}</div><div class="adm-sub">7 hari terakhir</div></div>
            <div style="text-align:right" class="adm-sub">{{ $n($u['total']) }} user<br>{{ $n($u['verified']) }} terverifikasi · {{ $n($u['admins']) }} admin</div>
        </div>
        <div class="adm-strip" style="margin-top:16px; height:70px">
            @foreach($u['signups'] as $i => $v)
                <i style="height:{{ max(5, round($v / $signMax * 100)) }}%; background:{{ $i === count($u['signups']) - 1 ? 'var(--a-lime)' : 'var(--a-line)' }}; animation-delay:{{ $i * 20 }}ms" title="{{ $d['labels'][$i] }}: {{ $n($v) }} user"></i>
            @endforeach
        </div>
        <div class="adm-foot"><span>{{ $d['labels'][0] }}</span><span>Hari ini</span></div>
    </section>

    {{-- ===== BARIS 7 : Monitoring user ===== --}}
    <section class="adm-card adm-c10" style="--i:10">
        <h2 class="adm-t">Monitoring user <span class="adm-chip mut">{{ $n($rows->total()) }} akun · metadata saja</span></h2>
        {{-- Desktop / tablet lebar: tabel penuh --}}
        <div class="adm-utable" style="overflow-x:auto">
            <table class="adm-table">
                <tr><th>Email</th><th>Peran</th><th>Form dibuat</th><th>Token AI</th><th>Terakhir aktif</th><th>Status</th></tr>
                @forelse($rows as $r)
                    <tr>
                        <td data-label="Email"><code>{{ $r->email }}</code></td>
                        <td data-label="Peran">{!! $r->is_admin ? '<span class="badge info">Admin</span>' : '<span class="badge">User</span>' !!}</td>
                        <td data-label="Form dibuat">{{ $n($r->exams_count) }}@if($r->active_exams) <span style="color:var(--a-mut)">({{ $r->active_exams }} aktif)</span>@endif</td>
                        <td data-label="Token AI">{{ $n($r->tokens_total) }}</td>
                        <td data-label="Terakhir aktif">{{ $r->last_activity ? $r->last_activity->diffForHumans() : '–' }}</td>
                        <td data-label="Status"><span class="badge {{ $r->state[1] }}">{{ $r->state[0] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="color:var(--a-mut)">Belum ada user.</td></tr>
                @endforelse
            </table>
        </div>

        {{-- Mobile: daftar email + accordion detail --}}
        <div class="adm-ulist">
            @forelse($rows as $r)
                <div class="adm-ui" x-data="{ open: false }" :class="{ open: open }">
                    <button type="button" @click="open = !open" :aria-expanded="open">
                        <span class="em">{{ $r->email }}</span>
                        <svg class="adm-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="adm-ubody" :aria-hidden="!open" :inert="!open">
                        <div>
                            <div class="adm-row"><span class="k">Peran</span>{!! $r->is_admin ? '<span class="badge info">Admin</span>' : '<span class="badge">User</span>' !!}</div>
                            <div class="adm-row"><span class="k">Form dibuat</span><b>{{ $n($r->exams_count) }}@if($r->active_exams) <span style="color:var(--a-mut); font-weight:400">({{ $r->active_exams }} aktif)</span>@endif</b></div>
                            <div class="adm-row"><span class="k">Token AI</span><b>{{ $n($r->tokens_total) }}</b></div>
                            <div class="adm-row"><span class="k">Terakhir aktif</span><b>{{ $r->last_activity ? $r->last_activity->diffForHumans() : '–' }}</b></div>
                            <div class="adm-row"><span class="k">Status</span><span class="badge {{ $r->state[1] }}">{{ $r->state[0] }}</span></div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="adm-sub">Belum ada user.</p>
            @endforelse
        </div>
        @if($rows->hasPages())
            <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin-top:12px">
                <span class="adm-sub" style="margin:0">Halaman {{ $rows->currentPage() }} dari {{ $rows->lastPage() }}</span>
                <span style="display:flex; gap:8px">
                    @if($rows->previousPageUrl())<a class="btn-pill ghost" href="{{ $rows->previousPageUrl() }}">← Sebelumnya</a>@endif
                    @if($rows->nextPageUrl())<a class="btn-pill ghost" href="{{ $rows->nextPageUrl() }}">Berikutnya →</a>@endif
                </span>
            </div>
        @endif
    </section>

    {{-- ===== BARIS 8 : Tambah user (6/10) + Sistem & integrasi (4/10) ===== --}}
    <section class="adm-card adm-c6" id="tambah-user" style="--i:11">
        <h2 class="adm-t">Tambah user via backend</h2>
        <form method="POST" action="{{ route('admin.monitoring.users.store') }}" autocomplete="off" class="adm-fcol" style="justify-content:space-between">
            @csrf
            <div>
                @if($errors->any() && ! $keyErr)
                    <div style="margin-bottom:12px">@foreach($errors->all() as $msg)<div class="error">{{ $msg }}</div>@endforeach</div>
                @endif
                <div class="field"><label for="np-name">Nama</label><input id="np-name" name="name" value="{{ old('name') }}" required maxlength="120"></div>
                <div class="field"><label for="np-email">Email</label><input id="np-email" type="email" name="email" value="{{ old('email') }}" required></div>
                <div class="field">
                    <label for="np-pass">Password (min. 10 karakter)</label>
                    <div style="display:flex; gap:8px">
                        <input id="np-pass" type="password" name="password" required minlength="10" autocomplete="new-password">
                        <button type="button" id="gen-pw" class="btn-pill ghost" style="white-space:nowrap">Acak</button>
                    </div>
                    <div class="adm-sub">Catat password sebelum menyimpan; tidak dapat ditampilkan lagi.</div>
                </div>
            </div>
            <div>
                <div class="consent">
                    <input id="np-verified" type="checkbox" name="verified" value="1" @checked(old('verified', true))>
                    <label for="np-verified" style="font-weight:400">Tandai email sudah terverifikasi (jika tidak, email aktivasi dikirim)</label>
                </div>
                <div style="margin-top:14px"><button type="submit" class="btn-pill">Buat akun</button></div>
            </div>
        </form>
    </section>

    @php
        $sys = $d['sys'];
        $backupOk = $sys['backup_age'] !== null && $sys['backup_age'] < 172800;
        $gPct = min(100, $sys['google']);
    @endphp
    <section class="adm-card adm-c4" style="--i:12">
        <h2 class="adm-t">Sistem &amp; integrasi <span class="adm-chip mut">Admin</span></h2>
        <div class="adm-sys">
            <div>
                <div class="hd"><span>Cadangan data</span><span class="adm-chip {{ $backupOk ? '' : 'bad' }}">{{ $backupOk ? 'Terbaru' : 'Perlu dibuat' }}</span></div>
                <div class="adm-sub">
                    @if($sys['backup_age'] === null)
                        Belum ada cadangan. Jalankan <code>php artisan db:backup</code>.
                    @else
                        Terakhir {{ floor($sys['backup_age'] / 3600) }} jam lalu.
                    @endif
                </div>
            </div>
            <div>
                <div class="hd"><span>Pengiriman email</span><span class="adm-chip {{ $sys['mail_driver'] === 'log' ? 'mut' : '' }}">{{ $sys['mail_driver'] }}</span></div>
                <div class="adm-sub" style="overflow-wrap:anywhere">Pengirim: {{ $sys['mail_from'] }}@if($sys['mail_driver'] === 'log') · mode uji, email hanya ditulis ke log.@endif</div>
                <form method="POST" action="{{ route('admin.mail-test') }}">
                    @csrf
                    <button type="submit" class="btn-pill ghost">Kirim email uji</button>
                </form>
            </div>
            <div>
                <div class="hd"><span>Akun Google terhubung</span><span class="adm-chip {{ $sys['google'] >= 80 ? 'bad' : '' }}">{{ $sys['google'] }} / 100</span></div>
                <div class="adm-meter"><i style="width:{{ $gPct }}%"></i></div>
                <div class="adm-sub">Batas 100 berlaku selama aplikasi Google belum diverifikasi.</div>
            </div>
        </div>
    </section>

    {{-- ===== BARIS TERAKHIR : Kebijakan non-intervensi ===== --}}
    <section class="adm-card adm-c10" style="--i:12">
        <h2 class="adm-t">Kebijakan non-intervensi <span class="adm-chip">Read-only</span></h2>
        <p style="font:400 13px/1.7 var(--mono); margin:0 0 14px">Admin hanya melihat <b>metadata dan agregat</b>: jumlah, status, dan konsumsi token. Isi soal, jawaban, identitas responden, nilai esai, dan isi kunci API tidak dapat dilihat atau diubah dari halaman ini.</p>
        <dl class="adm-kv">
            <div><dt>Email user</dt><dd>ditampilkan utuh</dd></div>
            <div><dt>Isi jawaban &amp; nilai</dt><dd>tidak diakses</dd></div>
            <div><dt>Kunci API</dt><dd>tidak ditampilkan</dd></div>
            <div><dt>Hak admin via form</dt><dd>tidak bisa diberikan</dd></div>
        </dl>
    </section>
</div>
</div>

<script>
    document.addEventListener('alpine:init', function () {
        var fmt = function (n) { return Math.round(n || 0).toLocaleString('id-ID'); };

        Alpine.data('keyStack', function (list, base) {
            return {
                list: list, base: base, a: 0,
                get cur() { return this.list[this.a] || {}; },
                /* kartu aktif di depan, maksimal 3 kartu terlihat */
                pos: function (i) {
                    var n = this.list.length, r = (i - this.a + n) % n, v = Math.min(n, 3);
                    if (r >= 3) { return { top: 0, scale: .88, z: 0, o: 0 }; }
                    return { top: (v - 1 - r) * 30, scale: 1 - r * 0.04, z: n - r, o: 1 };
                }
            };
        });

        Alpine.data('gaugeCard', function (data) {
            return {
                data: data, p: 'normal', fmt: fmt,
                get cur() { return this.data[this.p]; }
            };
        });
    });

    document.getElementById('gen-pw').addEventListener('click', function () {
        var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!#$%*';
        var arr = new Uint32Array(16), out = '';
        crypto.getRandomValues(arr);
        arr.forEach(function (x) { out += chars[x % chars.length]; });
        var i = document.getElementById('np-pass');
        i.type = 'text'; i.value = out;
    });
    @if($keyErr)
        document.getElementById('tambah-kunci').scrollIntoView({ block: 'start' });
    @elseif($errors->any())
        document.getElementById('tambah-user').scrollIntoView({ block: 'start' });
    @endif
</script>
@endsection
