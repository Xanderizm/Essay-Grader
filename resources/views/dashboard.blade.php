@extends('layout')
@section('title', 'Penilaian saya')

@push('styles')
<style>
    .exam-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .exam-card { position: relative; display: flex; flex-direction: column; justify-content: space-between; gap: 18px; min-height: 190px; }
    .exam-card h3 { margin: 0 0 10px; font-size: 19px; overflow-wrap: anywhere; }
    .exam-meta { display: flex; gap: 8px; flex-wrap: wrap; }
    .empty { display: grid; grid-template-columns: 210px 1fr; gap: 24px; align-items: center; }
    .empty-laptop {
        width: 100%;
        max-width: 210px;
        height: auto;
        display: block;
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    /* Kartu berwarna pastel: teks selalu gelap (--dark-text) di mode terang maupun gelap */
    .exam-card.card-lime  { background: var(--lime); }
    .exam-card.card-sky   { background: var(--sky); }
    .exam-card.card-amber { background: var(--amber); }
    .exam-card.card-pastel { color: var(--dark-text); border-color: var(--dark-text); box-shadow: 0 4px 0 var(--dark-text); }
    html[data-theme="dark"] .exam-card.card-pastel { box-shadow: 0 4px 0 rgba(236, 236, 232, .22); }
    .exam-card.card-pastel h3 { color: var(--dark-text); }
    .exam-card.card-pastel .badge { background: rgba(255, 255, 255, .75); color: var(--dark-text); border-color: var(--dark-text); }
    .exam-card.card-pastel .badge.bad { background: var(--coral); }
    .exam-card.card-lime  .badge.ok   { background: rgba(255, 255, 255, .75); }
    .exam-card.card-sky   .badge.ok   { background: var(--lime); }
    .exam-card.card-sky   .badge.warn { background: var(--amber); }
    .exam-card.card-amber .badge.ok   { background: var(--lime); }
    .exam-card.card-amber .badge.warn { background: rgba(255, 255, 255, .75); }
    .exam-card.card-pastel .btn { background: var(--dark-text); color: #fff; border-color: var(--dark-text); }
    .exam-card.card-pastel .btn.btn-outline { background: rgba(255, 255, 255, .75); color: var(--dark-text); }
    .exam-card.card-pastel .btn:hover { box-shadow: 3px 3px 0 var(--dark-text); }
    .exam-card.card-pastel :focus-visible { outline-color: var(--dark-text); }

    /* ---------- Hapus massal ---------- */
    .dash-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; flex-wrap: wrap; }
    .dash-actions > .btn { min-height: 40px; }
    .bulk-group { display: flex; align-items: center; gap: 10px; }
    .bulk-trigger { min-width: 40px; min-height: 40px; padding: 0; background: var(--surface); color: #d92d20; border-color: var(--ink); }
    html[data-theme="dark"] .bulk-trigger:not(.is-delete) { color: #ff7b72; }
    .bulk-trigger.is-delete { padding: 0 18px; background: #d92d20; color: #fff; border-color: #d92d20; }
    .bulk-trigger.is-delete:hover { box-shadow: 3px 3px 0 var(--ink); }
    .bulk-all {
        display: inline-flex; align-items: center; gap: 8px; min-height: 40px; padding: 0 14px;
        background: #fff; color: #1b1b1b; border: 1.5px solid var(--ink); border-radius: 10px;
        font: 700 12.5px var(--mono); cursor: pointer; user-select: none;
    }
    .bulk-check {
        -webkit-appearance: none; appearance: none; flex-shrink: 0; width: 18px; height: 18px; margin: 0;
        border: 2px solid #1b1b1b; border-radius: 50%; background: #fff; cursor: pointer;
    }
    .bulk-check:checked {
        border-color: #d92d20;
        background: #d92d20 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 12.5l4.5 4.5L19 7.5'/%3E%3C/svg%3E") center / 12px no-repeat;
    }
    .bulk-check:focus-visible { outline: 2px solid #1b1b1b; outline-offset: 2px; }
    .bulk-bar { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin: -6px 0 14px; font: 400 12.5px var(--mono); color: var(--muted); }
    .bulk-bar b { color: var(--ink); }

    /* Lingkaran pilih di kartu: seluruh kartu bisa diklik saat mode pilih */
    .sel-overlay { position: absolute; inset: 0; z-index: 5; border-radius: inherit; cursor: pointer; }
    .sel-overlay:focus-visible { outline: 3px solid var(--ink); outline-offset: 3px; }
    .sel-circle {
        position: absolute; right: 16px; bottom: 16px; width: 26px; height: 26px; border-radius: 50%;
        border: 2px solid #1b1b1b; background: rgba(255, 255, 255, .75); color: #1b1b1b;
        display: grid; place-items: center; transition: background .12s, border-color .12s;
    }
    .card-dark .sel-circle { background: transparent; border-color: var(--card-dark-fg); color: var(--card-dark-fg); }
    .sel-circle svg { width: 14px; height: 14px; opacity: 0; }
    .sel-circle.on { background: #d92d20; border-color: #d92d20; color: #fff; }
    .sel-circle.on svg { opacity: 1; }
    .exam-card.is-selected { outline: 3px solid #d92d20; outline-offset: 3px; }

    /* Dialog konfirmasi (gaya sama dengan dialog hapus di halaman Kelola) */
    .bulk-box { display: flex; flex-direction: column; max-height: calc(100vh - 40px); max-height: calc(100dvh - 40px); box-sizing: border-box; }
    .dlg-head { display: grid; grid-template-columns: auto 1fr auto; gap: 12px; align-items: start; margin-bottom: 16px; flex-shrink: 0; }
    .dlg-head h3 { margin: 0 0 4px; font-size: 19px; }
    .dlg-head p { margin: 0; }
    .dlg-x { background: none; color: var(--ink); border: 0; padding: 4px 8px; min-height: 0; font-size: 22px; line-height: 1; box-shadow: none; }
    .dlg-x:hover { background: var(--mist); box-shadow: none; transform: none; }
    .dlg-warn { display: flex; flex-direction: column; min-height: 0; background: var(--err-bg); border: 1.5px solid var(--err); border-radius: 12px; padding: 12px 14px; margin-bottom: 16px; }
    .dlg-warn p { margin: 0 0 6px; }
    .bulk-list { overflow-y: auto; max-height: min(260px, 38vh); max-height: min(260px, 38dvh); overscroll-behavior: contain; padding-right: 6px; }
    .bulk-item { padding: 8px 0; }
    .bulk-item + .bulk-item { border-top: 1px dashed var(--err); }
    .bulk-item b { overflow-wrap: anywhere; }
    .bulk-item .muted { font-size: 12.5px; margin-top: 2px; }
    .bulk-item .bulk-active { font-size: 12.5px; margin-top: 2px; color: var(--err); font-weight: 700; }
    .bulk-box .dlg-actions { flex-shrink: 0; }

    @media (max-width: 820px) {
        .exam-grid, .empty { grid-template-columns: 1fr; }
        .empty-laptop { max-width: 180px; margin: 0 auto; }
        .sec-head { flex-direction: column; align-items: stretch; gap: 12px; }
        .sec-head .btn { width: 100%; text-align: center; min-height: 44px; }
        .exam-card .row-actions { flex-direction: column; }
        .exam-card .row-actions .btn { width: 100%; text-align: center; min-height: 44px; }

        /* Hapus massal di mobile: tombol penuh di bawah "Buat penilaian baru" */
        .dash-actions { flex-direction: column; align-items: stretch; width: 100%; gap: 10px; }
        .dash-actions > .btn { order: 1; min-height: 44px; }
        .bulk-group { order: 2; position: relative; display: block; }
        .bulk-trigger { width: 100%; min-height: 44px; padding: 10px 16px; background: var(--coral); color: #7f1d1d; }
        html[data-theme="dark"] .bulk-trigger:not(.is-delete) { color: #7f1d1d; }
        .bulk-trigger.is-delete { background: #d92d20; color: #fff; }
        .bulk-all { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); min-height: 30px; padding: 0 10px; border-radius: 8px; font-size: 12px; }
        .sel-circle { top: 14px; bottom: auto; right: 14px; }
        .is-selecting .exam-card h3 { padding-right: 40px; }
    }
</style>
@endpush

@section('content')
@include('shape-grid', ['scope' => 'box'])
@php
    $exams->loadCount('submissions');
    $statusLabel = ['draft' => 'Draf', 'active' => 'Aktif', 'closed' => 'Ditutup'];
    $statusBadge = ['draft' => 'warn', 'active' => 'ok', 'closed' => 'bad'];
    $bulk = $exams->map(fn ($e) => [
        'id'     => $e->id,
        'title'  => $e->title ?? 'Menunggu analisis Form',
        'status' => $e->status,
        'subs'   => (int) $e->submissions_count,
    ])->values();
@endphp

<script>
    function bulkDelete(items) {
        return {
            items: items, selected: [], selecting: false, confirming: false, busy: false,
            get count() { return this.selected.length; },
            get allSelected() { return this.items.length > 0 && this.selected.length === this.items.length; },
            get picked() { return this.items.filter(i => this.selected.includes(i.id)); },
            isSel(id) { return this.selected.includes(id); },
            toggle(id) { this.selected = this.isSel(id) ? this.selected.filter(x => x !== id) : [...this.selected, id]; },
            toggleAll() { this.selected = this.allSelected ? [] : this.items.map(i => i.id); },
            exit() { this.selecting = false; this.selected = []; },
            // Ikon trash: masuk mode pilih. Belum ada yang dicentang: keluar. Sudah ada: konfirmasi hapus.
            trigger() {
                if (!this.selecting) { this.selecting = true; return; }
                if (this.count === 0) { this.exit(); return; }
                this.openConfirm();
            },
            openConfirm() { this.busy = false; this.confirming = true; this.$refs.dlg.showModal(); },
            closeConfirm() { this.$refs.dlg.close(); },
        };
    }
</script>

<div x-data="bulkDelete(@js($bulk))" :class="{ 'is-selecting': selecting }"
     @keydown.escape.window="if (selecting && !confirming) exit()">

    <div class="sec-head" style="margin-top:0; justify-content:space-between; align-items:center">
        <h1 class="tag" style="font-size:26px">Penilaian saya</h1>
        <div class="dash-actions">
            @unless($exams->isEmpty())
                <div class="bulk-group">
                    <button type="button" class="bulk-trigger" :class="{ 'is-delete': count > 0 }" @click="trigger()"
                            :aria-label="count > 0 ? 'Hapus ' + count + ' penilaian terpilih' : (selecting ? 'Batalkan pemilihan' : 'Pilih penilaian untuk dihapus')"
                            :title="count > 0 ? 'Hapus ' + count + ' penilaian terpilih' : (selecting ? 'Batalkan pemilihan' : 'Pilih penilaian untuk dihapus')">
                        <svg x-show="count === 0" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                        <span x-show="count > 0" x-cloak>Hapus</span>
                    </button>
                    <label class="bulk-all" x-show="selecting" x-cloak>
                        <input type="checkbox" class="bulk-check" :checked="allSelected" @change="toggleAll()">
                        Semua
                    </label>
                </div>
            @endunless
            <a class="btn" href="{{ route('exams.create') }}">Buat penilaian baru</a>
        </div>
    </div>

    @if($exams->isEmpty())
        <div class="card empty">
            <img src="{{ asset('images/empty.svg') }}" class="empty-laptop" alt="Belum ada penilaian" loading="lazy">
            <div>
                <h2>Belum ada penilaian</h2>
                <p class="muted">Hubungkan akun Google, tempel link Google Form, dan sistem membaca soalnya otomatis.</p>
                <a class="btn" href="{{ route('exams.create') }}">Buat penilaian pertama</a>
            </div>
        </div>
    @else
        <div class="bulk-bar" x-show="selecting" x-cloak role="status" aria-live="polite">
            <span><b x-text="count"></b> dari {{ $exams->count() }} penilaian dipilih</span>
            <button type="button" class="linkbtn" @click="exit()">Batal</button>
        </div>

        <div class="exam-grid">
            @foreach($exams as $i => $exam)
                @php
                    $tone = ['lime', 'dark', 'sky', 'amber'][$i % 4];
                    $dark = $tone === 'dark';
                @endphp
                <div>
                    @if($exam->gform_deleted_at)
                        <div role="alert" style="background:#FEF2F2; color:#B91C1C; border:1px solid #FECACA; border-radius:6px; padding:10px 14px; margin-bottom:8px; font-size:14px">
                            Google Form ini sudah dihapus. Penilaian dinonaktifkan dan tidak bisa diaktifkan lagi.
                        </div>
                    @endif
                    <article class="card exam-card {{ $dark ? 'card-dark' : 'card-pastel card-' . $tone }}"
                             :class="{ 'is-selected': isSel({{ $exam->id }}) }">
                        <div :inert="selecting">
                            <h3>{{ $exam->title ?? 'Menunggu analisis Form' }}</h3>
                            <div class="exam-meta">
                                @if($exam->analysis_status === 'success')
                                    <span class="badge ok">Analisis berhasil</span>
                                @elseif($exam->analysis_status === 'failed')
                                    <span class="badge bad">Analisis gagal</span>
                                @else
                                    <span class="badge warn">Menganalisis</span>
                                @endif
                                <span class="badge {{ $statusBadge[$exam->status] ?? '' }}">{{ $statusLabel[$exam->status] ?? $exam->status }}</span>
                                <span class="badge">{{ $exam->submissions_count }} jawaban masuk</span>
                            </div>
                        </div>
                        <div class="row-actions" style="margin-top:0" :inert="selecting">
                            @if($exam->analysis_status === 'success')
                                <a class="btn" href="{{ route('exams.show', $exam) }}">Lihat nilai</a>
                            @endif
                            <a class="btn {{ $dark ? 'btn-ghost' : 'btn-outline' }}" href="{{ route('exams.edit', $exam) }}">Kelola</a>
                        </div>

                        <div class="sel-overlay" x-show="selecting" x-cloak role="checkbox" tabindex="0"
                             :aria-checked="isSel({{ $exam->id }}) ? 'true' : 'false'"
                             aria-label="Pilih penilaian {{ $exam->title ?? 'tanpa judul' }}"
                             @click="toggle({{ $exam->id }})"
                             @keydown.space.prevent="toggle({{ $exam->id }})"
                             @keydown.enter.prevent="toggle({{ $exam->id }})">
                            <span class="sel-circle" :class="{ 'on': isSel({{ $exam->id }}) }" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                            </span>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
    @endif

    <dialog x-ref="dlg" class="dlg" @close="confirming = false" aria-labelledby="bd-title" aria-describedby="bd-desc">
        <form method="POST" action="{{ route('exams.bulk-destroy') }}" class="dlg-box bulk-box" @submit="busy = true">
            @csrf
            @method('DELETE')
            <template x-for="it in picked" :key="'id' + it.id">
                <input type="hidden" name="ids[]" :value="it.id">
            </template>

            <div class="dlg-head">
                <span class="dlg-icon dlg-icon-danger" aria-hidden="true">!</span>
                <div>
                    <h3 id="bd-title">Hapus <span x-text="count"></span> penilaian?</h3>
                    <p id="bd-desc" class="muted">Tindakan ini tidak bisa dibatalkan.</p>
                </div>
                <button type="button" class="dlg-x" @click="closeConfirm()" aria-label="Tutup">&times;</button>
            </div>

            <div class="dlg-warn">
                <p>Data berikut akan <b>dihapus permanen dari database</b>:</p>
                <div class="bulk-list" tabindex="0" aria-label="Daftar penilaian yang akan dihapus">
                    <template x-for="it in picked" :key="'row' + it.id">
                        <div class="bulk-item">
                            <b x-text="it.title"></b>
                            <div class="muted">
                                <span x-text="it.subs"></span> pengiriman responden beserta jawabannya, semua nilai dan catatan koreksi, serta pengaturan dan rubrik penilaian
                            </div>
                            <div class="bulk-active" x-show="it.status === 'active'" x-cloak>Sedang aktif: pengambilan jawaban otomatis ikut berhenti.</div>
                        </div>
                    </template>
                </div>
                <p class="muted" style="margin:8px 0 0">Unduh nilai ke Excel dulu jika masih diperlukan. Jawaban asli di Google Form Anda tidak ikut terhapus.</p>
            </div>

            <div class="dlg-actions">
                <button type="button" class="btn-outline" @click="closeConfirm()" autofocus>Batal</button>
                <button type="submit" class="danger" :disabled="busy">Lanjutkan Hapus</button>
            </div>
        </form>
    </dialog>
</div>
@endsection