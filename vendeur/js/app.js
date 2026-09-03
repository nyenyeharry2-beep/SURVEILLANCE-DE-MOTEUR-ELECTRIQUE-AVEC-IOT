/**
 * Application vendeur PC — Pharmacie Nouvelle Eve
 */
(() => {
  'use strict';

  const $ = (sel) => document.querySelector(sel);
  const $$ = (sel) => document.querySelectorAll(sel);

  let selectedMed = null;
  let lastVenteId = null;
  let searchTimer = null;

  // ── Utilitaires ──────────────────────────────────────────────

  function showToast(msg, isError = false) {
    const el = $('#toast');
    el.textContent = msg;
    el.className = 'toast' + (isError ? ' toast-error' : '');
    el.classList.remove('hidden');
    clearTimeout(showToast._t);
    showToast._t = setTimeout(() => el.classList.add('hidden'), 3500);
  }

  function showError(el, msg) {
    el.textContent = msg;
    el.style.display = msg ? 'block' : 'none';
  }

  function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  // ── Auth ───────────────────────────────────────────────────────

  function showApp() {
    $('#login-screen').style.display = 'none';
    $('#app-shell').classList.add('active');
    const s = Api.session();
    $('#user-label').textContent = s.user?.nom || s.user?.email || 'Vendeur';
    if (!window.matchMedia('(display-mode: standalone)').matches && !window.navigator.standalone) {
      $('#install-banner').classList.remove('hidden');
    }
    loadTab('ventes');
  }

  function showLogin() {
    Api.clearSession();
    $('#login-screen').style.display = '';
    $('#app-shell').classList.remove('active');
  }

  async function handleLogin(e) {
    e.preventDefault();
    const errEl = $('#login-error');
    showError(errEl, '');
    const btn = $('#btn-login');
    btn.disabled = true;
    btn.textContent = 'Connexion…';
    try {
      const data = await Api.login($('#email').value.trim(), $('#password').value);
      Api.saveSession({
        token: data.token,
        session_id: data.session_id,
        user: data.user,
      });
      showApp();
    } catch (ex) {
      showError(errEl, ex.message || 'Connexion impossible');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Se connecter';
    }
  }

  async function handleLogout() {
    try { await Api.logout(); } catch { /* ignore */ }
    showLogin();
  }

  // ── Navigation ─────────────────────────────────────────────────

  function loadTab(name) {
    $$('#app-nav button').forEach((b) => b.classList.toggle('active', b.dataset.tab === name));
    $$('.panel').forEach((p) => p.classList.toggle('active', p.id === `panel-${name}`));
    switch (name) {
      case 'ventes': loadMedicaments(''); break;
      case 'caisse': loadCaisse(); break;
      case 'stock': loadStock(''); break;
      case 'rapports': loadRapport(); break;
      case 'alertes': loadAlertes(); break;
    }
  }

  // ── Ventes ─────────────────────────────────────────────────────

  async function loadMedicaments(q) {
    const list = $('#med-list');
    list.innerHTML = '<p class="empty">Chargement…</p>';
    try {
      const data = await Api.getMedicaments(q);
      const meds = data.medicaments || [];
      if (!meds.length) {
        list.innerHTML = '<p class="empty">Aucun médicament trouvé.</p>';
        return;
      }
      list.innerHTML = meds.map((m) => `
        <div class="med-item" data-id="${m.id}">
          <strong>${escapeHtml(m.nom)}</strong>
          <small>${escapeHtml(m.code || '')} — Stock: ${m.quantite_stock} — ${Format.money(m.prix_vente)}</small>
        </div>
      `).join('');
      list.querySelectorAll('.med-item').forEach((el) => {
        el.addEventListener('click', () => selectMedicament(meds.find((x) => String(x.id) === el.dataset.id)));
      });
    } catch (ex) {
      list.innerHTML = `<p class="empty">${escapeHtml(ex.message)}</p>`;
    }
  }

  function selectMedicament(med) {
    selectedMed = med;
    $$('.med-item').forEach((el) => el.classList.toggle('selected', el.dataset.id === String(med.id)));
    $('#sale-form-card').style.display = '';
    $('#selected-med-name').textContent = med.nom;
    $('#selected-med-info').textContent = `Code: ${med.code || '—'} | Stock: ${med.quantite_stock} | Prix: ${Format.money(med.prix_vente)}`;
    $('#sale-qty').max = med.quantite_stock;
    $('#sale-success').classList.add('hidden');
  }

  async function submitSale() {
    if (!selectedMed) {
      showToast('Sélectionnez un médicament', true);
      return;
    }
    const qty = parseInt($('#sale-qty').value, 10) || 0;
    if (qty <= 0 || qty > selectedMed.quantite_stock) {
      showToast('Quantité invalide', true);
      return;
    }
    const btn = $('#btn-valider-vente');
    btn.disabled = true;
    try {
      const result = await Api.createVente({
        medicament_id: selectedMed.id,
        quantite: qty,
        devise: $('#sale-devise').value,
        client_nom: $('#sale-client').value.trim(),
        notes: $('#sale-notes').value.trim(),
      });
      lastVenteId = result.id;
      const box = $('#sale-success');
      box.classList.remove('hidden');
      box.innerHTML = `
        Vente enregistrée : <strong>${escapeHtml(result.numero)}</strong> — ${Format.money(result.montant_total, result.devise)}
        <br><button type="button" class="btn btn-outline btn-sm" id="btn-voir-recu" style="margin-top:8px">Voir le reçu</button>
      `;
      $('#btn-voir-recu').addEventListener('click', () => openRecu(result.id));
      showToast('Vente enregistrée');
      selectedMed = null;
      $('#sale-form-card').style.display = 'none';
      loadMedicaments($('#search-med').value.trim());
    } catch (ex) {
      showToast(ex.message, true);
    } finally {
      btn.disabled = false;
    }
  }

  async function loadHistorique() {
    const list = $('#historique-list');
    list.innerHTML = '<p class="empty">Chargement…</p>';
    try {
      const data = await Api.getHistorique(50);
      const ventes = data.ventes || [];
      if (!ventes.length) {
        list.innerHTML = '<p class="empty">Aucune vente.</p>';
        return;
      }
      list.innerHTML = ventes.map((v) => `
        <div class="move-item historique-item" data-id="${v.id}">
          <div><strong>${escapeHtml(v.numero)}</strong> — ${Format.money(v.montant_total, v.devise)}</div>
          <small>${Format.dateTime(v.date_vente)} | ${escapeHtml(v.details || '')}</small>
          <button type="button" class="btn btn-outline btn-sm btn-recu">Reçu</button>
        </div>
      `).join('');
      list.querySelectorAll('.historique-item').forEach((el) => {
        el.querySelector('.btn-recu').addEventListener('click', (e) => {
          e.stopPropagation();
          openRecu(parseInt(el.dataset.id, 10));
        });
        el.addEventListener('click', () => openRecu(parseInt(el.dataset.id, 10)));
      });
    } catch (ex) {
      list.innerHTML = `<p class="empty">${escapeHtml(ex.message)}</p>`;
    }
  }

  // ── Reçu ───────────────────────────────────────────────────────

  async function openRecu(id) {
    try {
      const data = await Api.getRecu(id);
      const v = data.vente;
      const ph = data.pharmacie;
      const lignes = (data.lignes || []).map((l) =>
        `<tr><td>${escapeHtml(l.nom)}</td><td>${l.quantite}</td><td>${Format.money(l.prix_unitaire, v.devise)}</td><td>${Format.money(l.sous_total, v.devise)}</td></tr>`
      ).join('');

      $('#recu-content').innerHTML = `
        <div class="recu-header">
          <img src="../assets/img/logo.jpg" alt="" class="recu-logo" onerror="this.style.display='none'">
          <h2>${escapeHtml(ph.nom)}</h2>
          <p>${escapeHtml(ph.tagline || '')}</p>
          <p>${escapeHtml(ph.adresse || '')}</p>
          <p>Tél: ${escapeHtml(ph.telephone || '')}</p>
        </div>
        <hr>
        <p><strong>Reçu N°</strong> ${escapeHtml(v.numero)}</p>
        <p><strong>Date</strong> ${Format.dateTime(v.date_vente)}</p>
        <p><strong>Vendeur</strong> ${escapeHtml(v.vendeur || '')}</p>
        ${v.client_nom ? `<p><strong>Client</strong> ${escapeHtml(v.client_nom)}</p>` : ''}
        <table class="recu-table">
          <thead><tr><th>Article</th><th>Qté</th><th>P.U.</th><th>Total</th></tr></thead>
          <tbody>${lignes}</tbody>
        </table>
        <p class="recu-total"><strong>Total : ${Format.money(v.montant_total, v.devise)}</strong></p>
        <p class="recu-lettres">${escapeHtml(v.montant_lettres || '')}</p>
        <p class="recu-equiv">${escapeHtml(v.equivalent || '')}</p>
        ${v.notes ? `<p><em>${escapeHtml(v.notes)}</em></p>` : ''}
        <p class="recu-footer">Merci pour votre confiance !</p>
      `;
      $('#recu-overlay').classList.remove('hidden');
    } catch (ex) {
      showToast(ex.message, true);
    }
  }

  function closeRecu() {
    $('#recu-overlay').classList.add('hidden');
  }

  function printRecu() {
    window.print();
  }

  // ── Caisse ─────────────────────────────────────────────────────

  async function loadCaisse() {
    const date = Format.today();
    $('#caisse-date').textContent = date;
    try {
      const data = await Api.getCaisse(date);
      const r = data.resume || {};
      $('#caisse-resume').textContent = [
        `Entrées : ${Format.money(r.entrees_cdf)} / ${Format.money(r.entrees_usd, 'USD')}`,
        `Sorties : ${Format.money(r.sorties_cdf)} / ${Format.money(r.sorties_usd, 'USD')}`,
        `Solde   : ${Format.money(r.solde_cdf)} / ${Format.money(r.solde_usd, 'USD')}`,
        `Mouvements : ${r.nb_mouvements || 0}`,
      ].join('\n');

      const mouvements = data.mouvements || [];
      const list = $('#caisse-list');
      if (!mouvements.length) {
        list.innerHTML = '<p class="empty">Aucun mouvement aujourd\'hui.</p>';
        return;
      }
      list.innerHTML = mouvements.map((m) => `
        <div class="move-item">
          <span class="${m.type === 'entree' ? 'badge-entree' : 'badge-sortie'}">${m.type === 'entree' ? '↑ Entrée' : '↓ Sortie'}</span>
          <strong>${Format.money(m.montant, m.devise)}</strong>
          <p class="motif">${escapeHtml(m.motif)}</p>
          <small>${Format.dateTime(m.date_mouvement)} — ${escapeHtml(m.vendeur || '')}</small>
        </div>
      `).join('');
    } catch (ex) {
      $('#caisse-resume').textContent = ex.message;
    }
  }

  async function submitMouvement() {
    const montant = parseFloat($('#mv-montant').value) || 0;
    const motif = $('#mv-motif').value.trim();
    const type = document.querySelector('input[name="mv-type"]:checked')?.value || 'entree';
    const devise = $('#mv-devise').value;

    if (montant <= 0) {
      showToast('Montant obligatoire', true);
      return;
    }
    if (!motif) {
      showToast('Motif obligatoire pour toute sortie ou entrée', true);
      return;
    }

    const btn = $('#btn-mv-save');
    btn.disabled = true;
    try {
      await Api.createMouvementCaisse({ type, montant, devise, motif });
      $('#mv-montant').value = '';
      $('#mv-motif').value = '';
      const box = $('#mv-success');
      box.classList.remove('hidden');
      box.textContent = 'Mouvement enregistré.';
      showToast('Mouvement enregistré');
      loadCaisse();
    } catch (ex) {
      showToast(ex.message, true);
    } finally {
      btn.disabled = false;
    }
  }

  // ── Stock ──────────────────────────────────────────────────────

  async function loadStock(q) {
    const list = $('#stock-list');
    list.innerHTML = '<p class="empty">Chargement…</p>';
    try {
      const data = await Api.getStock(q);
      const rows = data.stock || [];
      if (!rows.length) {
        list.innerHTML = '<p class="empty">Aucun article.</p>';
        return;
      }
      list.innerHTML = rows.map((s) => `
        <div class="stock-item${s.stock_faible ? ' stock-low' : ''}">
          <strong>${escapeHtml(s.nom)}</strong>
          <small>${escapeHtml(s.code || '')} | ${escapeHtml(s.categorie || '')}</small>
          <div>Stock: <strong>${s.quantite_stock}</strong> (seuil: ${s.seuil_alerte}) — ${Format.money(s.prix_vente)}</div>
          <small>Expiration: ${escapeHtml(s.date_expiration || '—')} ${escapeHtml(s.statut_expiration || '')}</small>
        </div>
      `).join('');
    } catch (ex) {
      list.innerHTML = `<p class="empty">${escapeHtml(ex.message)}</p>`;
    }
  }

  // ── Rapports ───────────────────────────────────────────────────

  async function loadRapport() {
    const date = $('#rapport-date').value || Format.today();
    $('#rapport-date').value = date;
    const box = $('#rapport-content');
    box.textContent = 'Chargement…';
    try {
      const data = await Api.rapportJour(date);
      const t = data.totaux || {};
      const c = data.caisse || {};
      let txt = `RAPPORT DU ${date}\n`;
      txt += `─────────────────────────\n`;
      txt += `Ventes CDF : ${Format.money(t.cdf_brut || 0)}\n`;
      txt += `Ventes USD : ${Format.money(t.usd_brut || 0, 'USD')}\n`;
      txt += `Nb ventes  : ${t.nb_ventes || 0}\n`;
      txt += `Taux USD   : ${data.taux_usd_cdf || '—'} FC\n`;
      if (c.date) {
        txt += `\nCAISSE\n`;
        txt += `Entrées : ${Format.money(c.entrees_cdf)}\n`;
        txt += `Sorties : ${Format.money(c.sorties_cdf)}\n`;
        txt += `Solde   : ${Format.money(c.solde_cdf)}\n`;
      }
      if ((data.par_devise || []).length) {
        txt += `\nPar devise:\n`;
        data.par_devise.forEach((d) => {
          txt += `  ${d.devise}: ${d.nb_ventes} ventes — ${Format.money(d.total, d.devise)}\n`;
        });
      }
      box.textContent = txt;
    } catch (ex) {
      box.textContent = ex.message;
    }
  }

  // ── Alertes ────────────────────────────────────────────────────

  async function loadAlertes() {
    const type = document.querySelector('input[name="alert-type"]:checked')?.value || 'all';
    const list = $('#alertes-list');
    list.innerHTML = '<p class="empty">Chargement…</p>';
    try {
      const data = await Api.getAlertes(type);
      const rows = data.alertes || [];
      if (!rows.length) {
        list.innerHTML = '<p class="empty">Aucune alerte.</p>';
        return;
      }
      list.innerHTML = rows.map((a) => `
        <div class="stock-item${a.stock_faible ? ' stock-low' : ''}">
          <strong>${escapeHtml(a.nom)}</strong>
          <small>${escapeHtml(a.code || '')} — ${escapeHtml(a.statut_label || a.statut || '')}</small>
          <div>Stock: ${a.quantite_stock} / seuil ${a.seuil_alerte}</div>
          <small>Expiration: ${escapeHtml(a.date_expiration || '—')}</small>
        </div>
      `).join('');
    } catch (ex) {
      list.innerHTML = `<p class="empty">${escapeHtml(ex.message)}</p>`;
    }
  }

  // ── Init ───────────────────────────────────────────────────────

  function init() {
    $('#login-form').addEventListener('submit', handleLogin);
    $('#btn-logout').addEventListener('click', handleLogout);

    $$('#app-nav button').forEach((b) => {
      b.addEventListener('click', () => loadTab(b.dataset.tab));
    });

    $$('.sub-tab').forEach((b) => {
      b.addEventListener('click', () => {
        $$('.sub-tab').forEach((x) => x.classList.toggle('active', x === b));
        $$('.sub-panel').forEach((p) => p.classList.toggle('active', p.id === b.dataset.sub));
        if (b.dataset.sub === 'vente-historique') loadHistorique();
      });
    });

    $('#search-med').addEventListener('input', (e) => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => loadMedicaments(e.target.value.trim()), 250);
    });

    $('#search-stock').addEventListener('input', (e) => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => loadStock(e.target.value.trim()), 250);
    });

    $('#btn-valider-vente').addEventListener('click', submitSale);
    $('#btn-refresh-historique').addEventListener('click', loadHistorique);
    $('#btn-mv-save').addEventListener('click', submitMouvement);
    $('#btn-rapport-load').addEventListener('click', loadRapport);
    $('#rapport-date').value = Format.today();

    $$('input[name="alert-type"]').forEach((r) => {
      r.addEventListener('change', loadAlertes);
    });

    $('#btn-print-recu').addEventListener('click', printRecu);
    $('#btn-close-recu').addEventListener('click', closeRecu);
    $('#recu-overlay').addEventListener('click', (e) => {
      if (e.target === $('#recu-overlay')) closeRecu();
    });

    if (Api.isLoggedIn()) {
      showApp();
    } else {
      showLogin();
    }
  }

  document.addEventListener('DOMContentLoaded', init);
})();
