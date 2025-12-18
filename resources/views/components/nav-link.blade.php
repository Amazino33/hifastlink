@props(['active'])

@php
$classes = ($active ?? false)
            ? 'border-b-2 border-gray-100 transition duration-150 ease-in-out'
            : '';
@endphp

<a {{ $attributes->merge(['class' => 'relative px-6 py-3 text-white font-semibold hover:text-blue-300 transition-all duration-300 group '.$classes]) }}>
    {{ $slot }}
</a>
