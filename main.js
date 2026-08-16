import { login, register, logout, me, fetchEtat, sendCommande, toUser } from "./api.js";

import {
  SEUILS,
  calculateResultante,
  computeVibrationFromSamples,
  resolveRpm,
  rmsLevel,
  rmsLevelLabel,
  deriveMotorState,
  buildDiagnostics,
  isInvalidSample,
  pushSampleWindow,
  formatNumber,
  formatDate,
  formatTime,
  ETAT_LABELS,
} from "./calculs.js";

const appEl = document.getElementById("app");

const CHART_MAX = 40;
const TABLE_MAX = 20;
const DEFAULT_EMAIL = "nyenyeharry2@gmail.com";
const DEFAULT_PASSWORD = "(\u0027-\u00e91014";
const DEFAULT_NAME = "Harry Nyenye";

const state = {
  user: null,
  mode: "login",
  authEmail: DEFAULT_EMAIL,
  authPassword: DEFAULT_PASSWORD,
  view: "overview",
  error: "",
  toast: "",
  mysqlStatus: "checking",
  mysqlError: "",
  pollTimer: null,
  demoMode: false,
  dashboardMounted: false,
  sampleWindow: [],
  history: [],
  commande: { etatCommande: false, updatedAt: null },
  lastDataAt: null,
  dataSource: "none",
  motor: {
    x: 0,
    y: 0,
    z: 0,
    resultante: 0,
    rmsMmS: 0,
    rpm: 0,
    etat: "arrêté",
    timestamp: null,
  },
};

let demoTimer = null;
let charts = { vibration: null, rpm: null };
let toastTimer = null;

const theme = localStorage.getItem("lumen-theme") || "light";
document.documentElement.dataset.theme = theme;

window.addEventListener("error", (event) => {
  showFatal(event.error?.message || event.message || "Erreur JavaScript");
});

window.addEventListener("unhandledrejection", (event) => {
  const message = event.reason?.message || String(event.reason || "Erreur");
  state.mysqlError = message;
  if (state.dashboardMounted) updateDashboard();
  else showFatal(message);
});

function showFatal(message) {
  if (!appEl) return;
  if (appEl.querySelector(".fatal-error")) return;
  const box = document.createElement("div");
  box.className = "fatal-error";
  box.textContent = "Erreur : " + message;
  appEl.prepend(box);
}

function escapeHtml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;");
}

function initials(name = "") {
  return (
    name
      .split(" ")
      .filter(Boolean)
      .slice(0, 2)
      .map((p) => p[0].toUpperCase())
      .join("") || "LM"
  );
}

function toast(message) {
  state.toast = message;
  const el = document.getElementById("toast");
  if (el) {
    el.hidden = !message;
    el.textContent = message || "";
  } else if (!state.dashboardMounted) {
    render();
  }
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    state.toast = "";
    const t = document.getElementById("toast");
    if (t) {
      t.hidden = true;
      t.textContent = "";
    }
  }, 2500);
}

function frenchAuthError(error) {
  return error?.message || "Une erreur est survenue.";
}

