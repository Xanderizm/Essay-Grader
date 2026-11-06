@extends('layout')
@section('title', 'Kelola API Key')
@section('content')
<p><a href="{{ route('dashboard') }}">&larr; Kembali</a></p>
<h2>Kunci Layanan AI</h2>
<p class="muted">
    Sistem memakai kunci secara bergantian. Jika satu kunci mencapai batas pemakaian, sistem otomatis pindah ke kunci lain.
    Kunci disimpan aman dan tidak pernah ditampilkan utuh.
</p>

@if($errors->any())
    <div class="card" style="border-left:4px solid #991b1b; margin-bottom:16px">
        @foreach($errors->all() as $e)<div class="error">{{ $e }}</div>@endforeach
    </div>
@endif

<div class="card" style="overflow-x:auto; margin-bottom:16px">
    <table>
        <tr>
            <th>Label</th>
            <th>Key</th>
            <th>Status</th>
            <th>Dipakai hari ini</th>
            <th>Terakhir dipakai</th>
            <th>Aksi</th>
        </tr>

        @forelse($keys as $item)
            @php($k = $item['model'])
            <tr>
                <td>{{ $k->label }}<div class="muted">{{ $k->provider_name }}</div></td>
                <td><code>{{ $item['masked'] }}</code></td>
                <td>
                    @if(! $k->is_active)
                        <span class="badge bad">Nonaktif</span>
                    @elseif($k->cooldown_until && $k->cooldown_until->isFuture())
                        <span class="badge warn">Dijeda sampai {{ $k->cooldown_until->timezone('Asia/Jakarta')->format('H:i') }}</span>
                    @else
                        <span class="badge ok">Aktif</span>
                    @endif
                </td>
                <td>{{ $k->usage_date && $k->usage_date->isToday() ? $k->daily_usage_count : 0 }} permintaan</td>
                <td>{{ $k->last_used_at ? $k->last_used_at->timezone('Asia/Jakarta')->format('d/m H:i') : '-' }}</td>
                <td>
                    <div class="row-actions" style="margin-top:0">
                        <form method="POST" action="{{ route('keys.test', $k) }}">
                            @csrf
                            <button type="submit">Tes</button>
                        </form>
                        <form method="POST" action="{{ route('keys.toggle', $k) }}">
                            @csrf
                            <button type="submit">{{ $k->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        </form>
                        <form method="POST" action="{{ route('keys.destroy', $k) }}"
                              data-confirm="Hapus key ini?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="danger">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">Belum ada API key.</td></tr>
        @endforelse
    </table>
</div>

<div class="card" style="max-width:520px">
    <h3 style="margin-top:0">Tambah Kunci Layanan AI</h3>
    <form method="POST" action="{{ route('keys.store') }}">
        @csrf
        <label>Label (nama bebas, misalnya "akun-gmail-2")</label>
        <input type="text" name="label" value="{{ old('label') }}" required>
        <br><br>
        <label>API key (diawali gsk_)</label>
        <input type="password" name="api_key" autocomplete="off" required>
        <br><br>
        <button type="submit">Simpan Kunci</button>
    </form>
    <p class="muted">
        Batas pemakaian Groq biasanya dihitung per akun/organisasi, jadi dua key dari akun yang sama
        umumnya berbagi kuota yang sama. Rotasi baru terasa manfaatnya kalau key berasal dari akun
        berbeda. Baca juga ketentuan layanan Groq tentang penggunaan banyak akun.
    </p>
</div>
@endsection