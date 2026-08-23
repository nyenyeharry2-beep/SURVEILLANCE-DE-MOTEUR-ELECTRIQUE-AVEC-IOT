(function () {
  'use strict';

  const PRESETS = {
    'mariage-civil': {
      date: 'Vendredi, le 11 Septembre 2026',
      time: '11h00',
      venue: 'Commune de Kipushi, Ville de KIPUSHI',
      template: 'assets/invitations/mariage_civil.html'
    },
    'affiche-blanche': {
      date: 'Samedi 12 Septembre 2026',
      time: '14h00',
      venue: 'Kipushi',
      template: 'assets/invitations/affiche_blanche.html'
    }
  };

  const STORAGE_KEY = 'moise_sarah_config';
  let branding = {
    poster_civil: 'assets/template_mariage_civil.png',
    poster_blanche: 'assets/template_affiche_blanche.png',
    couple: 'assets/couple_photo.png'
  };
  let currentStyle = 'mariage-civil';
  let templateCache = {};

  function bust(url) {
    return url + (url.includes('?') ? '&' : '?') + 'v=' + Date.now();
  }

  async function loadBranding() {
    try {
      const res = await fetch('api/branding.php');
      const json = await res.json();
      if (json.success) {
        branding = json;
      }
    } catch (e) { /* defaults */ }
    updateHeroPoster();
    refreshLogos();
  }

  function loadConfig() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return;
      const cfg = JSON.parse(raw);
      if (cfg.date) document.getElementById('cfgDate').value = cfg.date;
      if (cfg.time) document.getElementById('cfgTime').value = cfg.time;
      if (cfg.venue) document.getElementById('cfgVenue').value = cfg.venue;
      if (cfg.message) document.getElementById('cfgMessage').value = cfg.message;
      if (cfg.style) currentStyle = cfg.style;
    } catch (e) { /* ignore */ }
    const embed = document.getElementById('cfgEmbedName');
    if (embed) embed.checked = true;
  }

  function saveConfig() {
    const cfg = {
      date: document.getElementById('cfgDate').value,
      time: document.getElementById('cfgTime').value,
      venue: document.getElementById('cfgVenue').value,
      message: document.getElementById('cfgMessage').value,
      style: currentStyle
    };
    localStorage.setItem(STORAGE_KEY, JSON.stringify(cfg));
    updateHeroPoster();
  }

  function getFormData() {
    const guest = (document.getElementById('guestName')?.value || '').trim();
    return {
      guest: guest || 'Invité',
      table: document.getElementById('tableNum')?.value?.trim() || '—',
      seats: document.getElementById('seats')?.value || '1',
      whatsapp: document.getElementById('whatsapp')?.value?.trim() || '',
      date: document.getElementById('cfgDate')?.value || PRESETS[currentStyle].date,
      time: document.getElementById('cfgTime')?.value || PRESETS[currentStyle].time,
      venue: document.getElementById('cfgVenue')?.value || PRESETS[currentStyle].venue
    };
  }

  function qrUrl(data) {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(data);
  }

  function posterUrl() {
    return bust(currentStyle === 'affiche-blanche' ? branding.poster_blanche : branding.poster_civil);
  }

  async function loadTemplate(style) {
    if (templateCache[style]) return templateCache[style];
    const path = PRESETS[style].template;
    const html = await fetch(path).then(r => r.text());
    templateCache[style] = html;
    return html;
  }

  async function renderHtmlPreview(targetId) {
    const d = getFormData();
    const host = document.getElementById(targetId);
    if (!host) return;

    const html = await loadTemplate(currentStyle);
    host.innerHTML = html;

    const qrData = 'INVITE|nom:' + d.guest + '|table:' + d.table + '|date:' + d.date + '|places:' + d.seats;

    host.querySelector('[data-bind="poster"]')?.setAttribute('src', posterUrl());
    host.querySelector('[data-bind="guest"]').textContent = d.guest;
    host.querySelector('[data-bind="table"]').textContent = d.table;
    host.querySelector('[data-bind="table2"]').textContent = d.table;
    host.querySelector('[data-bind="seats"]').textContent = d.seats;
    const qrImg = host.querySelector('[data-bind="qr"]');
    if (qrImg) qrImg.src = qrUrl(qrData);

    const label = document.getElementById('previewGuestLabel');
    if (label) label.textContent = d.guest;
  }

  function generateUrl() {
    const d = getFormData();
    const params = new URLSearchParams({
      style: currentStyle,
      guest: d.guest,
      table: d.table,
      seats: d.seats,
      _: Date.now()
    });
    return 'api/generate.php?' + params.toString();
  }

  function updateHeroPoster() {
    const src = posterUrl();
    ['heroPoster', 'configPoster'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.src = src;
    });
    document.querySelectorAll('.style-thumb').forEach(thumb => {
      const img = thumb.querySelector('img');
      if (!img) return;
      const url = thumb.dataset.style === 'affiche-blanche' ? branding.poster_blanche : branding.poster_civil;
      img.src = bust(url);
    });
  }

  function refreshLogos() {
    const logoSrc = bust(branding.couple);
    ['homeLogo', 'configLogo'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.src = logoSrc;
    });
  }

  async function uploadPhoto(input, type) {
    const file = input.files?.[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('type', type);
    fd.append('photo', file);
    const status = document.getElementById('photoStatus');
    if (status) status.textContent = 'Envoi en cours…';
    try {
      const res = await fetch('api/upload.php', { method: 'POST', body: fd });
      const json = await res.json();
      if (!json.success) throw new Error(json.error || 'Upload échoué');
      await loadBranding();
      if (status) status.textContent = '✓ ' + json.message;
    } catch (e) {
      if (status) status.textContent = '✗ ' + e.message;
      const url = URL.createObjectURL(file);
      if (type === 'couple') branding.couple = url;
      if (type === 'poster_civil') branding.poster_civil = url;
      if (type === 'poster_blanche') branding.poster_blanche = url;
      updateHeroPoster();
      refreshLogos();
      alert('Upload serveur échoué — aperçu local uniquement.\n' + e.message);
    }
  }

  async function saveGuest() {
    const d = getFormData();
    try {
      await fetch('api/guests.php?action=add', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          fullName: d.guest,
          whatsapp: d.whatsapp,
          tableZone: d.table,
          seats: parseInt(d.seats, 10) || 1,
          styleId: currentStyle
        })
      });
    } catch (e) { /* offline ok */ }
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
    if (id === 'preview') renderHtmlPreview('previewPoster');
  }

  function formatWhatsAppMessage() {
    let msg = document.getElementById('cfgMessage')?.value || '';
    const d = getFormData();
    return msg.replace('{NAME}', d.guest)
      .replace('{DATE}', d.date).replace('{VENUE}', d.venue)
      .replace('{TABLE}', d.table).replace('{SEATS}', d.seats);
  }

  function sendWhatsApp() {
    const phone = (document.getElementById('whatsapp')?.value || '').replace(/\D/g, '');
    const msg = encodeURIComponent(formatWhatsAppMessage());
    window.open(phone ? 'https://wa.me/' + phone + '?text=' + msg : 'https://wa.me/?text=' + msg, '_blank');
  }

  async function onGenerate() {
    const name = (document.getElementById('guestName')?.value || '').trim();
    if (!name) {
      alert('Saisissez le nom de l\'invité');
      document.getElementById('guestName')?.focus();
      return;
    }
    await saveGuest();
    showScreen('preview');
  }

  function bindEvents() {
    document.querySelectorAll('[data-nav]').forEach(el => {
      el.addEventListener('click', () => showScreen(el.dataset.nav));
    });
    document.querySelectorAll('.style-thumb').forEach(btn => {
      btn.addEventListener('click', () => setStyle(btn.dataset.style));
    });
    ['guestName', 'tableNum', 'seats'].forEach(id => {
      document.getElementById(id)?.addEventListener('input', () => {
        if (document.getElementById('screen-preview')?.classList.contains('active')) {
          renderHtmlPreview('previewPoster');
        }
      });
    });
    document.getElementById('btnSaveConfig')?.addEventListener('click', () => {
      saveConfig();
      showScreen('home');
    });
    document.getElementById('btnGenerate')?.addEventListener('click', onGenerate);
    document.getElementById('btnSendWa')?.addEventListener('click', sendWhatsApp);
    document.getElementById('uploadCouple')?.addEventListener('change', e => uploadPhoto(e.target, 'couple'));
    document.getElementById('uploadPosterCivil')?.addEventListener('change', e => uploadPhoto(e.target, 'poster_civil'));
    document.getElementById('uploadPosterBlanche')?.addEventListener('change', e => uploadPhoto(e.target, 'poster_blanche'));
  }

  document.addEventListener('DOMContentLoaded', async () => {
    loadConfig();
    bindEvents();
    await loadBranding();
    setStyle(currentStyle);
    document.querySelectorAll('.style-thumb').forEach(t => {
      t.classList.toggle('active', t.dataset.style === currentStyle);
    });
  });
})();