function applyMeasurement(raw = {}, source = "mysql") {
  const x = Number(raw.x ?? raw.vibrationX ?? 0);
  const y = Number(raw.y ?? raw.vibrationY ?? 0);
  const z = Number(raw.z ?? raw.vibrationZ ?? 0);
  const rpm = resolveRpm(raw);
  const timestamp = raw.timestamp ?? new Date();
  const invalid = isInvalidSample(x, y, z, rpm);

  state.sampleWindow = pushSampleWindow(state.sampleWindow, { x, y, z });
  const vib = computeVibrationFromSamples(state.sampleWindow);

  const mysqlRms = Number(raw.rmsMmS ?? raw.rmsVitesse);
  const useMysqlRms =
    raw.uniteRms === "mm/s" && Number.isFinite(mysqlRms);

  const rmsMmS = useMysqlRms ? mysqlRms : vib.rmsMmS;
  const commandedOn = Boolean(state.commande.etatCommande);
  const noData = source === "none";
  const stale =
    !state.demoMode &&
    state.lastDataAt &&
    Date.now() - state.lastDataAt > SEUILS.staleMs;

  const extras = {
    sensorFault: Boolean(raw.defautCapteur),
    invalidData: invalid,
    noData,
    stale,
    commandedOn,
  };

  const etat = deriveMotorState(
    { rpm, rmsMmS },
    extras
  );

  state.motor = {
    x: vib.x,
    y: vib.y,
    z: vib.z,
    resultante: calculateResultante(x, y, z),
    rmsMmS,
    rpm,
    etat,
    timestamp,
  };
  state.dataSource = source;
  state.lastDataAt = Date.now();

  const point = {
    ...state.motor,
    timeLabel: formatTime(timestamp) || new Date().toLocaleTimeString("fr-FR"),
  };
  state.history = [...state.history, point].slice(-CHART_MAX);
}

function currentExtras() {
  const stale =
    !state.demoMode &&
    state.dataSource !== "demo" &&
    (!state.lastDataAt || Date.now() - state.lastDataAt > SEUILS.staleMs);
  return {
    noData: state.dataSource === "none" || stale,
    stale,
    sensorFault: false,
    invalidData: isInvalidSample(
      state.motor.x,
      state.motor.y,
      state.motor.z,
      state.motor.rpm
    ),
    commandedOn: Boolean(state.commande.etatCommande),
  };
}

/* ——— Auth / setup ——— */

function renderAuth() {
  const isLogin = state.mode === "login";
  appEl.innerHTML = `
    <section class="auth-shell">
      <aside class="brand-panel">
        <div class="mark"><span class="mark-dot"></span> Lumen</div>
        <div>
          <h1>Surveillance du moteur en temps réel.</h1>
          <p>Vibrations ADXL345, vitesse IR, état du moteur et diagnostic.</p>
        </div>
        <p>ADXL345 · IR · MySQL</p>
      </aside>
      <div class="form-panel">
        <form class="card" id="auth-form" novalidate>
          <h2>${isLogin ? "Connexion" : "Créer un compte"}</h2>
          <p class="lede">${
            isLogin
              ? "Utilisez le compte ci-dessous pour ouvrir le tableau de bord."
              : "Un e-mail et un mot de passe suffisent. La connexion est automatique après inscription."
          }</p>
          ${
            isLogin
              ? `<div class="cred-box">
                  <p>E-mail : <code>${escapeHtml(DEFAULT_EMAIL)}</code></p>
                  <p>Mot de passe : <code>${escapeHtml(DEFAULT_PASSWORD)}</code></p>
                </div>`
              : ""
          }
          ${state.error ? `<div class="alert">${escapeHtml(state.error)}</div>` : ""}
          ${
            isLogin
              ? ""
              : `<div class="field">
                  <label for="name">Nom</label>
                  <input id="name" name="name" autocomplete="name" placeholder="Harry Nyenye" value="Harry Nyenye" />
                </div>`
          }
          <div class="field">
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" autocomplete="username" placeholder="vous@example.com" value="${escapeHtml(state.authEmail || DEFAULT_EMAIL)}" />
          </div>
          <div class="field">
            <label for="password">Mot de passe</label>
            <input id="password" name="password" type="${isLogin ? "text" : "password"}" autocomplete="${isLogin ? "current-password" : "new-password"}" minlength="6" placeholder="('-é1014" />
          </div>
          <button class="btn btn-primary" type="submit">${
            isLogin ? "Entrer" : "S'inscrire"
          }</button>
          <div class="switch">
            ${isLogin ? "Pas encore de compte ?" : "Déjà inscrit ?"}
            <button class="btn btn-ghost" type="button" id="toggle-auth">
              ${isLogin ? "Créer un compte" : "Se connecter"}
            </button>
          </div>
        </form>
      </div>
    </section>`;

  document.getElementById("toggle-auth")?.addEventListener("click", () => {
    state.mode = isLogin ? "register" : "login";
    state.error = "";
    render();
  });

  const passwordInput = document.getElementById("password");
  if (passwordInput) passwordInput.value = state.authPassword || DEFAULT_PASSWORD;

  document.getElementById("auth-form")?.addEventListener("submit", async (event) => {
    event.preventDefault();
    event.stopPropagation();
    const email = (document.getElementById("email")?.value || DEFAULT_EMAIL).trim();
    const password = document.getElementById("password")?.value || DEFAULT_PASSWORD;
    const name = (document.getElementById("name")?.value || DEFAULT_NAME).trim();
    state.authEmail = email;
    state.authPassword = password;
    state.error = "";
    if (!email || !password) {
      state.error = "E-mail et mot de passe requis.";
      render();
      return;
    }
    if (!isLogin && name === "") {
      state.error = "Indiquez un nom pour créer le compte.";
      render();
      return;
    }
    try {
      const result = isLogin
        ? await login(email, password)
        : await register(name, email, password);
      const user = toUser(result);
      if (!user) throw new Error("Connexion impossible.");
      state.authPassword = "";
      onSignedIn(user);
    } catch (error) {
      state.error = frenchAuthError(error);
      render();
    }
  });
}

