@props(['values' => [], 'labels' => [], 'unit' => '', 'height' => 120, 'color' => 'var(--ink)', 'showLabels' => true, 'dec' => 0])
@php $max = max(array_merge($values, [0])) ?: 1; @endphp
<div class="bn-bars" style="--bh:{{ $height }}px; --bc:{{ $color }}">
    @foreach($values as $i => $v)
        <div>
            <i style="--h:{{ round(($v / $max) * 100, 1) }}%; animation-delay:{{ $i * 25 }}ms"
               title="{{ $labels[$i] ?? '' }}: {{ number_format($v, $dec, ',', '.') }} {{ $unit }}"></i>
        </div>
    @endforeach
</div>
@if($showLabels)
    <div class="bn-bars-lbl">@foreach($labels as $l)<span>{{ $l }}</span>@endforeach</div>
@elseif(count($labels) > 1)
    <div class="bn-bars-lbl" style="justify-content:space-between"><span style="flex:none">{{ $labels[0] }}</span><span style="flex:none">{{ $labels[count($labels) - 1] }}</span></div>
@endif