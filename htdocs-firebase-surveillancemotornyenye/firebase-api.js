/** Pont Firebase — remplace les appels PHP/MySQL */

let db = null;
let cmdCounter = 0;

export function initFirebase() {
  if (!window.firebase || !window.FIREBASE_CONFIG) {
    throw new Error("Firebase non chargé — vérifiez firebase-config.js");
  }
  if (!db) {
    firebase.initializeApp(window.FIREBASE_CONFIG);
    db = firebase.database();
  }
  return db;
}

export function subscribeLive(callback) {
  initFirebase();
  db.ref("motor/live").on("value", (snap) => callback(snap.val()));
}

export function subscribeCommand(callback) {
  initFirebase();
  const apply = (row = {}) => {
    callback({
      etatCommande: !!(row.wantOn ?? row.motorOn),
      emergency: !!row.emergency,
      updatedAt: row.at || row.updatedAt || null,
    });
  };
  db.ref("motor/command").on("value", (snap) => apply(snap.val()));
  db.ref("motor/wantOn").on("value", (snap) => {
    callback({ etatCommande: !!snap.val(), updatedAt: new Date().toISOString() });
  });
}

export function subscribeConnected(callback) {
  initFirebase();
  db.ref(".info/connected").on("value", (snap) => callback(!!snap.val()));
}

export function subscribeThresholds(callback) {
  initFirebase();
  db.ref("motor/thresholds").on("value", (snap) => callback(snap.val() || {}));
}

export function subscribeHistorique(callback) {
  initFirebase();
  db.ref("motor/historique").limitToLast(30).on("value", (snap) => {
    const rows = [];
    snap.forEach((child) => rows.push({ id: child.key, ...child.val() }));
    callback(rows);
  });
}

export async function sendCommande(on) {
  initFirebase();
  cmdCounter += 1;
  const seq = "w" + cmdCounter + "-" + Date.now().toString(36);
  const payload = {
    wantOn: !!on,
    emergency: false,
    seq,
    src: "web",
    at: new Date().toISOString(),
  };
  await db.ref("motor/command").set(payload);
  await db.ref("motor/wantOn").set(!!on);
  await db.ref("motor/emergency").set(false);
  return { ok: true, commande: { etatCommande: !!on } };
}

const DEFAULT_USER = {
  id: 1,
  email: "nyenyeharry2@gmail.com",
  nom: "Harry Nyenye",
};

export function login() {
  return Promise.resolve({ user: DEFAULT_USER });
}

export function register() {
  return login();
}

export function logout() {
  return Promise.resolve({ ok: true });
}

export function me() {
  return login();
}

export function toUser(payload) {
  const u = payload?.user;
  if (!u) return null;
  return {
    id: u.id,
    email: u.email,
    displayName: u.nom || u.email,
  };
}

/** Convertit motor/live Firebase → format mesure du tableau de bord */
export function mapFirebaseLive(row, thresholds = {}) {
  if (!row) return null;
  const vibPct = Number(row.vib ?? row.vibration ?? 0);
  const vibMax = Number(thresholds.vibMax ?? 100);
  const rpmMax = Number(thresholds.rpmMax ?? 5000);

  let rmsMmS = Number(row.rmsMmS ?? row.rms ?? row.vibration_rms);
  if (!Number.isFinite(rmsMmS)) {
    rmsMmS = (vibPct / Math.max(vibMax, 1)) * 7.1;
  }

  const ts = row.ts != null ? new Date(Number(row.ts)) : new Date();

  return {
    x: Number(row.x ?? row.ax ?? 0),
    y: Number(row.y ?? row.ay ?? 0),
    z: Number(row.z ?? row.az ?? 0),
    rpm: Number(row.rpm ?? 0),
    rmsMmS,
    timestamp: ts,
    defautCapteur: false,
    uniteRms: "mm/s",
    motorOn: !!row.motorOn,
    alert: !!row.alert,
    emergency: !!row.emergency,
    reason: row.reason || "ok",
    _rpmMax: rpmMax,
    _vibMax: vibMax,
  };
}