/* ——— Dashboard ——— */

function mountDashboard() {
  if (state.dashboardMounted && document.getElementById("dashboard-root")) {
    updateDashboard();
    return;
  }

  const userName = state.user.displayName || state.user.email;

  appEl.innerHTML = `
    <div class="shell" id="dashboard-root">
      <aside class="sidebar">
        <div class="mark"><span class="mark-dot"></span> Lumen</div>
        <p class="sidebar-sub">Surveillance du moteur</p>
        <nav>
          <button class="nav-btn" data-nav="overview">Vue d'ensemble</button>
          <button class="nav-btn" data-nav="surveillance">Surveillance</button>
          <button class="nav-btn" data-nav="historique">Historique</button>
          <button class="nav-btn" data-nav="diagnostic">Diagnostic</button>
          <button class="nav-btn" data-nav="commande">Commande moteur</button>
          <button class="nav-btn" id="logout-btn" type="button">Déconnexion</button>
        </nav>
        <div class="sidebar-foot">
          <button class="nav-btn" id="demo-btn" type="button">Mode démonstration : OFF</button>
          <button class="nav-btn" id="theme-btn" type="button">Thème</button>
        </div>
      </aside>

      <main class="main">
        <header class="topbar">
          <div>
            <p class="eyebrow">Lumen</p>
            <h1>Surveillance du moteur</h1>
          </div>
          <div class="topbar-status">
            <span class="status-pill" id="fb-status">MySQL…</span>
            <span class="status-pill" id="motor-pill">ARRÊTÉ</span>
            <div class="user-chip">
              <span class="avatar">${escapeHtml(initials(userName))}</span>
              <div>
                <strong>${escapeHtml(userName)}</strong>
                <div class="muted">${escapeHtml(state.user.email)}</div>
              </div>
            </div>
          </div>
        </header>

        <div class="demo-banner" id="demo-banner" hidden>
          Données de démonstration — ce ne sont pas des mesures réelles.
          ALLUMER / ÉTEINDRE change la vitesse simulée.
        </div>
        <div class="alert" id="error-banner" hidden></div>

        <section class="kpis kpis-4" data-section="overview surveillance commande">
          <article class="kpi" id="card-etat">
            <span>État du moteur</span>
            <strong id="val-etat">ARRÊTÉ</strong>
            <small id="val-etat-sub">Commande logicielle distincte</small>
          </article>
          <article class="kpi">
            <span>Vitesse de rotation</span>
            <strong id="val-rpm">0</strong>
            <small>tr/min (RPM)</small>
          </article>
          <article class="kpi" id="card-rms">
            <span>RMS Vibration</span>
            <strong id="val-rms">0.00</strong>
            <small>mm/s · <span id="val-rms-level">NORMAL</span></small>
          </article>
          <article class="kpi">
            <span>Diagnostic</span>
            <strong id="val-diag" class="kpi-diag">—</strong>
            <small id="val-diag-count">Aucun problème</small>
          </article>
        </section>

        <section class="kpis kpis-4" data-section="overview surveillance">
          <article class="kpi">
            <span>X</span>
            <strong id="val-x">0.00</strong>
            <small>g · ADXL345</small>
          </article>
          <article class="kpi">
            <span>Y</span>
            <strong id="val-y">0.00</strong>
            <small>g · ADXL345</small>
          </article>
          <article class="kpi">
            <span>Z</span>
            <strong id="val-z">0.00</strong>
            <small>g · ADXL345</small>
          </article>
          <article class="kpi">
            <span>Norme / résultante</span>
            <strong id="val-res">0.00</strong>
            <small>g</small>
          </article>
        </section>

        <section class="grid-2 charts-row" data-section="overview surveillance">
          <article class="panel">
            <h3>Courbe de vibration X / Y / Z</h3>
            <p class="muted chart-hint">Accélération ADXL345 (g) · temps</p>
            <div class="chart-wrap-canvas"><canvas id="chart-vibration"></canvas></div>
          </article>
          <article class="panel">
            <h3>Courbe de vitesse</h3>
            <p class="muted chart-hint">Axe horizontal : temps · Axe vertical : RPM</p>
            <div class="chart-wrap-canvas"><canvas id="chart-rpm"></canvas></div>
          </article>
        </section>

        <section class="panel" data-section="overview diagnostic" id="section-diagnostic">
          <div class="panel-heading">
            <div>
              <p class="eyebrow">Diagnostic / Problèmes détectés</p>
              <h3>État de santé</h3>
            </div>
            <span class="badge" id="diag-badge">Normal</span>
          </div>
          <div class="problem-list" id="problem-list"></div>
        </section>

        <section class="panel" data-section="overview historique">
          <div class="panel-heading">
            <div>
              <p class="eyebrow">Historique</p>
              <h3>Mesures récentes</h3>
            </div>
            <span class="muted" id="last-update">—</span>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Heure</th>
                  <th>X (g)</th>
                  <th>Y (g)</th>
                  <th>Z (g)</th>
                  <th>RMS (mm/s)</th>
                  <th>RPM</th>
                  <th>État</th>
                </tr>
              </thead>
              <tbody id="history-body">
                <tr><td colspan="7" class="muted">Aucune mesure pour l’instant.</td></tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="panel motor-control" data-section="overview commande">
          <div>
            <p class="eyebrow">Commande moteur</p>
            <h3 id="commande-label">ÉTEINT</h3>
            <p class="muted">
              Commande logicielle enregistrée dans MySQL
              (<code>commande</code>). L’ESP32 la lit sur le site
              et actionne le relais.
            </p>
          </div>
          <div class="motor-buttons">
            <button class="btn btn-primary" id="motor-on" type="button">ALLUMER LE MOTEUR</button>
            <button class="btn btn-danger" id="motor-off" type="button">ÉTEINDRE LE MOTEUR</button>
          </div>
        </section>
      </main>
    </div>
    <div class="toast" id="toast" hidden></div>
  `;

  state.dashboardMounted = true;
  bindDashboard();
  initCharts();
  updateDashboard();
}

