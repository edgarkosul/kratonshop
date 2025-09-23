@props([
    'topMenu' => [],
    'sideMenu' => [],
])

<div x-data="{ open: false }" class="border-b border-gray-200">
    <header>
        <!-- Верхнее мерню -->
        <nav class="bg-zinc-200 border-b border-zinc-300 hidden md:block" aria-label="Верхнее меню сайта">
            <ul
                class="mx-auto max-w-7xl px-4 flex justify-start items-center uppercase h-10 font-semibold text-brand-600">
                <x-top-nav-link href="/about" :active="request()->is('about')">
                    О компании
                </x-top-nav-link>

                <x-top-nav-link href="/delivery" :active="request()->is('delivery')">
                    Оплата и доставка
                </x-top-nav-link>

                <x-top-nav-link href="/reviews" :active="request()->is('reviews')">
                    Отзывы
                </x-top-nav-link>

                <x-top-nav-link href="/contacts" :active="request()->is('contacts')">
                    Контакты
                </x-top-nav-link>

                <x-top-nav-link href="/legal" :active="request()->is('legal')" class="ml-auto">
                    Реквизиты
                </x-top-nav-link>
            </ul>
        </nav>
        <!-- Основной блок -->
        <div
            class="mx-auto max-w-7xl flex lg:flex-nowrap flex-wrap justify-between items-center gap-4 px-4 my-4 md:my-6">
            <!-- Logo -->
            <div class="order-1 min-w-52">
                <x-icon name="logo" class="max-h-12 w-auto" />
            </div>



            <div class="order-4 basis-full lg:basis-auto lg:order-2 lg:flex-1 flex gap-3">
                <!-- Бургер -->
                <button type="button" class="h-9 w-9 md:hidden" aria-controls="side-menu"
                    :aria-expanded="open.toString()" @click="open = true" aria-label="Открыть меню">
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

    </header>

    <div class="hidden border-t border-gray-200 md:block">
        <x-nav :items="$topMenu" />
    </div>

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
</div>
<div class="px-4">
    <div class="link-swiper relative" wire:ignore>
        <!-- Кнопки навигации (необязательно) -->
        <button type="button"
            class="js-link-swiper-prev absolute left-0 top-1/2 -translate-y-1/2 z-10 flex items-center justify-center h-7 w-7 rounded-full bg-white/80 shadow hover:bg-white"
            aria-label="Прокрутить влево">
            <x-heroicon-o-arrow-left-circle class="text-brand-600/70"/>
        </button>

        <div class="js-link-swiper swiper select-none">
            <div class="swiper-wrapper">
                <div class="swiper-slide !w-auto">
                    <a href="#"
                        class="inline-block whitespace-nowrap rounded-full border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        Ссылка 1
                    </a>
                </div>
                <div class="swiper-slide !w-auto">
                    <a href="#"
                        class="inline-block whitespace-nowrap rounded-full border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        Длинная ссылка 2
                    </a>
                </div>
                <div class="swiper-slide !w-auto">
                    <a href="#"
                        class="inline-block whitespace-nowrap rounded-full border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        Тест 3
                    </a>
                </div>
                <div class="swiper-slide !w-auto">
                    <a href="#"
                        class="inline-block whitespace-nowrap rounded-full border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        Ссылка 4
                    </a>
                </div>
                <div class="swiper-slide !w-auto">
                    <a href="#"
                        class="inline-block whitespace-nowrap rounded-full border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        Очень длинная ссылка 5
                    </a>
                </div>
                <div class="swiper-slide !w-auto">
                    <a href="#"
                        class="inline-block whitespace-nowrap rounded-full border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        Ссылка 6
                    </a>
                </div>
            </div>
        </div>
        <button type="button"
            class="js-link-swiper-next absolute right-0 top-1/2 -translate-y-1/2 z-10 flex items-center justify-center h-7 w-7 rounded-full bg-white/70 shadow  hover:bg-white"
            aria-label="Прокрутить вправо">
            <x-heroicon-o-arrow-right-circle class="text-brand-600/70"/>
        </button>

    </div>
</div>
