@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block px-3 py-2 text-sm font-medium text-ink bg-paper rounded-sm transition border-l-[3px] border-action'
            : 'block px-3 py-2 text-sm font-medium text-ink-muted rounded-sm transition border-l-[3px] border-transparent hover:text-ink hover:bg-paper';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