function bindDashboard() {
  document.querySelectorAll("[data-nav]").forEach((btn) => {
    btn.addEventListener("click", () => {
      state.view = btn.dataset.nav;
      applyView();
    });
  });

  document.getElementById("demo-btn")?.addEventListener("click", () => {
    setDemoMode(!state.demoMode);
  });

  document.getElementById("theme-btn")?.addEventListener("click", () => {
    const next = document.documentElement.dataset.theme === "dark" ? "light" : "dark";
    document.documentElement.dataset.theme = next;
    localStorage.setItem("lumen-theme", next);
    updateDashboard();
  });

  document.getElementById("logout-btn")?.addEventListener("click", async () => {
    await logout().catch(() => {});
    onSignedOut();
  });

  document.getElementById("motor-on")?.addEventListener("click", () => {
    sendMotorCommand(true);
  });

  document.getElementById("motor-off")?.addEventListener("click", () => {
    sendMotorCommand(false);
  });
}

function applyView() {
  const view = state.view;
  document.querySelectorAll("[data-section]").forEach((el) => {
    const tags = el.dataset.section.split(/\s+/);
    el.hidden = view !== "overview" && !tags.includes(view);
  });
  document.querySelectorAll("[data-nav]").forEach((btn) => {
    btn.classList.toggle("active", btn.dataset.nav === view);
  });
  requestAnimationFrame(() => {
    charts.vibration?.resize();
    charts.rpm?.resize();
  });
}

