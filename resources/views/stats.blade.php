@extends('layout')
@section('title', 'Statistik · Essay Grader')

@push('styles')
    @include('bento-assets')
    @include('bento-glass')
    @include('bento-responsive')
@endpush

@section('content')
@php
    $n = fn ($v, $dec = 0) => number_format((float) $v, $dec, ',', '.');
    $e = $d['exams']; $s = $d['subs']; $a = $d['ans']; $sc = $d['score']; $t = $d['tok']; $w = $d['win'];
    $statusMap = ['active' => ['Aktif', 'ok'], 'draft' => ['Draf', ''], 'closed' => ['Ditutup', 'info']];
    $pct = fn ($v, $tot) => $tot > 0 ? round($v / $tot * 100) : 0;
    $distLbl = ['0–19', '20–39', '40–59', '60–79', '80–100'];
    $distMax = max(array_merge($sc['dist'], [1]));
    $distTot = array_sum($sc['dist']);
@endphp

<style>
    .ux-bars { display: flex; align-items: flex-end; gap: 3px; height: 110px; }
    .ux-bars i { flex: 1; min-width: 0; min-height: 2px; border-radius: 4px; background: var(--ux-green); transform-origin: bottom; animation: bnRise .6s cubic-bezier(.22,1,.36,1) both; }
    .ux-dist { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; align-items: end; height: 150px; }
    .ux-dist > div { display: flex; flex-direction: column; justify-content: flex-end; height: 100%; text-align: center; font: 400 11px var(--mono); color: var(--ux-mut); }
    .ux-dist b { display: block; font: 700 13px var(--mono); color: var(--ux-ink); margin-bottom: 4px; }
    .ux-dist i { display: block; border-radius: 10px 10px 4px 4px; min-height: 4px; transform-origin: bottom; animation: bnRise .6s cubic-bezier(.22,1,.36,1) both; }
    .ux-dist span { margin-top: 6px; }
    .ux-seg2 { display: flex; gap: 3px; height: 12px; margin-top: 14px; }
    .ux-seg2 i { display: block; height: 100%; border-radius: 6px; }
    .ux-meter { height: 12px; border-radius: 6px; background: var(--ux-soft); overflow: hidden; margin-top: 14px; }
    .ux-meter i { display: block; height: 100%; border-radius: 6px; background: var(--ux-green); }
    .ux-card h2 { margin: 0 0 4px; font: 600 15px var(--display); letter-spacing: -.01em; }

    /* Grid bento 10 kolom (CSS murni, tidak bergantung build Tailwind). gap 20px = gap-5 */
    .ux-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; }
    .ux-stack { display: flex; flex-direction: column; gap: 20px; }
    @media (min-width: 768px) {
        .ux-grid { grid-template-columns: repeat(10, minmax(0, 1fr)); }
        .ux-grid > .ux-c4 { grid-column: span 4 / span 4; }
        .ux-grid > .ux-c5 { grid-column: span 5 / span 5; }
        .ux-grid > .ux-c6 { grid-column: span 6 / span 6; }
        .ux-grid > .ux-c10 { grid-column: span 10 / span 10; }
    }

    /* Semua ukuran layar: kartu setinggi isinya, isi tidak boleh menciut atau meluber */
    .ux-grid > .ux-card,
    .ux-grid .ux-stack > .ux-card { display: flex; flex-direction: column; border-radius: 26px; height: auto; min-height: min-content; }
    .ux-grid .ux-card > * { flex-shrink: 0; }
    .ux-grid .ux-card > .ux-frame,
    .ux-grid .ux-card > .ux-meter,
    .ux-grid .ux-card > .ux-seg2 { margin-top: 14px; }

    /* Legenda status: rapat, membungkus rapi, font kecil */
    .ux-legend { display: flex; flex-wrap: wrap; gap: 4px 12px; margin-top: 12px; font-size: 11px; line-height: 1.4; }
    .ux-legend span { white-space: nowrap; }

    /* Desktop saja: tinggi kartu disamakan satu baris, ruang kosong dibagi lewat margin-top:auto */
    @media (min-width: 768px) {
        .ux-grid > .ux-card { height: 100%; }
        .ux-grid .ux-stack { height: 100%; }
        .ux-grid .ux-stack > .ux-card { flex: 1 1 auto; justify-content: space-between; }
        .ux-grid .ux-card > .ux-frame { margin-top: auto; }
        .ux-grid .ux-card > .ux-meter,
        .ux-grid .ux-card > .ux-seg2 { margin-top: auto; }
        .ux-grid .ux-card > .ux-sub + .ux-meter,
        .ux-grid .ux-card > .ux-sub + .ux-seg2 { margin-top: 14px; }
        .ux-grid .ux-stack > .ux-card > .ux-legend { margin-top: 12px; }
    }

    /* Kartu "Aktivitas Pengiriman Form" meregang vertikal mengikuti tinggi tumpukan kiri */
    .ux-grow > .ux-frame { flex: 1 1 auto; display: flex; flex-direction: column; margin-top: 14px; }
    .ux-grow .ux-bars { flex: 1 1 auto; height: auto; min-height: 140px; }
