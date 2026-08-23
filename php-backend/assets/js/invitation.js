(function () {
  'use strict';

  const PRESETS = {
    'mariage-civil': {
      date: 'Vendredi, le 11 Septembre 2026',
      time: '11h00',
      venue: 'Commune de Kipushi, Ville de KIPUSHI'
    },
    'affiche-blanche': {
      date: 'Samedi 12 Septembre 2026',
      time: '14h00',
      venue: 'Kipushi'
    }
  };

  let currentStyle = 'mariage-civil';

  function qrUrl(data) {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=130x130&data=' + encodeURIComponent(data);
  }

  function getFormData() {
    return {
      guest: document.getElementById('guestName')?.value || 'Invité',
      table: document.getElementById('tableNum')?.value || '—',
      seats: document.getElementById('seats')?.value || '1',
      date: document.getElementById('eventDate')?.value || '',
      time: document.getElementById('eventTime')?.value || '',
      venue: document.getElementById('eventVenue')?.value || ''
    };
  }

  function fillPoster(posterEl, data) {
    if (!posterEl) return;
    const qrData = 'INVITE|' + data.guest + '|TABLE:' + data.table + '|' + data.date;

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
    const data = getFormData();
    fillPoster(document.getElementById('posterCivil'), data);
    fillPoster(document.getElementById('posterBlanche'), data);
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
    if (preset) {
      const dateEl = document.getElementById('eventDate');
      const timeEl = document.getElementById('eventTime');
      const venueEl = document.getElementById('eventVenue');
      if (dateEl) dateEl.value = preset.date;
      if (timeEl) timeEl.value = preset.time;
      if (venueEl) venueEl.value = preset.venue;
    }
    updatePreview();
  }

  function setScale() {
    const frame = document.querySelector('.poster-frame');
    if (!frame) return;
    const w = frame.clientWidth - 8;
    const scale = Math.min(0.32, Math.max(0.22, w / 1200));
    const scaler = document.getElementById('posterScaler');
    if (scaler) scaler.style.setProperty('--inv-scale', scale);
  }

  async function loadInvitations() {
    const container = document.getElementById('posterScaler');
    if (!container) return;

    const [civilHtml, blancheHtml] = await Promise.all([
      fetch('assets/invitations/mariage_civil.html').then(r => r.text()),
      fetch('assets/invitations/affiche_blanche.html').then(r => r.text())
    ]);

    container.innerHTML = civilHtml + blancheHtml;
    setStyle(currentStyle);
    setScale();
  }

  function bindEvents() {
    document.querySelectorAll('.style-thumb').forEach(btn => {
      btn.addEventListener('click', () => setStyle(btn.dataset.style));
    });

    ['guestName', 'tableNum', 'seats', 'eventDate', 'eventTime', 'eventVenue'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.addEventListener('input', updatePreview);
    });

    const btnGen = document.getElementById('btnGenerate');
    if (btnGen) {
      btnGen.addEventListener('click', () => {
        updatePreview();
        document.querySelector('.invitation-section')?.scrollIntoView({ behavior: 'smooth' });
      });
    }

    window.addEventListener('resize', setScale);
  }

  document.addEventListener('DOMContentLoaded', async () => {
    bindEvents();
    await loadInvitations();
  });
})();
