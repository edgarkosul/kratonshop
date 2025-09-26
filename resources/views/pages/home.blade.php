<x-layout :title="$title" :topMenu="$topMenu" :sideMenu="$sideMenu" :rootCategories="$rootCategories">
    <section class="w-full">
        <div class="mx-auto max-w-7xl px-4">
            <!-- Hero slider wrapper keeps aspect/height -->
            <div class="relative">
                <!-- высота/пропорции: на мобиле h-56, на md — 16:9 -->
                <div class="js-hero-swiper">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <img src="/pics/iuscrhtml000007.png" alt="Слайд 1" class="w-full h-auto" loading="lazy">
                        </div>
                        <div class="swiper-slide">
                            <img src="/pics/iuscrhtml000009.png" alt="Слайд 2" class="w-full h-auto" loading="lazy">
                        </div>
                        <div class="swiper-slide">
                            <img src="/pics/iuscrhtml000043.png" alt="Слайд 3" class="w-full h-auto" loading="lazy">
                        </div>
                        <div class="swiper-slide">
                            <img src="/pics/iuscrhtml000044.png" alt="Слайд 4" class="w-full h-auto" loading="lazy">
                        </div>
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10">
        <h2 class="text-xl font-semibold">Промоблок / контент</h2>
        <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
            @foreach ($promoGrid as $item)
                <a href="{{ $item['url'] }}" class="h-24 rounded-lg border flex items-center justify-center text-sm">
                    {{ $item['title'] }}
                </a>
            @endforeach
        </div>
    </section>
</x-layout>
