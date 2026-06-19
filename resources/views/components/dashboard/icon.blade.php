@props(['name', 'class' => 'h-5 w-5'])
@php
$paths = [
    'grid' => 'M3 3h7v7H3V3Zm11 0h7v7h-7V3ZM3 14h7v7H3v-7Zm11 0h7v7h-7v-7Z',
    'card' => 'M3 7h18M5 5h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm2 10h4',
    'tag' => 'M20 13 13 20 4 11V4h7l9 9Zm-12-5h.01',
    'wallet' => 'M20 7H5a2 2 0 0 0 0 4h15v8H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h13v3Zm-2 8h.01',
    'chart' => 'M4 19V5m0 14h16M8 16v-5m4 5V8m4 8v-9',
    'settings' => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm0-13v3m0 14v3m10-10h-3M5 12H2m17.07-7.07-2.12 2.12M7.05 16.95l-2.12 2.12m14.14 0-2.12-2.12M7.05 7.05 4.93 4.93',
];
@endphp
<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="{{ $paths[$name] ?? $paths['grid'] }}" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
</svg>
