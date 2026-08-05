(function () {
  const banner = document.getElementById('siteNotice');
  const modal = document.getElementById('consentModal');

  // banner is required to show consent notice; modal is optional
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

  function canUseLocalStorage() {
    try {
      const testKey = '__cookieConsentTest__';
      window.localStorage.setItem(testKey, testKey);
      window.localStorage.removeItem(testKey);
      return true;
    } catch (e) {
      return false;
    }
  }

  function getConsent() {
    if (canUseLocalStorage()) {
      var stored = window.localStorage.getItem('cookieConsent');
      return stored === '' ? null : stored;
    }
    const cookieMatch = document.cookie.match(/(?:^|; )cookieConsent=([^;]+)/);
    if (!cookieMatch) return null;
    var value = decodeURIComponent(cookieMatch[1]);
    return value === '' ? null : value;
  }

  function setConsent(value) {
    if (canUseLocalStorage()) {
      window.localStorage.setItem('cookieConsent', value);
    } else {
      var date = new Date();
      date.setTime(date.getTime() + (365 * 24 * 60 * 60 * 1000));
      document.cookie = 'cookieConsent=' + encodeURIComponent(value) + ';expires=' + date.toUTCString() + ';path=/';
    }
  }

  function applyConsentState() {
    const consent = getConsent();
    if (consent !== null) {
      closeBanner();
    } else {
      showBanner();
    }
  }

  if (btnAcceptAll) {
    btnAcceptAll.addEventListener('click', () => {
      setConsent('all');
      closeBanner();
    });
  }

  if (btnReject) {
    btnReject.addEventListener('click', () => {
      setConsent('rejected');
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
