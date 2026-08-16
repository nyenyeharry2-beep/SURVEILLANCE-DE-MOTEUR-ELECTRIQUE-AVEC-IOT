/**
 * Calculs de surveillance moteur (ADXL345 + capteur IR).
 *
 * Les constantes ci-dessous sont des VALEURS PROVISOIRES,
 * à adapter au moteur et aux normes retenues (ex. ISO 10816).
 * Elles ne constituent PAS des limites officielles imposées.
 */

export const SEUILS = {
  /** RMS vitesse vibratoire — niveau ATTENTION (mm/s) */
  rmsAttentionMmS: 2.8,
  /** RMS vitesse vibratoire — niveau CRITIQUE (mm/s) */
  rmsCritiqueMmS: 7.1,

  rpmArret: 50,
  rpmSousVitesse: 1350,
  rpmSurvitesse: 1650,

  /**
   * Fréquence dominante estimée (Hz) pour la conversion
   * accélération → vitesse si l'intégration temporelle
   * n'est pas possible (fenêtre trop courte).
   */
  frequenceVibrationHz: 25,

  /** Fréquence d'échantillonnage ADXL345 attendue (Hz) — à aligner sur l'ESP32. */
  adxlSampleRateHz: 100,

  /** Impulsions capteur IR par tour de moteur. */
  impulsionsParTour: 1,

  gEnMs2: 9.80665,

  /** Au-delà de ce délai sans mesure, les données sont considérées absentes. */
  staleMs: 8000,
};

const HISTORY_MAX = 48;

/**
 * RMS classique : sqrt( moyenne(x²) )
 * @param {number[]} values
 * @returns {number}
 */
export function calculateRMS(values) {
  const nums = (Array.isArray(values) ? values : [])
    .map(Number)
    .filter((v) => Number.isFinite(v));

  if (!nums.length) return 0;

  const meanSquares = nums.reduce((sum, v) => sum + v * v, 0) / nums.length;
  return Math.sqrt(meanSquares);
}

/**
 * Accélération résultante / norme euclidienne des axes ADXL345.
 */
export function calculateResultante(x, y, z) {
  const X = Number(x) || 0;
  const Y = Number(y) || 0;
  const Z = Number(z) || 0;
  return Math.sqrt(X * X + Y * Y + Z * Z);
}

/**
 * Retire la composante continue (gravité, offset) d'une série.
 */
export function removeDcOffset(values) {
  const nums = (values || []).map(Number).filter(Number.isFinite);
  if (!nums.length) return [];
  const mean = nums.reduce((s, v) => s + v, 0) / nums.length;
  return nums.map((v) => v - mean);
}

/**
 * Convertit un RMS d'accélération (m/s²) en RMS de vitesse (mm/s).
 *
 * Hypothèse : vibration approximativement sinusoïdale à frequenceHz.
 *   v_rms = a_rms / (2 π f)
 *
 * Ne pas utiliser cette fonction pour relabeler une accélération
 * en prétendant qu'elle est déjà en mm/s.
 */
export function accelerationRmsToVelocityMmS(
  accelRmsMs2,
  frequenceHz = SEUILS.frequenceVibrationHz
) {
  const a = Number(accelRmsMs2);
  const f = Number(frequenceHz);

  if (!Number.isFinite(a) || a < 0) return 0;
  if (!Number.isFinite(f) || f <= 0) return 0;

  const velocityMs = a / (2 * Math.PI * f);
  return velocityMs * 1000;
}

/**
 * Intègre une série d'accélérations (en g) pour obtenir un RMS de
 * vitesse vibratoire en mm/s.
 *
 * Méthode : retrait du continu → conversion g → m/s² → intégration
 * d'Euler avec fuite (passe-haut) pour éviter la dérive → RMS → mm/s.
 */
export function integrateAccelGToVelocityRmsMmS(
  accelGSamples,
  sampleRateHz = SEUILS.adxlSampleRateHz
) {
  const rate = Number(sampleRateHz);
  if (!Number.isFinite(rate) || rate <= 0) return 0;

  const acG = removeDcOffset(accelGSamples);
  if (acG.length < 2) {
    const rmsG = calculateRMS(acG);
    const rmsMs2 = rmsG * SEUILS.gEnMs2;
    return accelerationRmsToVelocityMmS(rmsMs2);
  }

  const dt = 1 / rate;
  const leak = 0.995;
  let velocityMs = 0;
  const velocities = [];

  for (const g of acG) {
    const accelMs2 = g * SEUILS.gEnMs2;
    velocityMs = (velocityMs + accelMs2 * dt) * leak;
    velocities.push(velocityMs);
  }

  return calculateRMS(velocities) * 1000;
}

/**
 * Indicateurs de vibration à partir d'une fenêtre d'échantillons ADXL345 (axes en g).
 */
export function computeVibrationFromSamples(samples, options = {}) {
  const list = Array.isArray(samples) ? samples : [];
  const last = list[list.length - 1] || { x: 0, y: 0, z: 0 };

  const x = Number(last.x) || 0;
  const y = Number(last.y) || 0;
  const z = Number(last.z) || 0;
  const resultante = calculateResultante(x, y, z);

  const magnitudesG = list.map((s) =>
    calculateResultante(s?.x, s?.y, s?.z)
  );

  const rmsMmS = integrateAccelGToVelocityRmsMmS(
    magnitudesG,
    options.sampleRateHz ?? SEUILS.adxlSampleRateHz
  );

  return {
    x,
    y,
    z,
    resultante,
    rmsMmS,
    uniteAccel: "g",
    uniteRms: "mm/s",
  };
}

/**
 * RPM à partir des impulsions du capteur IR.
 * rpm = (impulsions / impulsionsParTour / duree_s) * 60
 */
