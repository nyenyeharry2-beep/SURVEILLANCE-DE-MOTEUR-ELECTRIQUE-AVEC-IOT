import { initializeApp } from "https://www.gstatic.com/firebasejs/11.0.2/firebase-app.js";
import {
  getDatabase,
  ref,
  onValue,
} from "https://www.gstatic.com/firebasejs/11.0.2/firebase-database.js";
import { firebaseConfig, isFirebaseConfigured } from "./firebase-config.js";

const els = {
  rpm: document.getElementById("rpm-value"),
  aRms: document.getElementById("a-rms-value"),
  vibration: document.getElementById("vibration-value"),
  status: document.getElementById("status-value"),
  badge: document.getElementById("connection-badge"),
  hint: document.getElementById("setup-hint"),
};

function setStatus(label) {
  els.status.textContent = label;
  els.status.classList.remove("warn", "bad");
  const upper = String(label || "").toUpperCase();
  if (upper.includes("SURVEILLANCE") || upper.includes("AVERT")) {
    els.status.classList.add("warn");
  }
  if (upper.includes("ALARME") || upper.includes("ALARM")) {
    els.status.classList.add("bad");
  }
}

function renderMotor(data) {
  els.rpm.textContent = Number(data.rpm ?? 0).toFixed(0);
  els.aRms.textContent = Number(data.a_rms ?? 0).toFixed(2);
  els.vibration.textContent = Number(data.vibration_rms ?? data.vibration ?? 0).toFixed(2);
  setStatus(data.status ?? data.alert_level ?? "Inconnu");
}

function startDemoMode() {
  els.badge.textContent = "Mode démo";
  els.badge.classList.remove("live");
  if (els.hint) els.hint.classList.remove("hidden");

  const tick = () => {
    const rpm = 1200 + Math.random() * 400;
    const a_rms = 1.2 + Math.random() * 1.8;
    const vibration_rms = 1.5 + Math.random() * 2.5;
    let status = "NORMAL";
    if (vibration_rms > 4.5) status = "AVERTISSEMENT";
    if (vibration_rms > 7) status = "ALARME";
    renderMotor({ rpm, a_rms, vibration_rms, status });
  };

  tick();
  setInterval(tick, 2000);
}

function startFirebaseMode() {
  els.badge.textContent = "Firebase connecté";
  els.badge.classList.add("live");
  if (els.hint) els.hint.classList.add("hidden");

  const app = initializeApp(firebaseConfig);
  const db = getDatabase(app);
  const liveRef = ref(db, "moteur/live");

  onValue(
    liveRef,
    (snapshot) => {
      const data = snapshot.val();
      if (!data) {
        renderMotor({
          rpm: 0,
          a_rms: 0,
          vibration_rms: 0,
          status: "En attente",
        });
        return;
      }
      renderMotor(data);
    },
    (error) => {
      console.error("Erreur Firebase:", error);
      els.badge.textContent = "Erreur Firebase";
      startDemoMode();
    }
  );
}

if (isFirebaseConfigured()) {
  startFirebaseMode();
} else {
  startDemoMode();
}
