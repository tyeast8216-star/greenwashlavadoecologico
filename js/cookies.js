(function () {
  const banner = document.getElementById('cookieBanner');
  const modal = document.getElementById('cookieModal');

  if (!banner || !modal) return;

  const btnAcceptAll = document.getElementById('btnAcceptAll');
  const btnReject = document.getElementById('btnReject');
  const btnConfig = document.getElementById('btnConfig');
  const btnCloseModal = document.getElementById('btnCloseModal');
  const btnSavePreferences = document.getElementById('btnSavePreferences');
  const analyticToggle = document.getElementById('analyticToggle');
  const adsToggle = document.getElementById('adsToggle');

  function updateWhatsappPosition() {
    const whatsapp = document.querySelector('.whatsapp-float');
    if (!whatsapp || !banner) return;
    const bannerVisible = !banner.classList.contains('hidden');
    const offset = bannerVisible ? banner.offsetHeight + 18 : 18;
    whatsapp.style.bottom = offset + 'px';
  }

  function closeBanner() {
    banner.classList.remove('visible');
    banner.classList.add('hidden');
    modal.classList.remove('active');
    updateWhatsappPosition();
  }

  function showBanner() {
    banner.classList.remove('hidden');
    banner.classList.add('visible');
    modal.classList.remove('active');
    updateWhatsappPosition();
  }

  function applyConsentState() {
    const consent = localStorage.getItem('cookieConsent');
    if (consent !== null) {
      closeBanner();
    } else {
      showBanner();
    }
  }

  if (btnAcceptAll) {
    btnAcceptAll.addEventListener('click', () => {
      localStorage.setItem('cookieConsent', 'all');
      closeBanner();
    });
  }

  if (btnReject) {
    btnReject.addEventListener('click', () => {
      localStorage.setItem('cookieConsent', 'rejected');
      closeBanner();
    });
  }

  if (btnConfig) {
    btnConfig.addEventListener('click', () => {
      modal.classList.add('active');
    });
  }

  if (btnCloseModal) {
    btnCloseModal.addEventListener('click', () => {
      modal.classList.remove('active');
    });
  }

  if (btnSavePreferences) {
    btnSavePreferences.addEventListener('click', () => {
      const preferences = {
        analytics: analyticToggle ? analyticToggle.checked : false,
        ads: adsToggle ? adsToggle.checked : false
      };
      localStorage.setItem('cookieConsent', JSON.stringify(preferences));
      closeBanner();
    });
  }

  window.addEventListener('DOMContentLoaded', () => {
    applyConsentState();
  });
})();
