const swiper = new Swiper('.timeline-swiper', {
  slidesPerView: 1.15,
  slidesPerGroup: 1,
  spaceBetween: 18,
  loop: true,
  autoplay: {
    delay: 4000,
    disableOnInteraction: false,
  },
  observer: true,
  observeParents: true,
  watchOverflow: true,
  initialSlide: 0,
  navigation: {
    nextEl: '.swiper-button-next',
    prevEl: '.swiper-button-prev',
  },
  breakpoints: {
    576: {
      slidesPerView: 2,
      spaceBetween: 20,
    },
    992: {
      slidesPerView: 3,
      spaceBetween: 22,
    },
    1200: {
      slidesPerView: 4,
      spaceBetween: 24,
    },
  },
  on: {
    init: function () {
      this.slideTo(0, 0);
    },
  },
});

let autoplayResumeTimer;

const resetAutoplayResume = () => {
  clearTimeout(autoplayResumeTimer);
  autoplayResumeTimer = setTimeout(() => {
    if (swiper.autoplay) swiper.autoplay.start();
  }, 15000);
};

const pauseAutoplay = () => {
  if (swiper.autoplay) swiper.autoplay.stop();
  clearTimeout(autoplayResumeTimer);
};

const swiperEl = document.querySelector('.timeline-swiper');
if (swiperEl) {
  swiperEl.addEventListener('touchstart', pauseAutoplay, { passive: true });
  swiperEl.addEventListener('pointerdown', pauseAutoplay);
  swiperEl.addEventListener('touchend', resetAutoplayResume);
  swiperEl.addEventListener('pointerup', resetAutoplayResume);
  swiperEl.addEventListener('pointercancel', resetAutoplayResume);
}

document.querySelectorAll('.swiper-button-next, .swiper-button-prev').forEach((button) => {
  button.addEventListener('click', () => {
    pauseAutoplay();
    resetAutoplayResume();
  });
});