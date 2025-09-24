@props([
    'topMenu' => [],
    'sideMenu' => [],
    'rootCategories' => [],
])

<div x-data="{ open: false }" class="border-b border-gray-200">
    <header>
        <!-- Верхнее меню -->
        <x-top-nav :items="$topMenu" />

        <!-- Основной блок -->
        <div
            class="mx-auto max-w-7xl flex lg:flex-nowrap flex-wrap justify-between items-center gap-4 px-4 my-4 md:my-6">
            <!-- Logo -->
            <div class="order-1 min-w-52">
                <x-icon name="logo" class="max-h-12 w-auto" />
            </div>



            <div class="order-4 basis-full lg:basis-auto lg:order-2 lg:flex-1 flex gap-3">
                <!-- Бургер -->
                <button type="button" class="h-9 w-9 md:hidden" aria-controls="side-menu" :aria-expanded="open.toString()"
                    @click="open = true" aria-label="Открыть меню">
                    <!-- Поиск -->
                    <x-heroicon-c-bars-3 />
                </button>
                <x-search class="flex-1" />
            </div>
            <!-- Контакты -->
            <section class="order-2 lg:order-3 text-sm md:text-base basis-full sm:basis-auto">
                <div class="flex gap-3 justify-between flex-wrap">
                    <!-- Email -->
                    <ul class="flex flex-col font-bold text-sm" aria-label="Электронная почта">
                        <li class="flex items-center gap-2">
                            <!-- mail icon -->
                            <svg class="size-5 shrink-0 -mb-1" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path
                                    d="M3 7.5A2.5 2.5 0 0 1 5.5 5h13A2.5 2.5 0 0 1 21 7.5v9A2.5 2.5 0 0 1 18.5 19h-13A2.5 2.5 0 0 1 3 16.5v-9Z"
                                    stroke="currentColor" />
                                <path d="m4 7 8 6 8-6" stroke="currentColor" />
                            </svg>
                            <a href="mailto:KratonShop@yandex.ru" class="hover:underline">KratonShop@yandex.ru</a>
                        </li>
                        <li class="flex items-center gap-2">
                            <a href="mailto:Sale@KratonShop.ru" class="hover:underline ml-8">Sale@KratonShop.ru</a>
                        </li>
                    </ul>
                    <div class="flex gap-3">
                        <!-- Телефоны -->
                        <ul class="flex flex-col text-sm" aria-label="Телефоны">
                            <li class="-mb-0.5">
                                <a href="tel:+78126421004" class="font-bold whitespace-nowrap">8 (812) 642-10-04</a>
                            </li>
                            <li class="-mt-0.5">
                                <a href="tel:+78122450692" class="font-bold whitespace-nowrap">8 (812) 245-06-92</a>
                            </li>
                            <li class="text-nowrap text-zinc-500 -mt-1">
                                ПН.-ПТ. 9.00-18.00 МСК
                            </li>
                        </ul>
                        <!-- Мессенджеры -->
                        <ul class="flex flex-col gap-1">
                            <li>
                                <a href="https://wa.me/79643421004"><x-fab-whatsapp-square
                                        class="h-7 w-auto text-[#25D366]" /></a>
                            </li>
                            <li>
                                <x-icon-max-logo class="h-6 w-auto" />
                            </li>
                        </ul>
                    </div>
                </div>
            </section>
        </div>

        <!-- Мобильное меню -->
        <div x-show="open" x-transition.opacity class="fixed inset-0 z-40 bg-black/40 md:hidden" @click="open = false">
        </div>
        <aside id="side-menu" x-show="open" x-transition:enter="transition-transform ease-out duration-200"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition-transform ease-in duration-150" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 z-50 w-80 max-w-[85%] bg-white shadow md:hidden"
            @keydown.escape.window="open = false" @click.stop aria-label="Боковое меню">
            <div class="flex items-center justify-between border-b p-4">
                <span class="text-base font-semibold">Меню</span>
                <button class="inline-flex h-8 w-8 items-center justify-center rounded-md border" @click="open = false"
                    aria-label="Закрыть меню">×</button>
            </div>
            <nav class="p-2">
                @foreach ($sideMenu as $link)
                    <a href="{{ $link['url'] }}" class="block rounded-md px-3 py-2 text-sm hover:bg-gray-50">
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>
        </aside>
        <div class="mx-auto max-w-7xl items-center px-4 flex">
            <button class="py-2 flex-none flex items-center gap-1 mr-4">
                <x-heroicon-c-bars-3 class="inline-block size-6 text-zinc-500" /><span>Каталог</span>
            </button>
            <div class="flex-1 min-w-0">
                <div class="link-swiper relative" wire:ignore>
                    <button type="button"
                        class="js-link-swiper-prev absolute left-0 top-1/2 -translate-y-1/2 z-10 flex items-center justify-center h-5 w-5 rounded-full bg-white/80 shadow hover:bg-white"
                        aria-label="Прокрутить влево">
                        <x-heroicon-o-arrow-small-left class="text-brand-600/70" />
                    </button>
                    <div class="js-link-swiper swiper w-full min-w-0 overflow-hidden select-none">
                        <div class="swiper-wrapper">
                            @foreach ($rootCategories as $category)
                                <div class="swiper-slide !w-auto bg-white hover:bg-zinc-100">
                                    <a href="#"
                                        class="inline-block whitespace-nowrap  pl-3 py-1.5 text-xs text-zinc-700  focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 uppercase font-semibold">
                                        {{ $category['name'] }}
                                    </a>
                                    <x-iconpark-down class="size-6 inline-block" />
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
            <div class="flex items-center gap-3 justify-end pl-6 text-zinc-500">

                <x-heroicon-o-heart class="size-6 " />
                <x-ri-bar-chart-horizontal-fill class="size-6" />
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
                    <x-heroicon-o-user  class="size-6"/>
                @endauth

                <x-heroicon-o-shopping-cart class="size-6" />
            </div>

        </div>

    </header>
</div>
