(function () {
  'use strict';

  const V = document.body.dataset.version || '2.7.0';
  const PRESETS = {
    'mariage-civil': { template: 'assets/invitations/mariage_civil.html' },
    'affiche-blanche': { template: 'assets/invitations/affiche_blanche.html' }
  };

  let branding = window.NKUBA_BRANDING || {
    poster_civil: 'assets/template_mariage_civil.png',
    poster_blanche: 'assets/template_affiche_blanche.png',
    couple: 'assets/couple_photo.png'
  };
  let currentStyle = 'mariage-civil';
  let guestSort = 'name';
  let guestFilter = '';
  let guestsCache = [];
  let lastGuestId = null;
  let templateCache = {};

  function bust(url) {
    return url + (url.includes('?') ? '&' : '?') + 't=' + Date.now();
  }

  async function loadBranding() {
    try {
      const json = await fetch('api/branding.php').then(r => r.json());
      if (json.success) branding = json;
    } catch (e) { /* ok */ }
    updateHeroPoster();
    refreshLogos();
  }

  async function loadConfigFromServer() {
    try {
      const json = await fetch('api/config.php').then(r => r.json());
      if (!json.success) return;
      const c = json.config;
      if (c.event_date) document.getElementById('cfgDate').value = c.event_date;
      if (c.event_time) document.getElementById('cfgTime').value = c.event_time;
      if (c.event_venue) document.getElementById('cfgVenue').value = c.event_venue;
      if (c.whatsapp_message) document.getElementById('cfgMessage').value = c.whatsapp_message;
    } catch (e) { /* offline */ }
  }

  async function saveConfigToServer() {
    const body = {
      event_date: document.getElementById('cfgDate').value,
      event_time: document.getElementById('cfgTime').value,
      event_venue: document.getElementById('cfgVenue').value,
      whatsapp_message: document.getElementById('cfgMessage').value
    };
    try {
      await fetch('api/config.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      });
    } catch (e) { /* ok */ }
  }

  function getFormData() {
    const guest = (document.getElementById('guestName')?.value || '').trim();
    return {
      guest: guest || 'Invité',
      table: document.getElementById('tableNum')?.value?.trim() || '—',
      seats: document.getElementById('seats')?.value || '1',
      whatsapp: document.getElementById('whatsapp')?.value?.trim() || '',
      date: document.getElementById('cfgDate')?.value || '',
      time: document.getElementById('cfgTime')?.value || '',
      venue: document.getElementById('cfgVenue')?.value || ''
    };
  }

  function posterUrl() {
    return bust(currentStyle === 'affiche-blanche' ? branding.poster_blanche : branding.poster_civil);
  }

  function qrUrl(data) {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(data);
  }

  function generateUrl() {
    const d = getFormData();
    return 'api/generate.php?' + new URLSearchParams({
      style: currentStyle,
      guest: d.guest,
      table: d.table,
      seats: d.seats,
      _: Date.now()
    });
  }

  async function loadTemplate(style) {
    if (templateCache[style]) return templateCache[style];
    templateCache[style] = await fetch(PRESETS[style].template).then(r => r.text());
    return templateCache[style];
  }

  async function renderHtmlPreview(targetId) {
    const d = getFormData();
    const host = document.getElementById(targetId);
    if (!host) return;
    host.innerHTML = await loadTemplate(currentStyle);
    const qrData = 'INVITE|nom:' + d.guest + '|table:' + d.table + '|places:' + d.seats;
    host.querySelector('[data-bind="poster"]')?.setAttribute('src', posterUrl());
    host.querySelector('[data-bind="guest"]').textContent = d.guest;
    host.querySelectorAll('[data-bind="table"]').forEach(el => { el.textContent = d.table; });
    host.querySelector('[data-bind="table2"]') && (host.querySelector('[data-bind="table2"]').textContent = d.table);
    host.querySelector('[data-bind="seats"]').textContent = d.seats;
    const qrImg = host.querySelector('[data-bind="qr"]');
    if (qrImg) qrImg.src = qrUrl(qrData);
    const label = document.getElementById('previewGuestLabel');
    if (label) label.textContent = d.guest;
  }

  async function fetchInvitationBlob() {
    const res = await fetch(generateUrl());
    if (!res.ok) throw new Error('Génération image échouée');
    return res.blob();
  }

  function updateHeroPoster() {
    const src = posterUrl();
    ['heroPoster', 'configPoster'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.src = src;
    });
    document.querySelectorAll('.style-thumb img').forEach(img => {
      const thumb = img.closest('.style-thumb');
      const url = thumb?.dataset.style === 'affiche-blanche' ? branding.poster_blanche : branding.poster_civil;
      if (url) img.src = bust(url);
    });
  }

  function refreshLogos() {
    const src = bust(branding.couple);
    ['homeLogo', 'configLogo'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.src = src;
    });
  }

  async function uploadPhoto(input, type) {
    const file = input.files?.[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('type', type);
    fd.append('photo', file);
    const status = document.getElementById('photoStatus') || document.getElementById('homeUploadStatus');
    if (status) status.textContent = 'Envoi…';
    try {
      const json = await fetch('api/upload.php', { method: 'POST', body: fd }).then(r => r.json());
      if (!json.success) throw new Error(json.error || 'Upload échoué');
      if (status) status.textContent = '✓ ' + json.message;
      setTimeout(() => window.location.reload(), 700);
    } catch (e) {
      if (status) status.textContent = '✗ ' + e.message;
      alert('Upload: ' + e.message);
    }
  }

  async function loadGuestList() {
    try {
      const json = await fetch('api/guests.php?action=list&sort=' + guestSort).then(r => r.json());
      if (json.success) guestsCache = json.guests || [];
    } catch (e) {
      guestsCache = [];
    }
    renderGuestList();
  }

  function renderGuestList() {
    const el = document.getElementById('guestListBody');
    const stats = document.getElementById('guestListStats');
    if (!el) return;

    let list = guestsCache;
    if (guestFilter) {
      const q = guestFilter.toLowerCase();
      list = list.filter(g =>
        (g.fullName || '').toLowerCase().includes(q) ||
        (g.whatsapp || '').includes(q) ||
        (g.tableZone || '').toLowerCase().includes(q)
      );
    }

    const seats = list.reduce((s, g) => s + (g.seats || 1), 0);
    if (stats) stats.textContent = list.length + ' invité(s) • ' + seats + ' place(s)';

    if (!list.length) {
      el.innerHTML = '<p class="hint" style="padding:16px;text-align:center">Aucun invité. Ajoutez-en un pour générer une invitation.</p>';
      return;
    }

    el.innerHTML = list.map(g => `
      <div class="guest-row-item" data-id="${g.id}">
        <div class="guest-row-main">
          <strong>${esc(g.fullName)}</strong>
          ${g.sent ? '<span class="badge-sent">Envoyé</span>' : ''}
          <div class="guest-row-meta">
            📱 ${esc(g.whatsapp || '—')} &nbsp;•&nbsp; 🪑 ${esc(g.tableZone || '—')} &nbsp;•&nbsp; ${g.seats} pl.
          </div>
        </div>
        <div class="guest-row-actions">
          <button type="button" class="btn-mini" data-preview-guest="${g.id}">Voir</button>
        </div>
      </div>
    `).join('');

    el.querySelectorAll('[data-preview-guest]').forEach(btn => {
      btn.addEventListener('click', () => openGuestPreview(parseInt(btn.dataset.previewGuest, 10)));
    });
  }

  function esc(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
  }

  function openGuestPreview(id) {
    const g = guestsCache.find(x => x.id === id);
    if (!g) return;
    document.getElementById('guestName').value = g.fullName || '';
    document.getElementById('whatsapp').value = g.whatsapp || '';
    document.getElementById('tableNum').value = g.tableZone || '';
    document.getElementById('seats').value = g.seats || 1;
    currentStyle = g.styleId || 'mariage-civil';
    lastGuestId = g.id;
    setStyle(currentStyle);
    showScreen('preview');
  }

  async function saveGuest() {
    const d = getFormData();
    try {
      const res = await fetch('api/guests.php?action=add', {
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
      const json = await res.json();
      if (json.success) lastGuestId = json.id;
    } catch (e) { /* offline */ }
    await loadGuestList();
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
    if (id === 'guests') loadGuestList();
  }

  function formatWhatsAppMessage() {
    let msg = document.getElementById('cfgMessage')?.value || '';
    const d = getFormData();
    return msg.replace('{NAME}', d.guest)
      .replace('{DATE}', d.date).replace('{VENUE}', d.venue)
      .replace('{TABLE}', d.table).replace('{SEATS}', d.seats);
  }

  async function sendWhatsApp() {
    const phone = (document.getElementById('whatsapp')?.value || '').replace(/\D/g, '');
    const msg = formatWhatsAppMessage();
    try {
      const blob = await fetchInvitationBlob();
      const file = new File([blob], 'invitation-' + getFormData().guest.replace(/\s+/g, '-') + '.png', { type: 'image/png' });
      if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
        await navigator.share({ text: msg, files: [file] });
      } else {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = file.name;
        a.click();
        URL.revokeObjectURL(url);
        const hint = encodeURIComponent(msg + '\n\n📎 Joignez la photo invitation téléchargée');
        window.open(phone ? 'https://wa.me/' + phone + '?text=' + hint : 'https://wa.me/?text=' + hint, '_blank');
      }
      if (lastGuestId) {
        await fetch('api/guests.php?action=mark_sent', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: lastGuestId })
        });
        await loadGuestList();
      }
    } catch (e) {
      alert('Partage: ' + e.message);
    }
  }

  async function onGenerate() {
    const name = (document.getElementById('guestName')?.value || '').trim();
    if (!name) {
      alert('Saisissez le nom de l\'invité');
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
    document.getElementById('btnSaveConfig')?.addEventListener('click', async () => {
      await saveConfigToServer();
      showScreen('home');
    });
    document.getElementById('btnGenerate')?.addEventListener('click', onGenerate);
    document.getElementById('btnSendWa')?.addEventListener('click', sendWhatsApp);
    document.getElementById('btnToGuestList')?.addEventListener('click', () => showScreen('guests'));
    document.getElementById('guestSearch')?.addEventListener('input', e => {
      guestFilter = e.target.value;
      renderGuestList();
    });
    document.getElementById('guestSort')?.addEventListener('change', e => {
      guestSort = e.target.value;
      loadGuestList();
    });
    document.getElementById('uploadCouple')?.addEventListener('change', e => uploadPhoto(e.target, 'couple'));
    document.getElementById('uploadPosterCivil')?.addEventListener('change', e => uploadPhoto(e.target, 'poster_civil'));
    document.getElementById('uploadPosterBlanche')?.addEventListener('change', e => uploadPhoto(e.target, 'poster_blanche'));
    document.getElementById('uploadPosterCivilHome')?.addEventListener('change', e => uploadPhoto(e.target, 'poster_civil'));
  }

  document.addEventListener('DOMContentLoaded', async () => {
    bindEvents();
    await loadBranding();
    await loadConfigFromServer();
    await loadGuestList();
    setStyle(currentStyle);
    setInterval(loadGuestList, 30000);
    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) {
        loadGuestList();
        loadConfigFromServer();
      }
    });
  });
})();
