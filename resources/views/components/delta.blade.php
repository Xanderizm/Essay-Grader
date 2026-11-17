@props(['v' => null, 'suffix' => '%'])
@if($v === null)
    <span class="ux-delta" style="color:var(--muted)">—</span>
@else
    <span class="ux-delta {{ $v >= 0 ? 'up' : 'down' }}">{{ $v >= 0 ? '↑' : '↓' }} {{ number_format(abs($v), 1, ',', '.') }}{{ $suffix }}</span>
@endif