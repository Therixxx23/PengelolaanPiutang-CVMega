@props(['status' => null])
@if($status)
@php
    [$label, $color] = match ($status) {
        'belum_ditagih' => ['Belum Ditagih', '#5B6470'],
        'sedang_ditagih' => ['Sedang Ditagih', '#C8862A'],
        'janji_bayar' => ['Janji Bayar', '#6B7CA3'],
        'sudah_ditagih' => ['Sudah Ditagih', '#3E7C58'],
        default => [$status, '#5B6470'],
    };
@endphp
<span style="font-size:10px; padding:2px 7px; border-radius:4px; white-space:nowrap;
    background:{{ $color }}20; color:{{ $color }}; border:1px solid {{ $color }}">
    {{ $label }}
</span>
@endif