function chartColors() {
  const dark = document.documentElement.dataset.theme === "dark";
  return {
    ink: dark ? "#f0ebe3" : "#1c1914",
    muted: dark ? "#a39c90" : "#7a7368",
    grid: dark ? "rgba(240,235,227,0.12)" : "rgba(28,25,20,0.08)",
    x: "#3d6b7a",
    y: "#b8893a",
    z: "#2d6a4f",
    rpm: "#b85a42",
  };
}

function initCharts() {
  if (typeof Chart === "undefined") return;

  const vib = document.getElementById("chart-vibration");
  const rpm = document.getElementById("chart-rpm");
  if (!vib || !rpm) return;

  charts.vibration?.destroy();
  charts.rpm?.destroy();

  const c = chartColors();
  const common = {
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 250 },
    plugins: { legend: { labels: { color: c.ink, boxWidth: 12 } } },
    scales: {
      x: {
        ticks: { color: c.muted, maxTicksLimit: 6 },
        grid: { color: c.grid },
      },
      y: {
        ticks: { color: c.muted },
        grid: { color: c.grid },
      },
    },
  };

  charts.vibration = new Chart(vib, {
    type: "line",
    data: {
      labels: [],
      datasets: [
        { label: "X (g)", data: [], borderColor: c.x, backgroundColor: "transparent", tension: 0.25, pointRadius: 0, borderWidth: 2 },
        { label: "Y (g)", data: [], borderColor: c.y, backgroundColor: "transparent", tension: 0.25, pointRadius: 0, borderWidth: 2 },
        { label: "Z (g)", data: [], borderColor: c.z, backgroundColor: "transparent", tension: 0.25, pointRadius: 0, borderWidth: 2 },
      ],
    },
    options: common,
  });

  charts.rpm = new Chart(rpm, {
    type: "line",
    data: {
      labels: [],
      datasets: [
        { label: "RPM", data: [], borderColor: c.rpm, backgroundColor: "rgba(184,90,66,0.12)", fill: true, tension: 0.25, pointRadius: 0, borderWidth: 2 },
      ],
    },
    options: {
      ...common,
      scales: {
        ...common.scales,
        y: { ...common.scales.y, title: { display: true, text: "RPM", color: c.muted }, beginAtZero: true },
        x: { ...common.scales.x, title: { display: true, text: "Temps", color: c.muted } },
      },
    },
  });
}

function updateCharts() {
  if (!charts.vibration || !charts.rpm) return;
  const labels = state.history.map((h) => h.timeLabel);
  charts.vibration.data.labels = labels;
  charts.vibration.data.datasets[0].data = state.history.map((h) => h.x);
  charts.vibration.data.datasets[1].data = state.history.map((h) => h.y);
  charts.vibration.data.datasets[2].data = state.history.map((h) => h.z);
  charts.vibration.update("none");

  charts.rpm.data.labels = labels;
  charts.rpm.data.datasets[0].data = state.history.map((h) => h.rpm);
  charts.rpm.update("none");
}

