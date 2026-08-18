import { initializeApp } from "https://www.gstatic.com/firebasejs/11.0.2/firebase-app.js";
import {
  getDatabase,
  ref,
  onValue,
} from "https://www.gstatic.com/firebasejs/11.0.2/firebase-database.js";
import { firebaseConfig, isFirebaseConfigured } from "./firebase-config.js";

const els = {
  temp: document.getElementById("temp-value"),
  current: document.getElementById("current-value"),
  vibration: document.getElementById("vibration-value"),
  status: document.getElementById("status-value"),
  badge: document.getElementById("connection-badge"),
  hint: document.getElementById("setup-hint"),
};

function setStatus(label) {
  els.status.textContent = label;
  els.status.classList.remove("warn", "bad");
  if (label === "Attention") els.status.classList.add("warn");
  if (label === "Alarme") els.status.classList.add("bad");
}

function renderMotor(data) {
  els.temp.textContent = Number(data.temperature ?? 0).toFixed(1);
  els.current.textContent = Number(data.current ?? 0).toFixed(1);
  els.vibration.textContent = Number(data.vibration ?? 0).toFixed(2);
  setStatus(data.status ?? "Inconnu");
}

function startDemoMode() {
  els.badge.textContent = "Mode démo";
  els.badge.classList.remove("live");
  if (els.hint) els.hint.classList.remove("hidden");

  const tick = () => {
    const temperature = 55 + Math.random() * 18;
    const current = 8 + Math.random() * 6;
    const vibration = 1.2 + Math.random() * 2.5;
    let status = "OK";
    if (temperature > 70 || vibration > 3.2) status = "Attention";
    if (temperature > 78 || vibration > 3.6) status = "Alarme";
    renderMotor({ temperature, current, vibration, status });
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
  const motorRef = ref(db, "motors/motor1");

  onValue(
    motorRef,
    (snapshot) => {
      const data = snapshot.val();
      if (!data) {
        renderMotor({
          temperature: 0,
          current: 0,
          vibration: 0,
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
