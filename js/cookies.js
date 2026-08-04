(function () {
  const banner = document.getElementById('cookieBanner');
  const modal = document.getElementById('cookieModal');

  // banner is required to show cookie notice; modal is optional
  if (!banner) return;

  const btnAcceptAll = document.getElementById('btnAcceptAll');
  const btnReject = document.getElementById('btnReject');
  const btnConfig = document.getElementById('btnConfig');
  const btnCloseModal = document.getElementById('btnCloseModal');
  const btnSavePreferences = document.getElementById('btnSavePreferences');
  const analyticToggle = document.getElementById('analyticToggle');
  const adsToggle = document.getElementById('adsToggle');

  function updateWhatsappState() {
    const whatsapp = document.querySelector('.whatsapp-float');
    if (!whatsapp || !banner) return;
    if (banner.classList.contains('visible')) {
      whatsapp.style.display = 'none';
    } else {
      whatsapp.style.display = 'inline-flex';
      whatsapp.style.bottom = '18px';
    }
  }

  function closeBanner() {
    banner.classList.remove('visible');
    banner.classList.add('hidden');
    if (modal) modal.classList.remove('active');
    // After the CSS transition hides the banner, set display:none to remove its layout footprint
    var onTransitionEnd = function () {
      banner.style.display = 'none';
      banner.removeEventListener('transitionend', onTransitionEnd);
      updateWhatsappState();
    };
    banner.addEventListener('transitionend', onTransitionEnd);
    // Also update immediately as a fallback
    updateWhatsappState();
  }

  function showBanner() {
    // Make sure banner is part of layout before animating in
    banner.style.display = 'flex';
    banner.classList.remove('hidden');
    banner.classList.add('visible');
    if (modal) modal.classList.remove('active');
    updateWhatsappState();
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

  function setActiveNavbarLink() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const navLinks = document.querySelectorAll('.new-nav .navbar-nav .nav-link');

    navLinks.forEach(link => {
      const linkPage = link.getAttribute('href').split('/').pop();
      if (linkPage === currentPage || (currentPage === 'index.html' && linkPage === 'index.html')) {
        link.classList.add('active');
      }
    });
  }

  window.addEventListener('DOMContentLoaded', () => {
    applyConsentState();
    setActiveNavbarLink();
  });
})();
