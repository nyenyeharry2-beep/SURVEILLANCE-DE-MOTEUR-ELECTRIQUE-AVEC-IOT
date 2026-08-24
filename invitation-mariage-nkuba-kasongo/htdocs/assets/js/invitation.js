(function () {
  'use strict';

  const V = document.body.dataset.version || '2.9.2';
  const PRESETS = {
    'mariage-civil': { template: 'assets/invitations/mariage_civil.html' },
    'affiche-blanche': { template: 'assets/invitations/affiche_blanche.html' }
  };

  let branding = window.NKUBA_BRANDING || {
    couple: 'assets/couple_photo.jpg'
  };
  let currentStyle = 'mariage-civil';
  let guestSort = 'name';
  let guestFilter = '';
  let guestsCache = [];
  let lastGuestId = null;
  let guestUniqueId = null;
  let templateCache = {};

  function bust(url) {
    return url + (url.includes('?') ? '&' : '?') + 't=' + Date.now();
  }

  function uniqueGuestId() {
    return Date.now().toString(36).toUpperCase() + '-' + Math.random().toString(36).slice(2, 8).toUpperCase();
  }

  function ensureGuestId() {
    if (!guestUniqueId) guestUniqueId = uniqueGuestId();
    return guestUniqueId;
  }

  function resetGuestId(id) {
    guestUniqueId = id || uniqueGuestId();
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

  function buildDefaultQrData() {
    const d = getFormData();
    const id = ensureGuestId();
    return 'INVITE|nom:' + d.guest + '|id:' + id + '|table:' + d.table + '|places:' + d.seats;
  }

  function getQrPayload() {
    const el = document.getElementById('qrData');
    const custom = (el?.value || '').trim();
    if (custom) return custom;
    return buildDefaultQrData();
  }

  function syncQrDataField(force) {
    const el = document.getElementById('qrData');
    if (!el) return;
    if (force || el.dataset.manual !== '1') {
      el.value = buildDefaultQrData();
    }
  }

  function coupleUrl() {
    return bust(branding.couple || 'assets/couple_photo.jpg');
  }

  function bindPosterData(root, d, qrData) {
    root.querySelectorAll('[data-bind="couple"]').forEach(el => { el.src = coupleUrl(); });
    root.querySelectorAll('[data-bind="guest"]').forEach(el => { el.textContent = d.guest; });
    root.querySelectorAll('[data-bind="table"]').forEach(el => { el.textContent = d.table; });
    root.querySelector('[data-bind="table2"]') && (root.querySelector('[data-bind="table2"]').textContent = d.table);
    root.querySelector('[data-bind="seats"]') && (root.querySelector('[data-bind="seats"]').textContent = d.seats);
    root.querySelectorAll('[data-bind="date"]').forEach(el => { el.textContent = d.date || 'Vendredi, le 11 Septembre 2026'; });
    root.querySelectorAll('[data-bind="time"]').forEach(el => { el.textContent = d.time || '11h00'; });
    root.querySelectorAll('[data-bind="venue"]').forEach(el => { el.textContent = d.venue || 'Commune de Kipushi, Ville de KIPUSHI'; });
    renderQrCode(root.querySelector('[data-bind="qr"]'), qrData);
  }

  function posterUrl() {
    return coupleUrl();
  }

  function renderQrCode(container, data) {
    if (!container || typeof QRCode === 'undefined') return;
    container.innerHTML = '';
    const size = 184;
    /* eslint-disable no-new */
    new QRCode(container, {
      text: data,
      width: size,
      height: size,
      colorDark: '#000000',
      colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.M
    });
  }

  function generateUrl() {
    const d = getFormData();
    return 'api/generate.php?' + new URLSearchParams({
      style: currentStyle,
      guest: d.guest,
      table: d.table,
      seats: d.seats,
      qr: getQrPayload(),
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
    const qrData = getQrPayload();
    bindPosterData(host, d, qrData);
    const label = document.getElementById('previewGuestLabel');
    if (label) label.textContent = d.guest;
    const qrPreview = document.getElementById('qrDataPreview');
    if (qrPreview) qrPreview.textContent = qrData;
  }

  async function renderHeroPreview() {
    const host = document.getElementById('heroPreview');
    if (!host) return;
    const saved = currentStyle;
    currentStyle = 'mariage-civil';
    host.innerHTML = await loadTemplate('mariage-civil');
    bindPosterData(host, {
      guest: 'Nom invité',
      table: '—',
      seats: '2',
      date: document.getElementById('cfgDate')?.value || 'Vendredi, le 11 Septembre 2026',
      time: document.getElementById('cfgTime')?.value || '11h00',
      venue: document.getElementById('cfgVenue')?.value || 'Commune de Kipushi, Ville de KIPUSHI'
    }, 'INVITE|demo|id:PREVIEW');
    currentStyle = saved;
    await renderConfigPreview();
  }

  async function renderConfigPreview() {
    const host = document.getElementById('configPreview');
    if (!host) return;
    host.innerHTML = await loadTemplate('mariage-civil');
    bindPosterData(host, {
      guest: 'Nom invité',
      table: '—',
      seats: '2',
      date: document.getElementById('cfgDate')?.value || 'Vendredi, le 11 Septembre 2026',
      time: document.getElementById('cfgTime')?.value || '11h00',
      venue: document.getElementById('cfgVenue')?.value || 'Commune de Kipushi, Ville de KIPUSHI'
    }, 'INVITE|demo|id:CONFIG');
  }

  async function capturePosterBlob() {
    const el = document.querySelector('#previewPoster .poster');
    if (!el) throw new Error('Aperçu introuvable');
    if (typeof html2canvas === 'undefined') throw new Error('html2canvas non chargé');
    const canvas = await html2canvas(el, {
      scale: 1,
      width: 1200,
      height: 1700,
      useCORS: true,
      backgroundColor: '#ffffff',
      logging: false,
      onclone: (doc) => {
        doc.querySelectorAll('.poster-scaler').forEach(n => { n.style.transform = 'none'; n.style.marginBottom = '0'; });
      }
    });
    return new Promise((resolve, reject) => {
      canvas.toBlob(b => b ? resolve(b) : reject(new Error('Export PNG échoué')), 'image/png', 1);
    });
  }

  async function fetchInvitationBlob() {
    if (document.getElementById('screen-preview')?.classList.contains('active')) {
      return capturePosterBlob();
    }
    await renderHtmlPreview('previewPoster');
    return capturePosterBlob();
  }

  function updateHeroPoster() {
    renderHeroPreview();
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
    resetGuestId('G' + g.id);
    const qrEl = document.getElementById('qrData');
    if (qrEl) {
      qrEl.dataset.manual = '0';
      syncQrDataField(true);
    }
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
    if (id === 'config') renderConfigPreview();
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

  async function downloadPng() {
    try {
      const blob = await fetchInvitationBlob();
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'invitation-' + getFormData().guest.replace(/\s+/g, '-') + '.png';
      a.click();
      URL.revokeObjectURL(url);
    } catch (e) {
      alert('Téléchargement: ' + e.message);
    }
  }

  async function onGenerate() {
    const name = (document.getElementById('guestName')?.value || '').trim();
    if (!name) {
      alert('Saisissez le nom de l\'invité');
      return;
    }
    syncQrDataField(true);
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

    const liveFields = ['guestName', 'tableNum', 'seats', 'qrData'];
    liveFields.forEach(id => {
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('input', () => {
        if (id === 'qrData') {
          el.dataset.manual = el.value.trim() ? '1' : '0';
        }
        if (id !== 'qrData') syncQrDataField(false);
        if (document.getElementById('screen-preview')?.classList.contains('active')) {
          renderHtmlPreview('previewPoster');
        }
      });
    });

    document.getElementById('btnRegenQr')?.addEventListener('click', () => {
      resetGuestId();
      const qrEl = document.getElementById('qrData');
      if (qrEl) qrEl.dataset.manual = '0';
      syncQrDataField(true);
      if (document.getElementById('screen-preview')?.classList.contains('active')) {
        renderHtmlPreview('previewPoster');
      }
    });

    document.getElementById('btnSaveConfig')?.addEventListener('click', async () => {
      await saveConfigToServer();
      showScreen('home');
    });
    document.getElementById('btnGenerate')?.addEventListener('click', onGenerate);
    document.getElementById('btnSendWa')?.addEventListener('click', sendWhatsApp);
    document.getElementById('btnDownloadPng')?.addEventListener('click', downloadPng);
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
    document.getElementById('uploadCoupleHome')?.addEventListener('change', e => uploadPhoto(e.target, 'couple'));
  }

  document.addEventListener('DOMContentLoaded', async () => {
    resetGuestId();
    bindEvents();
    syncQrDataField(true);
    await loadBranding();
    await loadConfigFromServer();
    await loadGuestList();
    setStyle(currentStyle);
    await renderHeroPreview();
    setInterval(loadGuestList, 30000);
    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) {
        loadGuestList();
        loadConfigFromServer();
      }
    });
  });

  window.NkubaInv = {
    renderHtmlPreview,
    capturePosterBlob,
    fetchInvitationBlob,
    setStyle: (s) => { currentStyle = s; }
  };
})();
