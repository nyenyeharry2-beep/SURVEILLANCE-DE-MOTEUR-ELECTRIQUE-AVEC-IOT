/**
 * MOTORGUARD IoT — InfinityFree (Uno + ESP32) sans Firebase
 * JOYCE 2 — lit live.php / config_moteur.php / commande.php
 */
(function () {
  "use strict";

  const API = "";
  const API_PATHS = {
    live: "/live.php",
    config: "/config_moteur.php",
    commande: "/commande.php",
  };
  const MAX_POINTS = 40;
  const MAX_HISTORY_ROWS = 30;
  const STALE_MS = 8000;
  const POLL_MS = 1000;

  const el = {
    connBadge: document.getElementById("connBadge"),
    clockLabel: document.getElementById("clockLabel"),
    alertBanner: document.getElementById("alertBanner"),
    alertBannerText: document.getElementById("alertBannerText"),
    cardStatus: document.getElementById("cardStatus"),
    motorStatus: document.getElementById("motorStatus"),
    alertLevel: document.getElementById("alertLevel"),
    vibRms: document.getElementById("vibRms"),
    aRms: document.getElementById("aRms"),
    ax: document.getElementById("ax"),
    ay: document.getElementById("ay"),
    az: document.getElementById("az"),
    rpm: document.getElementById("rpm"),
    rpmNominal: document.getElementById("rpmNominal"),
    rpmBar: document.getElementById("rpmBar"),
    diagnostic: document.getElementById("diagnostic"),
    anomalyHint: document.getElementById("anomalyHint"),
    lastUpdate: document.getElementById("lastUpdate"),
    tsEsp: document.getElementById("tsEsp"),
    relayState: document.getElementById("relayState"),
    buzzerState: document.getElementById("buzzerState"),
    btnRelayOn: document.getElementById("btnRelayOn"),
    btnRelayOff: document.getElementById("btnRelayOff"),
    chkBuzzerMute: document.getElementById("chkBuzzerMute"),
    historyBody: document.querySelector("#historyTable tbody"),
    configForm: document.getElementById("configForm"),
    configMsg: document.getElementById("configMsg"),
    lights: document.querySelectorAll(".status-lights .light"),
    statLastVib: document.getElementById("statLastVib"),
    statMinVib: document.getElementById("statMinVib"),
    statMaxVib: document.getElementById("statMaxVib"),
    statAvgVib: document.getElementById("statAvgVib"),
    statLastRpm: document.getElementById("statLastRpm"),
    statMinRpm: document.getElementById("statMinRpm"),
    statMaxRpm: document.getElementById("statMaxRpm"),
    statAvgRpm: document.getElementById("statAvgRpm"),
    cfgFields: {
      rpm_nominal: document.getElementById("cfg_rpm_nominal"),
      rpm_min: document.getElementById("cfg_rpm_min"),
      rpm_max: document.getElementById("cfg_rpm_max"),
      vib_normal_mms: document.getElementById("cfg_vib_normal"),
      vib_alerte_mms: document.getElementById("cfg_vib_alerte"),
      vib_critique_mms: document.getElementById("cfg_vib_critique"),
      a_rms_normal_ms2: document.getElementById("cfg_a_rms_normal"),
      a_rms_alerte_ms2: document.getElementById("cfg_a_rms_alerte"),
      a_rms_critique_ms2: document.getElementById("cfg_a_rms_critique"),
    },
    cfgAutoStop: document.getElementById("cfg_auto_stop_on_alarm"),
    cfgBuzzerEnabled: document.getElementById("cfg_buzzer_enabled"),
  };

  let lastLiveAt = 0;
  let configCache = null;
  const series = { labels: [], vib: [], rpm: [], ax: [], ay: [], az: [] };
  const historyStats = { vib: [], rpm: [] };

  const chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 250 },
    scales: {
      x: { ticks: { color: "#8fa3b0", maxTicksLimit: 6 }, grid: { color: "rgba(255,255,255,0.05)" } },
      y: { ticks: { color: "#8fa3b0" }, grid: { color: "rgba(255,255,255,0.06)" } },
    },
    plugins: { legend: { labels: { color: "#e8eef2" } } },
  };

  const chartVib = new Chart(document.getElementById("chartVib"), {
    type: "line",
    data: { labels: series.labels, datasets: [{ label: "Vibration RMS", data: series.vib, borderColor: "#2f9e8a", backgroundColor: "rgba(47,158,138,0.15)", tension: 0.25, fill: true, pointRadius: 0 }] },
    options: chartDefaults,
  });
  const chartRpm = new Chart(document.getElementById("chartRpm"), {
    type: "line",
    data: { labels: series.labels, datasets: [{ label: "RPM", data: series.rpm, borderColor: "#4a6d82", backgroundColor: "rgba(74,109,130,0.18)", tension: 0.25, fill: true, pointRadius: 0 }] },
    options: chartDefaults,
  });
  const chartAxes = new Chart(document.getElementById("chartAxes"), {
    type: "line",
    data: {
      labels: series.labels,
      datasets: [
        { label: "Ax", data: series.ax, borderColor: "#2f9e8a", tension: 0.2, pointRadius: 0 },
        { label: "Ay", data: series.ay, borderColor: "#c9a227", tension: 0.2, pointRadius: 0 },
        { label: "Az", data: series.az, borderColor: "#d97706", tension: 0.2, pointRadius: 0 },
      ],
    },
    options: chartDefaults,
  });

  async function apiGet(path) {
    const url = path.startsWith("/") ? path : `${API}/${path}`;
    const res = await fetch(url, { credentials: "include", cache: "no-store" });
    const data = await res.json();
    if (!res.ok || !data.ok) throw new Error(data.error || `HTTP ${res.status} sur ${url}`);
    return data;
  }

  async function apiPost(path, body) {
    const url = path.startsWith("/") ? path : `${API}/${path}`;
    const res = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      credentials: "include",
      cache: "no-store",
      body: JSON.stringify(body),
    });
    const data = await res.json();
    if (!res.ok || !data.ok) throw new Error(data.error || `HTTP ${res.status} sur ${url}`);
    return data;
  }

  function fmt(n, d) {
    if (n === null || n === undefined || Number.isNaN(Number(n))) return "—";
    return Number(n).toFixed(d);
  }

  function pushPoint(label, live) {
    series.labels.push(label);
    series.vib.push(Number(live.vibration_rms) || 0);
    series.rpm.push(Number(live.rpm) || 0);
    series.ax.push(Number(live.ax) || 0);
    series.ay.push(Number(live.ay) || 0);
    series.az.push(Number(live.az) || 0);
    if (series.labels.length > MAX_POINTS) Object.keys(series).forEach((k) => series[k].shift());
    chartVib.update("none");
    chartRpm.update("none");
    chartAxes.update("none");
  }

  function updateStats(live) {
    const v = Number(live.vibration_rms);
    const r = Number(live.rpm);
    if (!Number.isNaN(v)) historyStats.vib.push(v);
    if (!Number.isNaN(r)) historyStats.rpm.push(r);
    if (historyStats.vib.length > 200) historyStats.vib.shift();
    if (historyStats.rpm.length > 200) historyStats.rpm.shift();
    const agg = (arr) => {
      if (!arr.length) return { last: "—", min: "—", max: "—", avg: "—" };
      const last = arr[arr.length - 1];
      const min = Math.min(...arr);
      const max = Math.max(...arr);
      const avg = arr.reduce((a, b) => a + b, 0) / arr.length;
      return { last: last.toFixed(2), min: min.toFixed(2), max: max.toFixed(2), avg: avg.toFixed(2) };
    };
    const sv = agg(historyStats.vib);
    const sr = agg(historyStats.rpm);
    el.statLastVib.textContent = sv.last;
    el.statMinVib.textContent = sv.min;
    el.statMaxVib.textContent = sv.max;
    el.statAvgVib.textContent = sv.avg;
    el.statLastRpm.textContent = sr.last;
    el.statMinRpm.textContent = sr.min;
    el.statMaxRpm.textContent = sr.max;
    el.statAvgRpm.textContent = sr.avg;
  }

  function setLights(level) {
    el.lights.forEach((node) => {
      node.className = "light";
      const lv = node.getAttribute("data-level");
      if (lv === level) {
        if (level === "NORMAL") node.classList.add("on-normal");
        if (level === "SURVEILLANCE") node.classList.add("on-watch");
        if (level === "AVERTISSEMENT") node.classList.add("on-warn");
        if (level === "ALARME") node.classList.add("on-alarm");
      }
    });
  }

  function evaluateAlerts(live, config) {
    const vib = Number(live.vibration_rms) || 0;
    const rpm = Number(live.rpm) || 0;
    const crit = config ? Number(config.vib_critique_mms) : NaN;
    const rmin = config ? Number(config.rpm_min) : NaN;
    const rmax = config ? Number(config.rpm_max) : NaN;
    const messages = [];
    if (!Number.isNaN(crit) && vib > crit) messages.push("Vibration au-dessus du seuil critique.");
    if (!Number.isNaN(rmin) && rpm > 50 && rpm < rmin) messages.push("Régime inférieur au RPM minimum.");
    if (!Number.isNaN(rmax) && rpm > rmax) messages.push("Régime supérieur au RPM maximum.");
    if (live.alert_level === "ALARME" || live.status === "ALARME") {
      messages.push(live.diagnostic || "Niveau ALARME signalé par l'Uno/ESP32.");
    }
    if (messages.length) {
      el.alertBanner.classList.remove("hidden");
      el.alertBannerText.textContent = messages.join(" ");
    } else {
      el.alertBanner.classList.add("hidden");
      el.alertBannerText.textContent = "";
    }
  }

  function renderLive(live) {
    if (!live) return;
    lastLiveAt = Date.now();
    el.motorStatus.textContent = live.status || "—";
    el.alertLevel.textContent = "Niveau : " + (live.alert_level || "—");
    el.cardStatus.setAttribute("data-state", live.status || "");
    setLights(live.alert_level || live.status || "");
    el.vibRms.textContent = fmt(live.vibration_rms, 2);
    el.aRms.textContent = fmt(live.a_rms, 3);
    el.ax.textContent = fmt(live.ax, 3);
    el.ay.textContent = fmt(live.ay, 3);
    el.az.textContent = fmt(live.az, 3);
    el.rpm.textContent = fmt(live.rpm, 1);
    el.rpmNominal.textContent = fmt(live.rpm_nominal, 0);
    const nominal = Number(live.rpm_nominal) || 1;
    el.rpmBar.style.width = Math.max(0, Math.min(120, (Number(live.rpm) / nominal) * 100)) + "%";
    el.diagnostic.textContent = live.diagnostic || "—";
    el.anomalyHint.textContent = live.anomaly_hint || "";
    el.relayState.textContent = live.relay_state ? "ON" : "OFF";
    el.relayState.style.color = live.relay_state ? "#2f9e8a" : "#c23b3b";
    el.buzzerState.textContent = live.buzzer_state ? "ON" : "OFF";
    if (typeof live.buzzer_mute === "boolean") el.chkBuzzerMute.checked = live.buzzer_mute;
    el.lastUpdate.textContent = new Date().toLocaleString("fr-FR");
    el.tsEsp.textContent = live.timestamp != null ? String(live.timestamp) : "—";
    pushPoint(new Date().toLocaleTimeString("fr-FR", { hour12: false }), live);
    updateStats(live);
    evaluateAlerts(live, configCache);
    setOnline(live.online !== false, live.uno_online === true);
  }

  function setOnline(espOnline, unoOnline) {
    if (espOnline) {
      el.connBadge.textContent = unoOnline ? "Uno + ESP32 en ligne" : "ESP32 en ligne (Uno absent)";
      el.connBadge.className = "badge badge-on";
    } else {
      el.connBadge.textContent = "ESP32 hors ligne — aucune donnée récente";
      el.connBadge.className = "badge badge-off";
    }
  }

  function showApiError(msg) {
    el.connBadge.textContent = "Erreur API : " + msg;
    el.connBadge.className = "badge badge-off";
    if (el.configMsg && !el.configMsg.textContent) {
      el.configMsg.style.color = "#c23b3b";
      el.configMsg.textContent = "Vérifiez que JOYCE 1 est à la racine htdocs (live.php, mesure.php).";
    }
  }

  function fillConfigForm(config) {
    if (!config) return;
    configCache = config;
    Object.keys(el.cfgFields).forEach((key) => {
      if (config[key] != null && el.cfgFields[key]) el.cfgFields[key].value = config[key];
    });
    if (typeof config.auto_stop_on_alarm === "boolean") el.cfgAutoStop.checked = config.auto_stop_on_alarm;
    if (typeof config.buzzer_enabled === "boolean") el.cfgBuzzerEnabled.checked = config.buzzer_enabled;
  }

  function validateConfig(values) {
    const errors = [];
    if (!(values.rpm_min < values.rpm_nominal && values.rpm_nominal < values.rpm_max)) errors.push("RPM min < nominal < max.");
    if (!(values.vib_normal_mms < values.vib_alerte_mms && values.vib_alerte_mms < values.vib_critique_mms)) errors.push("Vibration normale < alerte < critique.");
    if (!(values.a_rms_normal_ms2 < values.a_rms_alerte_ms2 && values.a_rms_alerte_ms2 < values.a_rms_critique_ms2)) errors.push("A_RMS normal < alerte < critique.");
    return errors;
  }

  function renderHistory(rows) {
    const latest = (rows || []).slice(-MAX_HISTORY_ROWS).reverse();
    el.historyBody.innerHTML = latest.map((r) => {
      const t = r.timestamp != null ? r.timestamp : "—";
      return `<tr><td>${t}</td><td>${fmt(r.vibration_rms, 2)}</td><td>${fmt(r.a_rms, 3)}</td><td>${fmt(r.rpm, 1)}</td><td>${fmt(r.ax, 3)}</td><td>${fmt(r.ay, 3)}</td><td>${fmt(r.az, 3)}</td><td>${r.status || "—"}</td><td>${r.diagnostic || "—"}</td></tr>`;
    }).join("");
  }

  el.configForm.addEventListener("submit", async (ev) => {
    ev.preventDefault();
    const values = {};
    Object.keys(el.cfgFields).forEach((key) => { values[key] = Number(el.cfgFields[key].value); });
    values.auto_stop_on_alarm = el.cfgAutoStop.checked;
    values.buzzer_enabled = el.cfgBuzzerEnabled.checked;
    values.note = "Seuils a calibrer selon moteur et norme applicable";
    const errors = validateConfig(values);
    if (errors.length) {
      el.configMsg.style.color = "#c23b3b";
      el.configMsg.textContent = errors.join(" ");
      return;
    }
    try {
      await apiPost(API_PATHS.config, values);
      el.configMsg.style.color = "#2f9e8a";
      el.configMsg.textContent = "Paramètres enregistrés. L'ESP32 les lira sous ~5 s.";
      configCache = Object.assign({}, configCache || {}, values);
    } catch (err) {
      el.configMsg.style.color = "#c23b3b";
      el.configMsg.textContent = "Erreur : " + err.message;
    }
  });

  async function setRelayCommand(on) {
    try {
      await apiPost(API_PATHS.commande, { relay: on });
      el.relayState.textContent = on ? "commande ON…" : "commande OFF…";
    } catch (err) {
      alert("Impossible de commander le relais : " + err.message);
    }
  }

  el.btnRelayOn.addEventListener("click", () => setRelayCommand(true));
  el.btnRelayOff.addEventListener("click", () => setRelayCommand(false));
  el.chkBuzzerMute.addEventListener("change", async () => {
    try {
      await apiPost(API_PATHS.commande, { buzzer_mute: el.chkBuzzerMute.checked });
    } catch (err) {
      alert("Mute buzzer impossible : " + err.message);
    }
  });

  async function poll() {
    try {
      const data = await apiGet(API_PATHS.live);
      if (data.live) renderLive(data.live);
      if (data.historique) renderHistory(data.historique);
    } catch (err) {
      console.error(err);
      showApiError(err.message);
      setOnline(false, false);
    }
    try {
      const cfg = await apiGet(API_PATHS.config);
      if (cfg.config) fillConfigForm(cfg.config);
    } catch (err) {
      console.warn("config", err);
    }
  }

  el.connBadge.textContent = "InfinityFree connecté";
  el.connBadge.className = "badge badge-on";
  poll();
  setInterval(poll, POLL_MS);
  setInterval(() => {
    el.clockLabel.textContent = new Date().toLocaleTimeString("fr-FR", { hour12: false });
    if (lastLiveAt && Date.now() - lastLiveAt > STALE_MS) setOnline(false, false);
  }, 1000);
})();
