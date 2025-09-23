import Swiper from 'swiper';
import { FreeMode, Mousewheel, A11y, Navigation } from 'swiper/modules';

import 'swiper/css';
import 'swiper/css/free-mode';
import 'swiper/css/mousewheel';
import 'swiper/css/navigation';

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.link-swiper').forEach((wrap) => {
    const el = wrap.querySelector('.js-link-swiper');

    new Swiper(el, {
      modules: [FreeMode, Mousewheel, A11y, Navigation],
      slidesPerView: 'auto',
      spaceBetween: 8,
      freeMode: true,
      mousewheel: { forceToAxis: true },
      a11y: { enabled: true },
      navigation: {
        nextEl: wrap.querySelector('.js-link-swiper-next'),
        prevEl: wrap.querySelector('.js-link-swiper-prev'),
      },
    });
  });
});
