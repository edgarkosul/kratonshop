import Swiper from 'swiper';
import {
    A11y,
    FreeMode,
    Mousewheel,
    Navigation,
    EffectFade,
    Autoplay,
    Pagination,
} from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/free-mode';
import 'swiper/css/mousewheel';
import 'swiper/css/effect-fade';
import 'swiper/css/pagination';
import collapse from '@alpinejs/collapse'

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.link-swiper').forEach((wrap) => {
        const el = wrap.querySelector('.js-link-swiper');

        const swiper = new Swiper(el, {
            modules: [A11y, FreeMode, Mousewheel, Navigation],
            slidesPerView: 'auto',
            spaceBetween: 0,

            // главное — свободный режим без снапа
            freeMode: {
                enabled: true,
                sticky: false,
                momentum: true,
                momentumBounce: true,
                momentumRatio: 0.5,
                momentumVelocityRatio: 0.5,
            },

            // если внутри ещё есть горизонтальные/вертикальные скроллы
            nested: true,
            mousewheel: { forceToAxis: false },

            // защита от резких отскоков у края
            resistanceRatio: 0,          // не тянуть за пределы
            speed: 300,
            a11y: { enabled: true },
            navigation: {
                nextEl: wrap.querySelector('.js-link-swiper-next'),
                prevEl: wrap.querySelector('.js-link-swiper-prev'),
            },
        });
    });
});

document.querySelectorAll('.js-hero-swiper').forEach((el) => {
    // Пагинация — локально внутри конкретного hero
    const paginationEl = el.querySelector('.swiper-pagination');

    const options = {
        modules: [EffectFade, Autoplay, Pagination],
        effect: 'fade',
        fadeEffect: { crossFade: true },
        speed: 700,
        loop: true,
        allowTouchMove: false, // без свайпов
        autoplay: {
            delay: 4000,
            disableOnInteraction: false,
        },
    };

    // Добавляем пагинацию только если элемент найден (безопасно)
    if (paginationEl) {
        options.pagination = {
            el: paginationEl,
            clickable: true,
        };
    }

    new Swiper(el, options);
});

