@props([
    'href',
    'active' => false,
    'extraClass' => ''
])

<li {{ $attributes->class([$extraClass]) }}>
    <a href="{{ $href }}"
       class="
            inline-block px-3 py-2 transition-colors
            hover:text-brand-700 hover:bg-white/70
            focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500
            focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-200 whitespace-nowrap
            {{ $active ? 'text-brand-700 bg-white/80' : 'text-brand-600' }}
       "
       aria-current="{{ $active ? 'page' : 'false' }}">
        {{ $slot }}
    </a>
</li>
