const $ = (id) => document.getElementById(id);

const chart = new Chart($("chart"), {
  type: "line",
  data: {
    labels: [],
    datasets: [
      { label: "Courant (A)", data: [], borderColor: "#3ad1ff", tension: 0.3, pointRadius: 0 },
      { label: "Température (°C)", data: [], borderColor: "#ffb020", tension: 0.3, pointRadius: 0 },
      { label: "Vibration (%)", data: [], borderColor: "#ff4d6d", tension: 0.3, pointRadius: 0 },
    ],
  },
  options: {
    responsive: true,
    animation: false,
    scales: {
      x: { ticks: { color: "#7fa0b8" }, grid: { color: "#1c3347" } },
      y: { ticks: { color: "#7fa0b8" }, grid: { color: "#1c3347" } },
    },
    plugins: { legend: { labels: { color: "#d7e7f4" } } },
  },
});

function pushChart(row) {
  const t = new Date((row.ts || Date.now() / 1000) * 1000).toLocaleTimeString("fr-FR");
  chart.data.labels.push(t);
  chart.data.datasets[0].data.push(row.current);
  chart.data.datasets[1].data.push(row.temperature);
  chart.data.datasets[2].data.push(row.vibration);
  const max = 40;
  if (chart.data.labels.length > max) {
    chart.data.labels.shift();
    chart.data.datasets.forEach((d) => d.data.shift());
  }
  chart.update();
}

function setKpi(key, value, warn, fault) {
  const el = document.querySelector(`.kpi[data-key="${key}"]`);
  if (!el) return;
  el.classList.toggle("warn", !!warn && !fault);
  el.classList.toggle("fault", !!fault);
  $(`v-${key}`).textContent = value;
}

function render(row) {
  if (!row) return;
  setKpi("voltage", row.voltage?.toFixed(1), row.voltage < 200 || row.voltage > 240, row.voltage < 180);
  setKpi("current", row.current?.toFixed(2), row.current >= 8, row.current >= 12);
  setKpi("power", row.power?.toFixed(0), false, false);
  setKpi("temperature", row.temperature?.toFixed(1), row.temperature >= 70, row.temperature >= 85);
  setKpi("vibration", row.vibration?.toFixed(1), row.vibration >= 60, row.vibration >= 85);
  setKpi("rpm", Math.round(row.rpm || 0), false, false);

  $("v-health").textContent = row.health ?? "—";
  $("v-energy").textContent = `${(row.energy_kwh || 0).toFixed(4)} kWh`;
  $("v-relay").textContent = row.relay ? "FERMÉ (marche)" : "OUVERT (coupé)";
  $("v-status").textContent = (row.status || "—").toUpperCase();
  $("source").textContent = row.source === "mqtt" ? "ESP32 / MQTT" : "Simulateur";

  const rotor = $("rotor");
  rotor.classList.toggle("on", row.status === "running" || row.status === "starting" || row.status === "alarm");
  rotor.classList.toggle("fault", row.status === "fault");

  const banner = $("banner");
  banner.className = "status-banner";
  if (row.status === "fault") {
    banner.classList.add("fault");
    banner.textContent = "DÉFAUT — protection activée. Vérifier courant, température et vibration.";
  } else if (row.status === "alarm") {
    banner.classList.add("alarm");
    banner.textContent = "ALARME — un paramètre sort des seuils nominaux.";
  } else if (row.status === "stopped") {
    banner.textContent = "Moteur à l'arrêt. Relais ouvert.";
  } else {
    banner.classList.add("ok");
    banner.textContent = "MARCHE — moteur dans les limites de fonctionnement.";
  }

  const ring = $("health-ring");
  ring.style.borderColor = row.health >= 70 ? "#1f6b50" : row.health >= 40 ? "#b8860b" : "#c0394a";
  pushChart(row);
}

async function loadAlerts() {
  const res = await fetch("/api/alerts");
  const items = await res.json();
  $("alerts").innerHTML = items
    .map(
      (a) =>
        `<li class="${a.niveau}"><b>${a.code}</b> · ${a.message}<br/><small>${new Date(a.ts * 1000).toLocaleString("fr-FR")}</small></li>`
    )
    .join("") || "<li>Aucune alerte.</li>";
}

function connectWs() {
  const proto = location.protocol === "https:" ? "wss" : "ws";
  const ws = new WebSocket(`${proto}://${location.host}/ws`);
  ws.onopen = () => {
    $("conn").textContent = "En ligne";
    $("conn").classList.add("on");
    $("conn").classList.remove("off");
  };
  ws.onclose = () => {
    $("conn").textContent = "Hors ligne";
    $("conn").classList.remove("on");
    $("conn").classList.add("off");
    setTimeout(connectWs, 1500);
  };
  ws.onmessage = (ev) => {
    const msg = JSON.parse(ev.data);
    if (msg.type === "telemetry") render(msg.data);
  };
}

document.querySelectorAll("button[data-cmd]").forEach((btn) => {
  btn.addEventListener("click", async () => {
    await fetch(`/api/command/${btn.dataset.cmd}`, { method: "POST" });
    loadAlerts();
  });
});

$("ack").addEventListener("click", async () => {
  await fetch("/api/alerts/ack", { method: "POST" });
  loadAlerts();
});

setInterval(() => {
  $("clock").textContent = new Date().toLocaleString("fr-FR");
}, 1000);

setInterval(loadAlerts, 4000);
connectWs();
loadAlerts();
fetch("/api/live").then((r) => r.json()).then(render).catch(() => {});
