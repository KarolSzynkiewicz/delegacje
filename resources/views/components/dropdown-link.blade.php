@php
    $classes = 'block w-full px-4 py-2 text-start text-sm leading-5 transition duration-150 ease-in-out';
    if (isset($active) && $active) {
        $classes .= ' bg-gray-100 text-gray-900';
    } else {
        $classes .= ' text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100';
    }
@endphp
<a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
