@props(['items' => []])

<nav class="bg-zinc-200 border-b border-zinc-300 hidden md:block" aria-label="Верхнее меню сайта">
    <ul class="mx-auto max-w-7xl px-4 flex justify-start items-center uppercase h-10 font-semibold text-brand-600">
        @foreach ($items as $item)
            <x-top-nav-link
                href=""
                :active="request()->routeIs($item['route'])"
                :extra-class="$loop->last ? 'ml-auto' : ''"
            >
                {{ $item['label'] }}
            </x-top-nav-link>
        @endforeach
    </ul>
</nav>