function setText(id, value) {
  const el = document.getElementById(id);
  if (el) el.textContent = value;
}

function updateDashboard() {
  if (!document.getElementById("dashboard-root")) return;

  const motor = state.motor;
  const extras = currentExtras();
  const problems = buildDiagnostics(motor, extras);
  const alerts = problems.filter((p) => p.severity === "warning" || p.severity === "fault");
  const level = rmsLevel(motor.rmsMmS);
  const etatLabel = ETAT_LABELS[motor.etat] || motor.etat.toUpperCase();
  const mysqlOk = state.mysqlStatus === "connected";

  applyView();

  const fb = document.getElementById("fb-status");
  if (fb) {
    fb.textContent =
      state.mysqlStatus === "connected"
        ? "MySQL connecté"
        : state.mysqlStatus === "error"
          ? "Erreur MySQL"
          : state.mysqlStatus === "disconnected"
            ? "Hors ligne"
            : "MySQL…";
    fb.className = "status-pill " + (mysqlOk ? "ok" : "bad");
  }

  const pill = document.getElementById("motor-pill");
  if (pill) {
    pill.textContent = etatLabel;
    pill.className = "status-pill etat-" + motor.etat.replace(/\s+/g, "-");
  }

  const demoBanner = document.getElementById("demo-banner");
  if (demoBanner) demoBanner.hidden = !state.demoMode;

  const errBanner = document.getElementById("error-banner");
  if (errBanner) {
    const msg = state.mysqlError;
    errBanner.hidden = !msg;
    errBanner.textContent = msg || "";
  }

  setText("val-etat", etatLabel);
  setText("val-rpm", formatNumber(motor.rpm, 0));
  setText("val-rms", formatNumber(motor.rmsMmS, 2));
  setText("val-rms-level", rmsLevelLabel(level));
  setText("val-x", formatNumber(motor.x));
  setText("val-y", formatNumber(motor.y));
  setText("val-z", formatNumber(motor.z));
  setText("val-res", formatNumber(motor.resultante));
  setText("val-diag", alerts.length ? alerts[0].label : "RAS");
  setText("val-diag-count", alerts.length ? `${alerts.length} problème(s)` : "Aucun problème");
  setText("last-update", motor.timestamp ? "Dernière mesure : " + formatDate(motor.timestamp) : "Aucune mesure");

  const cardRms = document.getElementById("card-rms");
  if (cardRms) cardRms.dataset.level = level;

  const cardEtat = document.getElementById("card-etat");
  if (cardEtat) cardEtat.dataset.etat = motor.etat;

  const badge = document.getElementById("diag-badge");
  if (badge) {
    badge.textContent = alerts.length ? `${alerts.length} alerte(s)` : "Normal";
    badge.className = "badge " + (alerts.length ? "haute" : "normal");
  }

  const list = document.getElementById("problem-list");
  if (list) {
    list.innerHTML = problems
      .map(
        (p) =>
          `<div class="problem ${escapeHtml(p.severity)}">${escapeHtml(p.label)}</div>`
      )
      .join("");
  }

  const body = document.getElementById("history-body");
  if (body) {
    const rows = state.history.slice(-TABLE_MAX).reverse();
    body.innerHTML = rows.length
      ? rows
          .map(
            (h) => `<tr>
              <td>${escapeHtml(h.timeLabel)}</td>
              <td>${formatNumber(h.x)}</td>
              <td>${formatNumber(h.y)}</td>
              <td>${formatNumber(h.z)}</td>
              <td>${formatNumber(h.rmsMmS)}</td>
              <td>${formatNumber(h.rpm, 0)}</td>
              <td>${escapeHtml(ETAT_LABELS[h.etat] || h.etat)}</td>
            </tr>`
          )
          .join("")
      : `<tr><td colspan="7" class="muted">Aucune mesure pour l’instant.</td></tr>`;
  }

  const commanded = Boolean(state.commande.etatCommande);
  setText("commande-label", commanded ? "Commande : ALLUMÉ" : "Commande : ÉTEINT");
  const onBtn = document.getElementById("motor-on");
  const offBtn = document.getElementById("motor-off");
  if (onBtn) onBtn.disabled = commanded;
  if (offBtn) offBtn.disabled = !commanded;

  const demoBtn = document.getElementById("demo-btn");
  if (demoBtn) {
    demoBtn.textContent = "Mode démonstration : " + (state.demoMode ? "ON" : "OFF");
    demoBtn.classList.toggle("active", state.demoMode);
  }

  const themeBtn = document.getElementById("theme-btn");
  if (themeBtn) {
    themeBtn.textContent =
      "Thème " + (document.documentElement.dataset.theme === "dark" ? "clair" : "sombre");
  }

  const toastEl = document.getElementById("toast");
  if (toastEl) {
    toastEl.hidden = !state.toast;
    toastEl.textContent = state.toast || "";
  }

  updateCharts();
}