export function calculateRpmFromPulses(
  pulseCount,
  elapsedMs,
  pulsesPerRevolution = SEUILS.impulsionsParTour
) {
  const pulses = Number(pulseCount);
  const ms = Number(elapsedMs);
  const ppr = Number(pulsesPerRevolution) || 1;

  if (!Number.isFinite(pulses) || pulses < 0) return 0;
  if (!Number.isFinite(ms) || ms <= 0) return 0;

  return (pulses / ppr / (ms / 1000)) * 60;
}

export function resolveRpm(data = {}) {
  const direct = Number(data.rpm ?? data.vitesse ?? data.vitesseRotation);
  if (Number.isFinite(direct) && direct >= 0) return direct;

  if (
    data.impulsions != null &&
    (data.periodeMs != null || data.dureeMs != null)
  ) {
    return calculateRpmFromPulses(
      data.impulsions,
      data.periodeMs ?? data.dureeMs,
      data.impulsionsParTour ?? SEUILS.impulsionsParTour
    );
  }

  return 0;
}

export function rmsLevel(rmsMmS) {
  const value = Number(rmsMmS) || 0;
  if (value >= SEUILS.rmsCritiqueMmS) return "critique";
  if (value >= SEUILS.rmsAttentionMmS) return "attention";
  return "normal";
}

export function rmsLevelLabel(level) {
  if (level === "critique") return "CRITIQUE";
  if (level === "attention") return "ATTENTION";
  return "NORMAL";
}

/**
 * État moteur dérivé des mesures (la commande logicielle est distincte).
 * Valeurs : arrêté | en fonctionnement | alerte | défaut
 */
export function deriveMotorState(motor, extras = {}) {
  const rpm = Number(motor.rpm) || 0;
  const rms = Number(motor.rmsMmS) || 0;
  const { sensorFault, invalidData, noData, commandedOn } = extras;

  if (noData || sensorFault || invalidData) return "défaut";
  if (rms >= SEUILS.rmsCritiqueMmS) return "défaut";

  const speedAlert =
    rpm >= SEUILS.rpmArret &&
    (rpm < SEUILS.rpmSousVitesse || rpm > SEUILS.rpmSurvitesse);
  const vibAlert = rms >= SEUILS.rmsAttentionMmS;

  if (speedAlert || vibAlert) return "alerte";
  if (rpm >= SEUILS.rpmArret) return "en fonctionnement";
  if (commandedOn && rpm < SEUILS.rpmArret) return "alerte";
  return "arrêté";
}

export function buildDiagnostics(motor, extras = {}) {
  const items = [];
  const rpm = Number(motor.rpm) || 0;
  const rms = Number(motor.rmsMmS) || 0;
  const { noData, sensorFault, invalidData, stale } = extras;

  if (noData || stale) {
    items.push({
      code: "unavailable",
      label: "Données indisponibles",
      severity: "warning",
    });
  }

  if (sensorFault) {
    items.push({
      code: "sensor",
      label: "Défaut de capteur",
      severity: "fault",
    });
  }

  if (invalidData) {
    items.push({
      code: "invalid",
      label: "Données invalides",
      severity: "fault",
    });
  }

  if (!noData && !invalidData) {
    if (rms >= SEUILS.rmsCritiqueMmS) {
      items.push({
        code: "vib-crit",
        label: "Vibration critique",
        severity: "fault",
      });
    } else if (rms >= SEUILS.rmsAttentionMmS) {
      items.push({
        code: "vib-high",
        label: "Vibration élevée",
        severity: "warning",
      });
    } else {
      items.push({
        code: "vib-ok",
        label: "Vibration normale",
        severity: "ok",
      });
    }

    if (rpm < SEUILS.rpmArret) {
      items.push({
        code: "stopped",
        label: "Moteur arrêté",
        severity: "info",
      });
    } else if (rpm > SEUILS.rpmSurvitesse) {
      items.push({
        code: "over",
        label: "Survitesse",
        severity: "warning",
      });
    } else if (rpm < SEUILS.rpmSousVitesse) {
      items.push({
        code: "under",
        label: "Sous-vitesse",
        severity: "warning",
      });
    } else {
      items.push({
        code: "rpm-ok",
        label: "Vitesse normale",
        severity: "ok",
      });
    }
  }

  return items;
}

export function isInvalidSample(x, y, z, rpm) {
  const nums = [x, y, z, rpm];
  return nums.some((v) => !Number.isFinite(Number(v)));
}

export function pushSampleWindow(window, sample) {
  const next = [...window, sample];
  if (next.length > HISTORY_MAX) next.splice(0, next.length - HISTORY_MAX);
  return next;
}

export function formatNumber(value, digits = 2) {
  const number = Number(value);
  if (!Number.isFinite(number)) return "—";
  return number.toFixed(digits);
}

export function formatDate(value) {
  if (!value) return "—";

  let date;
  try {
    date = value.toDate ? value.toDate() : new Date(value);
  } catch {
    return "—";
  }

  if (Number.isNaN(date.getTime())) return "—";

  return date.toLocaleString("fr-FR", {
    day: "2-digit",
    month: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  });
}

export function formatTime(value) {
  if (!value) return "";
  let date;
  try {
    date = value.toDate ? value.toDate() : new Date(value);
  } catch {
    return "";
  }
  if (Number.isNaN(date.getTime())) return "";
  return date.toLocaleTimeString("fr-FR", {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  });
}

export const ETAT_LABELS = {
  arrêté: "ARRÊTÉ",
  "en fonctionnement": "EN FONCTIONNEMENT",
  alerte: "ALERTE",
  défaut: "DÉFAUT",
};
