@props(['name' => null])
@php
    $paths = [
        'stethoscope' => 'M6 2v6a4 4 0 0 0 8 0V2M10 12v3a5 5 0 0 0 10 0v-2',
        'heart' => 'M4.3 12.7 12 21l7.7-8.3a5 5 0 0 0-7-7.1L12 6.3l-.7-.7a5 5 0 0 0-7 7.1z',
        'syringe' => 'M18 2l4 4M17 7l3-3M19 9 8.7 19.3a1 1 0 0 1-.7.3H5v-3a1 1 0 0 1 .3-.7L15.6 5.6M9 11l4 4',
    ];
    $d = $paths[$name] ?? 'M12 5v14M5 12h14'; // ikon default: plus
@endphp
<svg {{ $attributes->merge(['class' => 'h-6 w-6']) }} fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $d }}"/>
</svg>