/* ——— Commande MySQL ——— */

async function sendMotorCommand(on) {
  state.commande = {
    etatCommande: on,
    updatedAt: new Date(),
  };
  if (state.dashboardMounted) updateDashboard();

  try {
    await sendCommande(on);
    toast(on ? "Commande ALLUMER enregistrée" : "Commande ÉTEINDRE enregistrée");
  } catch (error) {
    state.mysqlError = error.message;
    toast(
      state.demoMode
        ? "Commande appliquée en démonstration (MySQL inaccessible)"
        : state.mysqlError
    );
    updateDashboard();
  }
}

/* ——— Démonstration ——— */

function setDemoMode(on) {
  state.demoMode = on;
  if (demoTimer) {
    clearInterval(demoTimer);
    demoTimer = null;
  }
  if (on) {
    state.sampleWindow = [];
    state.history = [];
    state.mysqlError = "";
    tickDemo();
    demoTimer = setInterval(tickDemo, 700);
    toast("Mode démonstration activé");
  } else {
    state.sampleWindow = [];
    state.history = [];
    state.dataSource = "none";
    toast("Mode démonstration désactivé");
  }
  updateDashboard();
}

function tickDemo() {
  const t = Date.now() / 1000;
  const running = Boolean(state.commande.etatCommande);
  const spike = running && Math.sin(t / 18) > 0.92;
  const noise = () => (Math.random() - 0.5) * (running ? (spike ? 0.35 : 0.06) : 0.01);
  const baseRpm = running ? 1480 + Math.sin(t / 3) * 40 : 0;

  applyMeasurement(
    {
      x: noise(),
      y: noise(),
      z: 1 + noise(),
      rpm: Math.max(0, baseRpm + (spike ? 220 : 0)),
      timestamp: new Date(),
      defautCapteur: false,
    },
    "demo"
  );
  updateDashboard();
}

/* ——— MySQL (polling) ——— */

function applyCommandeData(data = {}) {
  if (typeof data.etatCommande === "boolean") {
    state.commande.etatCommande = data.etatCommande;
  } else if (data.etatCommande === 1 || data.etatCommande === "1") {
    state.commande.etatCommande = true;
  } else if (data.commande === "ON" || data.etat === "marche") {
    state.commande.etatCommande = true;
  } else if (data.commande === "OFF" || data.etat === "arrêté") {
    state.commande.etatCommande = false;
  } else if (data.etatCommande === 0 || data.etatCommande === "0") {
    state.commande.etatCommande = false;
  }
  state.commande.updatedAt = data.updatedAt ?? data.timestamp ?? state.commande.updatedAt;
}

