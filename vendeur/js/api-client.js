/**
 * Client API vendeur — même backend que l'APK Android
 */
const Api = {
  base: '/api',

  session() {
    try {
      return JSON.parse(localStorage.getItem('ne_vendeur_session') || '{}');
    } catch {
      return {};
    }
  },

  saveSession(data) {
    localStorage.setItem('ne_vendeur_session', JSON.stringify(data));
  },

  clearSession() {
    localStorage.removeItem('ne_vendeur_session');
  },

  isLoggedIn() {
    const s = this.session();
    return !!(s.token || s.session_id);
  },

  authQuery(url) {
    const s = this.session();
    const u = new URL(url, window.location.origin);
    if (s.token) u.searchParams.set('token', s.token);
    if (s.session_id) u.searchParams.set('sid', s.session_id);
    return u.toString();
  },

  async request(method, path, body = null) {
    const url = path.startsWith('http') ? path : `${this.base}${path}`;
    const finalUrl = method === 'GET' ? this.authQuery(url) : this.authQuery(url);
    const s = this.session();
    const headers = {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    };
    if (s.token) {
      headers.Authorization = `Bearer ${s.token}`;
      headers['X-Auth-Token'] = s.token;
    }
    if (s.session_id) headers['X-Session-Id'] = s.session_id;

    let payload = body;
    if (body && typeof body === 'object') {
      payload = { ...body };
      if (s.token) payload.token = s.token;
      if (s.session_id) payload.sid = s.session_id;
    }

    const res = await fetch(finalUrl, {
      method,
      headers,
      credentials: 'same-origin',
      body: payload ? JSON.stringify(payload) : null,
    });

    const text = await res.text();
    if (text.includes('aes.js')) {
      throw new Error('Connexion bloquée par InfinityFree. Rechargez la page et réessayez.');
    }

    let json;
    try {
      json = JSON.parse(text);
    } catch {
      throw new Error('Réponse serveur invalide.');
    }

    if (!json.success) {
      throw new Error(json.message || 'Erreur API');
    }
    return json.data ?? json;
  },

  login(email, password) {
    return this.request('POST', '/login.php', { email, password });
  },

  logout() {
    return this.request('POST', '/logout.php', {});
  },

  getMedicaments(q = '') {
    const p = q ? `?q=${encodeURIComponent(q)}` : '';
    return this.request('GET', `/medicaments.php${p}`);
  },

  getStock(q = '') {
    const p = q ? `?q=${encodeURIComponent(q)}` : '';
    return this.request('GET', `/stock.php${p}`);
  },

  createVente(data) {
    return this.request('POST', '/ventes.php', data);
  },

  getHistorique(limit = 50) {
    return this.request('GET', `/ventes.php?liste=1&limit=${limit}`);
  },

  getRecu(id) {
    return this.request('GET', `/recu.php?id=${id}`);
  },

  rapportJour(date) {
    return this.request('GET', `/rapports.php?type=jour&date=${date}`);
  },

  getCaisse(date) {
    const p = date ? `?date=${date}` : '';
    return this.request('GET', `/caisse.php${p}`);
  },

  createMouvementCaisse(data) {
    return this.request('POST', '/caisse.php', data);
  },

  getAlertes(type = 'all') {
    return this.request('GET', `/alertes.php?type=${encodeURIComponent(type)}`);
  },
};

const Format = {
  money(amount, devise = 'CDF') {
    const n = Number(amount) || 0;
    if (devise === 'USD') return `$${n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    return `${Math.round(n).toLocaleString('fr-FR')} FC`;
  },
  dateTime(raw) {
    if (!raw) return '—';
    const d = new Date(String(raw).replace(' ', 'T'));
    return isNaN(d) ? raw : d.toLocaleString('fr-FR');
  },
  today() {
    return new Date().toISOString().slice(0, 10);
  },
};
