const swiper = new Swiper('.timeline-swiper', {
  slidesPerView: 1.15,
  slidesPerGroup: 1,
  spaceBetween: 18,
  loop: false,
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