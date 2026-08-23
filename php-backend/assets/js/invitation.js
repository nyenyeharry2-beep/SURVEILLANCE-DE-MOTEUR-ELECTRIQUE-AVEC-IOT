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
  let currentStyle = 'mariage-civil';
  let invitationsLoaded = false;

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
    syncEventFields();
    updateHeroPoster();
    updatePreview();
  }

  function getEventFromConfig() {
    return {
      date: document.getElementById('cfgDate')?.value || PRESETS[currentStyle].date,
      time: document.getElementById('cfgTime')?.value || PRESETS[currentStyle].time,
      venue: document.getElementById('cfgVenue')?.value || PRESETS[currentStyle].venue
    };
  }

  function syncEventFields() {
    const ev = getEventFromConfig();
    /* champs invité héritent de la config */
  }

  function qrUrl(data) {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=130x130&data=' + encodeURIComponent(data);
  }

  function getFormData() {
    const ev = getEventFromConfig();
    const embed = document.getElementById('cfgEmbedName')?.checked !== false;
    const guest = document.getElementById('guestName')?.value || 'Invité';
    return {
      guest: embed ? guest : ' ',
      table: document.getElementById('tableNum')?.value || '—',
      seats: document.getElementById('seats')?.value || '1',
      date: ev.date,
      time: ev.time,
      venue: ev.venue
    };
  }

  function fillPoster(posterEl, data) {
    if (!posterEl) return;
    const qrData = 'INVITE|' + data.guest.trim() + '|TABLE:' + data.table + '|' + data.date;
    posterEl.querySelectorAll('[data-field="guest"]').forEach(el => { el.textContent = data.guest; });
    posterEl.querySelectorAll('[data-field="table"]').forEach(el => { el.textContent = data.table; });
    posterEl.querySelectorAll('[data-field="table2"]').forEach(el => { el.textContent = data.table; });
    posterEl.querySelectorAll('[data-field="seats"]').forEach(el => { el.textContent = data.seats; });
    posterEl.querySelectorAll('[data-field="date"]').forEach(el => { el.textContent = data.date; });
    posterEl.querySelectorAll('[data-field="time"]').forEach(el => { el.textContent = data.time; });
    posterEl.querySelectorAll('[data-field="venue"]').forEach(el => { el.textContent = data.venue; });
    posterEl.querySelectorAll('[data-field="qr"]').forEach(el => { el.src = qrUrl(qrData); });
  }

  function updatePreview() {
    if (!invitationsLoaded) return;
    const data = getFormData();
    fillPoster(document.getElementById('posterCivil'), data);
    fillPoster(document.getElementById('posterBlanche'), data);
    syncPreviewScreen();
  }

  function syncPreviewScreen() {
    const host = document.getElementById('posterScaler');
    const preview = document.getElementById('previewScaler');
    if (!host || !preview) return;

    const civil = document.getElementById('posterCivil');
    const blanche = document.getElementById('posterBlanche');
    const active = currentStyle === 'mariage-civil' ? civil : blanche;
    if (active) {
      preview.innerHTML = active.outerHTML;
      const hidden = preview.querySelector('.invitation-hidden');
      if (hidden) hidden.classList.remove('invitation-hidden');
    }
  }

  function updateHeroPoster() {
    const thumb = PRESETS[currentStyle]?.thumb || PRESETS['mariage-civil'].thumb;
    ['heroPoster', 'configPoster'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.src = thumb;
    });
  }

  function setStyle(style) {
    currentStyle = style;
    document.querySelectorAll('.style-thumb').forEach(t => {
      t.classList.toggle('active', t.dataset.style === style);
    });

    const civil = document.getElementById('posterCivil');
    const blanche = document.getElementById('posterBlanche');
    if (civil) civil.classList.toggle('invitation-hidden', style !== 'mariage-civil');
    if (blanche) blanche.classList.toggle('invitation-hidden', style !== 'affiche-blanche');

    const preset = PRESETS[style];
    if (preset && !localStorage.getItem(STORAGE_KEY)) {
      document.getElementById('cfgDate').value = preset.date;
      document.getElementById('cfgTime').value = preset.time;
      document.getElementById('cfgVenue').value = preset.venue;
    }
    updateHeroPoster();
    updatePreview();
  }

  function showScreen(id) {
    document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
    const screen = document.getElementById('screen-' + id);
    if (screen) {
      screen.classList.add('active');
      window.scrollTo(0, 0);
    }
    if (id === 'preview') {
      updatePreview();
      syncPreviewScreen();
    }
  }

  function formatWhatsAppMessage() {
    let msg = document.getElementById('cfgMessage')?.value || '';
    const data = getFormData();
    msg = msg.replace('{NAME}', data.guest.trim())
      .replace('{DATE}', data.date)
      .replace('{VENUE}', data.venue)
      .replace('{TABLE}', data.table)
      .replace('{SEATS}', data.seats);
    return msg;
  }

  function sendWhatsApp() {
    const phone = (document.getElementById('whatsapp')?.value || '').replace(/\D/g, '');
    const msg = encodeURIComponent(formatWhatsAppMessage());
    const url = phone
      ? 'https://wa.me/' + phone + '?text=' + msg
      : 'https://wa.me/?text=' + msg;
    window.open(url, '_blank');
  }

  async function loadInvitations() {
    const container = document.getElementById('posterScaler');
    if (!container) return;

    const [civilHtml, blancheHtml] = await Promise.all([
      fetch('assets/invitations/mariage_civil.html').then(r => r.text()),
      fetch('assets/invitations/affiche_blanche.html').then(r => r.text())
    ]);

    container.innerHTML = civilHtml + blancheHtml;
    invitationsLoaded = true;
    setStyle(currentStyle);
  }

  function bindEvents() {
    document.querySelectorAll('[data-nav]').forEach(el => {
      el.addEventListener('click', () => showScreen(el.dataset.nav));
    });

    document.querySelectorAll('.style-thumb').forEach(btn => {
      btn.addEventListener('click', () => setStyle(btn.dataset.style));
    });

    ['guestName', 'tableNum', 'seats', 'cfgDate', 'cfgTime', 'cfgVenue'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.addEventListener('input', updatePreview);
    });

    document.getElementById('btnSaveConfig')?.addEventListener('click', () => {
      saveConfig();
      showScreen('home');
    });

    document.getElementById('btnGenerate')?.addEventListener('click', () => {
      updatePreview();
      showScreen('preview');
    });

    document.getElementById('btnSendWa')?.addEventListener('click', sendWhatsApp);
  }

  document.addEventListener('DOMContentLoaded', async () => {
    loadConfig();
    bindEvents();
    await loadInvitations();
    updateHeroPoster();
    document.querySelectorAll('.style-thumb').forEach(t => {
      t.classList.toggle('active', t.dataset.style === currentStyle);
    });
  });
})();