</style>

<div class="sec-head" style="margin-top:6px">
    <h1 class="tag" style="font-size:clamp(22px,3.5vw,30px)">Statistik anda</h1>
    <p>Hanya data ujian milik Anda. Grafik menampilkan {{ $d['days'] }} hari terakhir.</p>
</div>

<div class="ux">
{{-- Grid bento 10 kolom: gap-5 konsisten horizontal & vertikal --}}
<div class="ux-grid">

    {{-- Baris 1 · 5/10 + 5/10 --}}
    <section class="ux-card ux-c5 rounded-[26px]" style="--i:0">
        <p class="ux-label">Ujian dibuat</p>
        <div class="ux-big"><span data-count="{{ $e['total'] }}">{{ $n($e['total']) }}</span></div>
        <div class="ux-sub">{{ $e['active'] }} aktif · {{ $e['draft'] }} draf · {{ $e['closed'] }} ditutup</div>
        <div class="ux-frame" style="margin-top:14px">
            <small>Kumulatif {{ $d['days'] }} hari</small>
            <x-spark :lines="[['name' => 'Ujian', 'data' => $e['trend'], 'color' => 'var(--ux-green)', 'area' => true]]" :labels="$d['labels']" :w="420" :h="110" empty="Belum ada ujian."/>
        </div>
    </section>

    <section class="ux-card ux-c5 rounded-[26px]" style="--i:1">
        <p class="ux-label">Total responden</p>
        <div class="ux-big"><span data-count="{{ $s['total'] }}">{{ $n($s['total']) }}</span>
            <span style="font-size:14px; vertical-align:middle"><x-delta :v="$s['delta']"/></span></div>
        <div class="ux-sub">{{ $n($s['sum30']) }} responden baru dalam {{ $d['days'] }} hari</div>
        <div class="ux-frame" style="margin-top:14px">
            <small>Kumulatif {{ $d['days'] }} hari</small>
            <x-spark :lines="[['name' => 'Responden', 'data' => $s['cum'], 'color' => 'var(--ux-green)', 'area' => true]]" :labels="$d['labels']" :w="420" :h="110" empty="Belum ada responden."/>
        </div>
    </section>

    {{-- Baris 2 · kiri 4/10 (bertumpuk) + kanan 6/10 (setinggi tumpukan) --}}
    <div class="ux-stack ux-c4">
        <section class="ux-card rounded-[26px]" style="--i:2">
            <p class="ux-label">Analisis AI</p>
            <div class="ux-big"><span data-count="{{ $a['success'] }}">{{ $n($a['success']) }}</span>
                <span style="font-size:.4em; opacity:.6">/ {{ $n($a['total']) }} jawaban</span></div>
            <div class="ux-sub">{{ $pct($a['success'], $a['total']) }}% jawaban sudah selesai dinilai</div>
            <div class="ux-seg2">
                @if($a['total'] > 0)
                    <i style="flex:{{ max($a['success'], 0) }}; background:var(--ux-green)"></i>
                    <i style="flex:{{ max($a['failed'], 0) }}; background:var(--ux-red)"></i>
                    <i style="flex:{{ max($a['pending'], 0) }}; background:var(--ux-mut)"></i>
                @else
                    <i style="flex:1; background:var(--ux-soft)"></i>
                @endif
            </div>
            <div class="ux-legend">
                <span><i style="background:var(--ux-green)"></i>Selesai {{ $n($a['success']) }}</span>
                <span><i style="background:var(--ux-red)"></i>Gagal {{ $n($a['failed']) }}</span>
                <span><i style="background:var(--ux-mut)"></i>Menunggu {{ $n($a['pending']) }}</span>
            </div>
        </section>

        <section class="ux-card rounded-[26px]" style="--i:3">
            <p class="ux-label">Rata-rata nilai</p>
            <div class="ux-big"><span data-count="{{ round($sc['avg'], 1) }}" data-dec="1">{{ $n($sc['avg'], 1) }}</span><span style="font-size:.4em; opacity:.6">/100</span></div>
            <div class="ux-sub">dari {{ $n($sc['count']) }} jawaban dinilai · {{ $n($sc['overridden']) }} dikoreksi manual</div>
            <div class="ux-meter"><i style="width:{{ min(100, round($sc['avg'])) }}%"></i></div>
        </section>
    </div>

    <section class="ux-card ux-grow ux-c6 rounded-[26px]" style="--i:4">
        <p class="ux-label">Aktivitas pengiriman form</p>
        <div class="ux-big"><span data-count="{{ $s['week'] }}">{{ $n($s['week']) }}</span>
            <span style="font-size:.4em; opacity:.6">7 hari terakhir</span></div>
        <div class="ux-sub">{{ $n($s['sum30']) }} pengiriman dalam {{ $d['days'] }} hari</div>
        @php $bmax = max(array_merge($s['daily'], [1])); @endphp
        <div class="ux-frame">
            <div class="ux-bars">
                @foreach($s['daily'] as $i => $v)
                    <i style="height:{{ round($v / $bmax * 100, 1) }}%; animation-delay:{{ $i * 20 }}ms" title="{{ $d['labels'][$i] }}: {{ $n($v) }} pengiriman"></i>
                @endforeach
            </div>
            <div style="display:flex; justify-content:space-between; margin-top:6px; font:400 10.5px var(--mono); color:var(--ux-mut)">
                <span>{{ $d['labels'][0] }}</span><span>{{ $d['labels'][count($d['labels']) - 1] }}</span>
            </div>
        </div>
    </section>

    {{-- Baris 3 · 5/10 + 5/10 --}}
    <section class="ux-card ux-c5 rounded-[26px]" style="--i:5">
        <p class="ux-label">Sebaran nilai (0–100)</p>
        <div class="ux-sub" style="margin:2px 0 14px">{{ $n($distTot) }} jawaban dinilai</div>
        @php $colors = ['var(--ux-red)', '#ff8a33', '#ffb800', '#8dd64a', 'var(--ux-green)']; @endphp
        <div class="ux-dist" style="margin-top:auto">
            @foreach($sc['dist'] as $i => $v)
                <div>
                    <b>{{ $n($v) }}</b>
                    <i style="height:{{ max(round($v / $distMax * 100), 3) }}%; background:{{ $colors[$i] }}; animation-delay:{{ $i * 60 }}ms" title="{{ $distLbl[$i] }}: {{ $n($v) }} jawaban ({{ $pct($v, $distTot) }}%)"></i>
                    <span>{{ $distLbl[$i] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="ux-card ux-c5 rounded-[26px]" style="--i:7">
        <p class="ux-label">Win rate &amp; efisiensi</p>
        <div class="ux-big"><span data-count="{{ $w['rate'] }}" data-dec="1">{{ $n($w['rate'], 1) }}</span><span style="font-size:.4em; opacity:.6">%</span></div>
        <div class="ux-sub">permintaan AI yang berhasil</div>
        <div class="ux-meter"><i style="width:{{ min(100, $w['rate']) }}%"></i></div>
        <dl class="ux-kv" style="margin-top:14px">
            <div><dt>Permintaan berhasil / gagal</dt><dd>{{ $n($w['ok']) }} / {{ $n($w['fail']) }}</dd></div>
            <div><dt>Token per jawaban</dt><dd>{{ $n($w['per']) }}</dd></div>
            <div><dt>Latensi rata-rata</dt><dd>{{ $n($w['lat'], 1) }} dtk</dd></div>
            <div><dt>Token via kunci bersama</dt><dd>{{ $n($t['shared_tt']) }}</dd></div>
        </dl>
    </section>

    {{-- Baris 4 · 5/10 + 5/10 --}}
    <section class="ux-card ux-c5 rounded-[26px]" style="--i:6">
        <p class="ux-label">Token AI (log lokal)</p>
        <div class="ux-big"><span data-count="{{ $t['tt'] }}">{{ $n($t['tt']) }}</span>
            <span style="font-size:14px; vertical-align:middle"><x-delta :v="$t['delta']"/></span></div>
        <div class="ux-sub">{{ $n($t['pt']) }} prompt · {{ $n($t['ct']) }} respons · {{ $n($t['sum30']) }} dalam {{ $d['days'] }} hari</div>
        <div class="ux-frame" style="margin-top:14px">
            <small>Per hari · {{ $d['days'] }} hari</small>
            <x-spark :lines="[['name' => 'Token', 'data' => $t['trend'], 'color' => 'var(--ux-green)', 'area' => true]]" :labels="$d['labels']" :w="420" :h="150" empty="Belum ada pemakaian token."/>
        </div>
    </section>

    <section class="ux-card ux-c5 rounded-[26px]" style="--i:8">
        <p class="ux-label">Tren rata-rata nilai · {{ $d['days'] }} hari</p>
        <div class="ux-big"><span>{{ $n($sc['avg'], 1) }}</span><span style="font-size:.4em; opacity:.6">/100</span></div>
        <div class="ux-sub">&nbsp;</div>
        <div class="ux-frame" style="margin-top:14px">
            <small>Per hari · {{ $d['days'] }} hari</small>
            <x-spark :lines="[['name' => 'Rata-rata nilai', 'data' => $sc['trend'], 'color' => 'var(--ux-green)', 'area' => true]]" :labels="$d['labels']" :w="420" :h="150" :max="100" empty="Belum ada jawaban dinilai."/>
        </div>
    </section>

    {{-- Baris 5 · full width --}}
    <section class="ux-card ux-c10 rounded-[26px]" style="--i:9">
        <p class="ux-label">Riwayat ujian terbaru</p>
        <div class="ux-scroll">
        <table class="ux-table">
            <tr><th>Ujian</th><th class="r">Status</th><th class="r">Responden</th><th class="r">Rata-rata</th></tr>
            @forelse($d['history'] as $h)
                @php [$lbl, $cls] = $statusMap[$h['status']] ?? [ucfirst($h['status']), '']; @endphp
                <tr>
                    <td style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap" title="{{ $h['title'] }}">
                        <a href="{{ route('exams.show', $h['id']) }}">{{ $h['title'] }}</a>
                    </td>
                    <td class="r"><span class="badge {{ $cls }}">{{ $lbl }}</span></td>
                    <td class="r">{{ $n($h['subs']) }}</td>
                    <td class="r">{{ $h['avg'] !== null ? $n($h['avg'], 1) : '–' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="color:var(--ux-mut)">Belum ada ujian. <a href="{{ route('exams.create') }}">Buat penilaian pertama</a>.</td></tr>
            @endforelse
        </table>
        </div>
    </section>
</div>
</div>
@endsection

<script>
(function() {
    // Interval 30 detik untuk Admin, 60 detik untuk Statistik
    const INTERVAL_MS = 30000; 
    let timer = null;

    function refreshData() {
        // Jangan request jika tab browser sedang tidak dibuka oleh pengguna
        if (document.hidden) return;

        fetch(window.location.href, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // Ganti kontainer utama saja agar tidak flicker dan posisi scroll tetap aman
            const freshContent = doc.querySelector('.adm-grid') || doc.querySelector('.stats-grid');
            const currentContent = document.querySelector('.adm-grid') || document.querySelector('.stats-grid');

            if (freshContent && currentContent) {
                currentContent.innerHTML = freshContent.innerHTML;
            }
        })
        .catch(err => console.debug('Gagal auto-refresh:', err));
    }

    function startTimer() {
        timer = setInterval(refreshData, INTERVAL_MS);
    }

    // Tangani saat tab ditinggal / dibuka kembali
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            clearInterval(timer);
        } else {
            refreshData(); // Segarkan sekali saat tab kembali dibuka
            startTimer();
        }
    });

    startTimer();
})();
</script>