@props(['lines' => [], 'labels' => [], 'w' => 320, 'h' => 150, 'max' => null, 'empty' => 'Belum ada data.'])
@php
    $id = 'sp' . uniqid();
    $L = 6; $R = 38; $T = 10; $B = 10; $base = $h - $B;
    $all = [];
    foreach ($lines as $ln) { foreach ($ln['data'] as $v) { if ($v !== null) { $all[] = $v; } } }
    $top  = $max ?? ($all ? max($all) : 0);
    $top  = $top > 0 ? $top : 1;
    $n    = $lines ? count($lines[0]['data']) : 0;
    $step = $n > 1 ? ($w - $L - $R) / ($n - 1) : 0;
    $abbr = function ($v) {
        if ($v >= 1000000) { return rtrim(rtrim(number_format($v / 1000000, 1, '.', ''), '0'), '.') . 'M'; }
        if ($v >= 1000)    { return rtrim(rtrim(number_format($v / 1000, 1, '.', ''), '0'), '.') . 'K'; }
        return (string) (int) round($v);
    };

    $built = [];
    foreach ($lines as $ln) {
        $pts = [];
        foreach ($ln['data'] as $i => $v) {
            if ($v === null) { continue; }
            $pts[] = [$L + $i * $step, $T + (1 - min($v / $top, 1)) * ($base - $T)];
        }
        $dd = '';
        foreach ($pts as $k => $p) {
            if ($k === 0) { $dd .= sprintf('M%.1F %.1F', $p[0], $p[1]); continue; }
            $q = $pts[$k - 1]; $m = ($q[0] + $p[0]) / 2;
            $dd .= sprintf(' C%.1F %.1F %.1F %.1F %.1F %.1F', $m, $q[1], $m, $p[1], $p[0], $p[1]);
        }
        $last = $pts ? $pts[count($pts) - 1] : null;
        $area = (count($pts) > 1 && ($ln['area'] ?? false))
            ? $dd . sprintf(' L%.1F %.1F L%.1F %.1F Z', $last[0], $base, $pts[0][0], $base) : '';
        $built[] = ['d' => $dd, 'area' => $area, 'last' => $last, 'color' => $ln['color'], 'dash' => $ln['dash'] ?? false];
    }
    $hasData = array_sum($all) > 0;
@endphp
<svg class="ux-spark" viewBox="0 0 {{ $w }} {{ $h }}" role="img" aria-label="Grafik tren">
    <defs>
        @foreach($built as $bi => $b)
            <linearGradient id="{{ $id }}-{{ $bi }}" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" style="stop-color:{{ $b['color'] }};stop-opacity:.28"/>
                <stop offset="1" style="stop-color:{{ $b['color'] }};stop-opacity:0"/>
            </linearGradient>
        @endforeach
    </defs>

    @foreach([1, .75, .5, .25] as $g)
        <line class="gl" x1="{{ $L }}" x2="{{ $w - $R + 6 }}" y1="{{ $T + (1 - $g) * ($base - $T) }}" y2="{{ $T + (1 - $g) * ($base - $T) }}"/>
        <text x="{{ $w - 2 }}" y="{{ $T + (1 - $g) * ($base - $T) + 3 }}" text-anchor="end">{{ $abbr($top * $g) }}</text>
    @endforeach

    @if($hasData)
        @foreach($built as $bi => $b)
            @if($b['area'])<path d="{{ $b['area'] }}" fill="url(#{{ $id }}-{{ $bi }})"/>@endif
            <path d="{{ $b['d'] }}" class="ln" style="stroke:{{ $b['color'] }}" @if($b['dash']) stroke-dasharray="4 4" @endif/>
            @if($b['last'])<circle cx="{{ $b['last'][0] }}" cy="{{ $b['last'][1] }}" r="3.6" style="fill:{{ $b['color'] }}"/>@endif
        @endforeach

        @for($i = 0; $i < $n; $i++)
            @php $hx = $L + $i * $step; @endphp
            <g class="pt">
                <line class="v" x1="{{ $hx }}" x2="{{ $hx }}" y1="{{ $T }}" y2="{{ $base }}"/>
                <rect class="hit" x="{{ $hx - max($step, 6) / 2 }}" y="0" width="{{ max($step, 6) }}" height="{{ $base }}">
                    <title>{{ $labels[$i] ?? '' }}@foreach($lines as $ln) · {{ $ln['name'] ?? '' }}: {{ number_format($ln['data'][$i] ?? 0, 0, ',', '.') }}@endforeach</title>
                </rect>
            </g>
        @endfor
    @else
        <text x="{{ ($w - $R) / 2 }}" y="{{ $h / 2 }}" text-anchor="middle">{{ $empty }}</text>
    @endif
</svg>