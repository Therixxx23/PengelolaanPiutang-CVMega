@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-line rounded focus:border-action focus:ring-1 focus:ring-action']) }}>
