@props(['series' => [], 'labels' => [], 'unit' => '', 'dec' => 0, 'max' => null, 'w' => 640, 'h' => 220, 'empty' => 'Belum ada data.'])
@php
    $id  = 'bn' . uniqid();
    $n   = count($series);
    $L = 6; $R = 6; $T = 14; $B = 24;
    $vals = array_values(array_filter($series, fn ($v) => $v !== null));
    $top  = $max ?? ($vals ? max($vals) : 0);
    $top  = $top > 0 ? $top : 1;
    $step = $n > 1 ? ($w - $L - $R) / ($n - 1) : 0;
    $base = $h - $B;

    $pts = [];
    foreach ($series as $i => $v) {
        if ($v === null) { continue; }
        $pts[] = [$L + $i * $step, $T + (1 - min($v / $top, 1)) * ($h - $T - $B), $v, $i];
    }

    $line = '';
    foreach ($pts as $k => $p) {
        if ($k === 0) { $line .= sprintf('M%.1F %.1F', $p[0], $p[1]); continue; }
        $q = $pts[$k - 1]; $m = ($q[0] + $p[0]) / 2;
        $line .= sprintf(' C%.1F %.1F %.1F %.1F %.1F %.1F', $m, $q[1], $m, $p[1], $p[0], $p[1]);
    }
    $last = $pts ? $pts[count($pts) - 1] : null;
    $area = count($pts) > 1 ? $line . sprintf(' L%.1F %.1F L%.1F %.1F Z', $last[0], $base, $pts[0][0], $base) : '';
    $f    = fn ($v) => number_format($v, $dec, ',', '.');
    $hitW = max($step, 8);
@endphp
<svg class="bn-chart" viewBox="0 0 {{ $w }} {{ $h }}" role="img" aria-label="Grafik tren">
    <defs>
        <linearGradient id="{{ $id }}" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" style="stop-color:var(--lime);stop-opacity:.75"/>
            <stop offset="1" style="stop-color:var(--lime);stop-opacity:0"/>
        </linearGradient>
    </defs>

    @foreach([0, .5, 1] as $g)
        <line class="gl" x1="{{ $L }}" x2="{{ $w - $R }}" y1="{{ $T + $g * ($base - $T) }}" y2="{{ $T + $g * ($base - $T) }}"/>
    @endforeach

    @if($pts)
        @if($area)<path d="{{ $area }}" fill="url(#{{ $id }})"/>@endif
        <path class="ln" d="{{ $line }}"/>
        @foreach($pts as $p)
            <g class="bn-pt">
                <line class="v" x1="{{ $p[0] }}" x2="{{ $p[0] }}" y1="{{ $T }}" y2="{{ $base }}"/>
                <circle class="d" cx="{{ $p[0] }}" cy="{{ $p[1] }}" r="4.5"/>
                <rect class="hit" x="{{ $p[0] - $hitW / 2 }}" y="0" width="{{ $hitW }}" height="{{ $base }}">
                    <title>{{ $labels[$p[3]] ?? '' }}: {{ $f($p[2]) }} {{ $unit }}</title>
                </rect>
            </g>
        @endforeach
        <circle class="lastdot" cx="{{ $last[0] }}" cy="{{ $last[1] }}" r="4"/>
        <text x="{{ $L }}" y="10">{{ $f($top) }} {{ $unit }}</text>
    @else
        <text x="{{ $w / 2 }}" y="{{ $h / 2 }}" text-anchor="middle">{{ $empty }}</text>
    @endif

    @if(count($labels) > 2)
        <text x="{{ $L }}" y="{{ $h - 6 }}">{{ $labels[0] }}</text>
        <text x="{{ $w / 2 }}" y="{{ $h - 6 }}" text-anchor="middle">{{ $labels[(int) floor(count($labels) / 2)] }}</text>
        <text x="{{ $w - $R }}" y="{{ $h - 6 }}" text-anchor="end">{{ $labels[count($labels) - 1] }}</text>
    @endif
</svg>