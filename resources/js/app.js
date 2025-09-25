import Swiper from 'swiper';
import { A11y, FreeMode, Mousewheel, Navigation } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/free-mode';
import 'swiper/css/mousewheel';
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
