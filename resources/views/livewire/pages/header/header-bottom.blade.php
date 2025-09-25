<div x-data="{
    open: $wire.entangle('open'),
    active: $wire.entangle('activeId'),
    showRoots: $wire.entangle('showRoots'),
    }"
    class="relative">
    <div class="mx-auto max-w-7xl items-center pr-4 pl-1 flex justify-between">
        {{-- Кнопка каталог --}}
        <button type="button" @click="$wire.toggleCatalog()" :aria-expanded="open.toString()"
            class="py-2 px-3 flex-none flex items-center gap-1 mr-2 font-bold mb-1 text-brand-800 hover:bg-gray-200">
            <span class="uppercase cursor-pointer">Каталог</span>
        </button>

        {{-- Swiper JS --}}
        <div class="flex-1 min-w-0 hidden sm:block">
            <div class="link-swiper relative" wire:ignore>
                <button type="button"
                    class="js-link-swiper-prev absolute left-0 top-1/2 -translate-y-1/2 z-10 flex items-center justify-center h-5 w-5 rounded-full bg-white/80 shadow hover:bg-white"
                    aria-label="Прокрутить влево">
                    <x-heroicon-o-arrow-small-left class="text-brand-600/70" />
                </button>

                <div class="js-link-swiper swiper w-full min-w-0 overflow-hidden select-none">
                    <div class="swiper-wrapper">
                        @foreach ($rootCategories as $category)
                            @php $cid = (int) $category['id']; @endphp
                            <div class="swiper-slide !w-auto bg-white hover:bg-zinc-100">
                                <button type="button" @click="$wire.select({{ $cid }})"
                                    class="inline-flex items-center gap-1 whitespace-nowrap pl-3 py-1.5 text-xs text-zinc-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 uppercase font-semibold"
                                    :aria-pressed="(active === {{ $cid }}) ? 'true' : 'false'"
                                    :aria-expanded="(open && active === {{ $cid }}) ? 'true' : 'false'">
                                    <span>{{ $category['name'] }}</span>

                                    {{-- Иконки: вниз по умолчанию, вверх когда эта же категория активна и панель открыта --}}
                                    <span class="inline-block relative w-5 h-5">
                                        <span x-show="!(open && active === {{ $cid }})"
                                            x-transition.opacity.duration.150ms
                                            class="absolute inset-0 flex items-center justify-center"
                                            aria-hidden="true">
                                            <x-iconpark-down class="size-5" />
                                        </span>
                                        <span x-show="open && active === {{ $cid }}"
                                            x-transition.opacity.duration.150ms
                                            class="absolute inset-0 flex items-center justify-center"
                                            aria-hidden="true">
                                            <x-iconpark-up class="size-5" />
                                        </span>
                                    </span>
                                </button>
                            </div>
                        @endforeach

                    </div>
                </div>

                <button type="button"
                    class="js-link-swiper-next absolute right-0 top-1/2 -translate-y-1/2 z-10 flex items-center justify-center h-5 w-5 rounded-full bg-white/70 shadow hover:bg-white"
                    aria-label="Прокрутить вправо">
                    <x-heroicon-o-arrow-small-right class="text-brand-600/70" />
                </button>
            </div>
        </div>

        {{-- Блок с корзиной и авторизацией (как у тебя) --}}
        <div class="flex items-center gap-3 justify-end pl-6 text-zinc-500">
            <x-heroicon-o-heart class="size-6" />
            <x-ri-bar-chart-horizontal-fill class="size-6" />
            <x-heroicon-o-shopping-cart class="size-6" />

            @auth
                <flux:dropdown class="" position="bottom" align="start">
                    <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()"
                        icon:trailing="chevrons-up-down" />
                    <flux:menu class="w-[220px]">
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                        <span
                                            class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                            {{ auth()->user()->initials() }}
                                        </span>
                                    </span>
                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                        <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>
                        <flux:menu.separator />
                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>
                                {{ __('Settings') }}</flux:menu.item>
                        </flux:menu.radio.group>
                        <flux:menu.separator />
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                                class="w-full">
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            @else
                <x-heroicon-o-user class="size-6" />
            @endauth
        </div>
    </div>

    {{-- Панель контента --}}
    <div x-show="open" wire:transition.opacity.scale.origin.top x-cloak
        class="max-w-7xl mx-auto bg-white shadow absolute left-0 right-0 border border-zinc-200 overflow-hidden"
        role="region" aria-label="Каталог">
            @if ($showRoots)
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 p-6">
                    @foreach ($rootCategories as $cat)
                        <button type="button"
                            class="group flex items-center gap-3 p-2 rounded hover:bg-zinc-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            @click="$wire.select({{ (int) $cat['id'] }})">
                            @if (!empty($cat['img']))
                                <img src="{{ asset($cat['img']) }}" alt="{{ $cat['name'] }}"
                                    class="h-10 w-10 object-cover rounded" />
                            @endif
                            <span
                                class="text-sm font-medium text-zinc-800 group-hover:text-brand-700">{{ $cat['name'] }}</span>
                        </button>
                    @endforeach
                </div>
            @else
                {{-- Режим детей выбранной категории --}}
                @if ($activeId && count($panel))
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 p-6">
                        @foreach ($panel as $item)
                            <a href="{{ url($item['slug']) }}"
                                class="group flex items-center gap-3 p-2 rounded hover:bg-zinc-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                                @if (!empty($item['img']))
                                    <img src="{{ asset($item['img']) }}" alt="{{ $item['name'] }}"
                                        class="h-10 w-10 object-cover rounded" />
                                @endif
                                <span
                                    class="text-sm font-medium text-zinc-800 group-hover:text-brand-700">{{ $item['name'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            @endif


        <div class="px-6 pb-4 flex gap-4">
            <button type="button" @click="$wire.toggleCatalog()" class="text-xs text-zinc-500 hover:text-zinc-700">
                Закрыть
            </button>
        </div>
    </div>
</div>

