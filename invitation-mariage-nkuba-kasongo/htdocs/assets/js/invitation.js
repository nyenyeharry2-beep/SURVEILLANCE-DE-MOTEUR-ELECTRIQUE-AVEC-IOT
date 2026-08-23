(function () {
  'use strict';

  const PRESETS = {
    'mariage-civil': {
      date: 'Vendredi, le 11 Septembre 2026',
      time: '11h00',
      venue: 'Commune de Kipushi, Ville de KIPUSHI',
      thumb: 'assets/template_mariage_civil.png'
    },
    'affiche-blanche': {
      date: 'Samedi 12 Septembre 2026',
      time: '14h00',
      venue: 'Kipushi',
      thumb: 'assets/template_affiche_blanche.png'
    }
  };

  const STORAGE_KEY = 'moise_sarah_config';
  const UPLOAD_CACHE = {
    couple: 'assets/uploads/couple_photo.jpg',
    poster_civil: 'assets/uploads/poster_civil.jpg',
    poster_blanche: 'assets/uploads/poster_blanche.jpg'
  };

  let currentStyle = 'mariage-civil';

  function loadConfig() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return;
      const cfg = JSON.parse(raw);
      if (cfg.date) document.getElementById('cfgDate').value = cfg.date;
      if (cfg.time) document.getElementById('cfgTime').value = cfg.time;
      if (cfg.venue) document.getElementById('cfgVenue').value = cfg.venue;
      if (cfg.message) document.getElementById('cfgMessage').value = cfg.message;
      if (cfg.embedName !== undefined) document.getElementById('cfgEmbedName').checked = cfg.embedName;
      if (cfg.style) currentStyle = cfg.style;
    } catch (e) { /* ignore */ }
  }

  function saveConfig() {
    const cfg = {
      date: document.getElementById('cfgDate').value,
      time: document.getElementById('cfgTime').value,
      venue: document.getElementById('cfgVenue').value,
      message: document.getElementById('cfgMessage').value,
      embedName: document.getElementById('cfgEmbedName').checked,
      style: currentStyle
    };
    localStorage.setItem(STORAGE_KEY, JSON.stringify(cfg));
    updateHeroPoster();
  }

  function getFormData() {
    const embed = document.getElementById('cfgEmbedName')?.checked !== false;
    const guest = document.getElementById('guestName')?.value || 'Invité';
    return {
      guest: embed ? guest : ' ',
      table: document.getElementById('tableNum')?.value || '—',
      seats: document.getElementById('seats')?.value || '1',
      date: document.getElementById('cfgDate')?.value || PRESETS[currentStyle].date,
      time: document.getElementById('cfgTime')?.value || PRESETS[currentStyle].time,
      venue: document.getElementById('cfgVenue')?.value || PRESETS[currentStyle].venue
    };
  }

  function generateUrl() {
    const d = getFormData();
    const params = new URLSearchParams({
      style: currentStyle,
      guest: d.guest.trim(),
      table: d.table,
      seats: d.seats,
      date: d.date,
      time: d.time,
      venue: d.venue,
      _: Date.now()
    });
    return 'api/generate.php?' + params.toString();
  }

  function updatePreviewImage() {
    const img = document.getElementById('previewImage');
    if (img) img.src = generateUrl();
  }

  function updateHeroPoster() {
    const thumb = PRESETS[currentStyle]?.thumb;
    const hero = document.getElementById('heroPoster');
    const configPoster = document.getElementById('configPoster');
    const posterUpload = currentStyle === 'affiche-blanche'
      ? UPLOAD_CACHE.poster_blanche
      : UPLOAD_CACHE.poster_civil;
    const src = posterUpload + '?t=' + Date.now();
    if (hero) hero.src = src;
    if (configPoster) configPoster.src = src;
    if (!hero?.complete) hero.onerror = () => { hero.src = thumb; };
  }

  function refreshLogos() {
    const logoSrc = UPLOAD_CACHE.couple + '?t=' + Date.now();
    ['homeLogo', 'configLogo'].forEach(id => {
      const el = document.getElementById(id);
      if (el) {
        el.src = logoSrc;
        el.onerror = () => { el.src = 'assets/couple_photo.png'; };
      }
    });
    const status = document.getElementById('photoStatus');
    if (status) status.textContent = '✓ Photos chargées — logo et affiches actifs';
  }

  async function uploadPhoto(input, type) {
    const file = input.files?.[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('type', type);
    fd.append('photo', file);
    try {
      const res = await fetch('api/upload.php', { method: 'POST', body: fd });
      const json = await res.json();
      if (!json.success) throw new Error(json.error || 'Upload échoué');
      refreshLogos();
      updateHeroPoster();
      const status = document.getElementById('photoStatus');
      if (status) status.textContent = '✓ ' + json.message;
    } catch (e) {
      alert('Erreur upload: ' + e.message + '\n\nSur GitHub Pages, hébergez sur un serveur PHP local.');
      /* Fallback: aperçu local immédiat */
      const url = URL.createObjectURL(file);
      if (type === 'couple') {
        ['homeLogo', 'configLogo'].forEach(id => {
          const el = document.getElementById(id);
          if (el) el.src = url;
        });
      }
    }
  }

  function setStyle(style) {
    currentStyle = style;
    document.querySelectorAll('.style-thumb').forEach(t => {
      t.classList.toggle('active', t.dataset.style === style);
    });
    updateHeroPoster();
  }

  function showScreen(id) {
    document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
    document.getElementById('screen-' + id)?.classList.add('active');
    window.scrollTo(0, 0);
    if (id === 'preview') updatePreviewImage();
  }

  function formatWhatsAppMessage() {
    let msg = document.getElementById('cfgMessage')?.value || '';
    const d = getFormData();
    return msg.replace('{NAME}', d.guest.trim())
      .replace('{DATE}', d.date).replace('{VENUE}', d.venue)
      .replace('{TABLE}', d.table).replace('{SEATS}', d.seats);
  }

  function sendWhatsApp() {
    const phone = (document.getElementById('whatsapp')?.value || '').replace(/\D/g, '');
    const msg = encodeURIComponent(formatWhatsAppMessage());
    window.open(phone ? 'https://wa.me/' + phone + '?text=' + msg : 'https://wa.me/?text=' + msg, '_blank');
  }

  function bindEvents() {
    document.querySelectorAll('[data-nav]').forEach(el => {
      el.addEventListener('click', () => showScreen(el.dataset.nav));
    });
    document.querySelectorAll('.style-thumb').forEach(btn => {
      btn.addEventListener('click', () => setStyle(btn.dataset.style));
    });
    ['guestName', 'tableNum', 'seats', 'cfgDate', 'cfgTime', 'cfgVenue'].forEach(id => {
      document.getElementById(id)?.addEventListener('input', () => {
        if (document.getElementById('screen-preview')?.classList.contains('active')) {
          updatePreviewImage();
        }
      });
    });
    document.getElementById('btnSaveConfig')?.addEventListener('click', () => {
      saveConfig();
      showScreen('home');
    });
    document.getElementById('btnGenerate')?.addEventListener('click', () => showScreen('preview'));
    document.getElementById('btnSendWa')?.addEventListener('click', sendWhatsApp);
    document.getElementById('uploadCouple')?.addEventListener('change', e => uploadPhoto(e.target, 'couple'));
    document.getElementById('uploadPosterCivil')?.addEventListener('change', e => uploadPhoto(e.target, 'poster_civil'));
    document.getElementById('uploadPosterBlanche')?.addEventListener('change', e => uploadPhoto(e.target, 'poster_blanche'));
  }

  document.addEventListener('DOMContentLoaded', () => {
    loadConfig();
    bindEvents();
    setStyle(currentStyle);
    refreshLogos();
    document.querySelectorAll('.style-thumb').forEach(t => {
      t.classList.toggle('active', t.dataset.style === currentStyle);
    });
  });
})();