function rowsFromMesures(rows) {
  let window = [];
  return (rows || []).map((data) => {
    const x = Number(data.x ?? data.vibrationX ?? 0);
    const y = Number(data.y ?? data.vibrationY ?? 0);
    const z = Number(data.z ?? data.vibrationZ ?? 0);
    const rpm = resolveRpm(data);
    const ts = data.timestamp ?? null;
    window = pushSampleWindow(window, { x, y, z });
    const vib = computeVibrationFromSamples(window);
    const rmsMmS =
      data.uniteRms === "mm/s" && Number.isFinite(Number(data.rmsMmS ?? data.rms))
        ? Number(data.rmsMmS ?? data.rms)
        : vib.rmsMmS;
    const etat = deriveMotorState(
      { rpm, rmsMmS },
      { commandedOn: Boolean(state.commande.etatCommande) }
    );
    return {
      x,
      y,
      z,
      resultante: calculateResultante(x, y, z),
      rmsMmS,
      rpm,
      etat,
      timestamp: ts,
      timeLabel: formatTime(ts) || "",
    };
  });
}

async function restoreSession() {
  try {
    const data = await me();
    const user = toUser(data);
    if (user) return user;
  } catch {
    /* not signed in yet */
  }
  try {
    const result = await login(DEFAULT_EMAIL, DEFAULT_PASSWORD);
    const user = toUser(result);
    if (user) return user;
  } catch {
    /* account may be missing */
  }
  try {
    const result = await register(DEFAULT_NAME, DEFAULT_EMAIL, DEFAULT_PASSWORD);
    return toUser(result);
  } catch {
    return null;
  }
}

async function pollEtat() {
  if (!state.user || state.demoMode) return;
  try {
    const data = await fetchEtat();
    state.mysqlStatus = "connected";
    state.mysqlError = "";
    applyCommandeData(data.commande || {});
    if (data.moteur) applyMeasurement(data.moteur, "mysql");
    if (Array.isArray(data.mesures) && data.mesures.length) {
      state.history = rowsFromMesures(data.mesures).slice(-CHART_MAX);
    }
    if (state.dashboardMounted) updateDashboard();
  } catch (error) {
    if (error.status === 401) {
      const user = await restoreSession();
      if (user) {
        state.user = user;
        return;
      }
      onSignedOut();
      return;
    }
    state.mysqlStatus = "error";
    state.mysqlError = error.message;
    if (state.dashboardMounted) updateDashboard();
  }
}

function startPolling() {
  stopPolling();
  pollEtat();
  state.pollTimer = setInterval(pollEtat, 1000);
}

function stopPolling() {
  if (state.pollTimer) {
    clearInterval(state.pollTimer);
    state.pollTimer = null;
  }
}

function watchNetwork() {
  const apply = () => {
    if (!navigator.onLine) {
      state.mysqlStatus = "disconnected";
      state.mysqlError = "Perte de connexion réseau.";
    } else if (state.mysqlStatus === "disconnected") {
      state.mysqlStatus = "checking";
      state.mysqlError = "";
    }
    if (state.dashboardMounted) updateDashboard();
  };
  window.addEventListener("online", apply);
  window.addEventListener("offline", apply);
  apply();
}

function stopListeners() {
  stopPolling();
  if (demoTimer) {
    clearInterval(demoTimer);
    demoTimer = null;
  }
  charts.vibration?.destroy();
  charts.rpm?.destroy();
  charts = { vibration: null, rpm: null };
  state.dashboardMounted = false;
  state.demoMode = false;
  state.history = [];
  state.sampleWindow = [];
}

function onSignedIn(user) {
  state.user = user;
  state.error = "";
  state.mysqlStatus = "checking";
  startPolling();
  render();
}

function onSignedOut() {
  state.user = null;
  stopListeners();
  render();
}

function render() {
  if (!state.user) {
    state.dashboardMounted = false;
    renderAuth();
    return;
  }
  mountDashboard();
}

async function boot() {
  watchNetwork();
  const user = await restoreSession();
  if (user) onSignedIn(user);
  else render();
}

boot();
