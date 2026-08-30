@props(['sumber' => null])
@if(!empty($sumber))
@php
    $color = match (strtoupper($sumber)) {
        'BOS' => ['bg' => '#EBF5FB', 'text' => '#1A5276', 'border' => '#1A5276'],
        'BOP' => ['bg' => '#F4ECF7', 'text' => '#6C3483', 'border' => '#6C3483'],
        default => ['bg' => '#F2F3F4', 'text' => '#5B6470', 'border' => '#5B6470'],
    };
@endphp
<span style="font-size:10px; padding:2px 7px; border-radius:4px; white-space:nowrap;
    background:{{ $color['bg'] }}; color:{{ $color['text'] }};
    border:1px solid {{ $color['border'] }}">
    {{ strtoupper($sumber) }}
</span>
@endif
