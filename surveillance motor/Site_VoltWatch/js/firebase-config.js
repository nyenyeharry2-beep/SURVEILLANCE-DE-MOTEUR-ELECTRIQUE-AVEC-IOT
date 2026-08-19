/**
 * Colle ici la config de ton projet Firebase (Console Firebase → App Web).
 * Remplace chaque "PLACEHOLDER" par la vraie valeur.
 *
 * databaseURL est OBLIGATOIRE pour Realtime Database.
 * Tant que les PLACEHOLDER sont là, le site reste en mode démo.
 */
export const firebaseConfig = {
  apiKey: "PLACEHOLDER",
  authDomain: "PLACEHOLDER",
  databaseURL: "PLACEHOLDER",
  projectId: "PLACEHOLDER",
  storageBucket: "PLACEHOLDER",
  messagingSenderId: "PLACEHOLDER",
  appId: "PLACEHOLDER",
};

export function isFirebaseConfigured() {
  return Object.entries(firebaseConfig).every(
    ([key, value]) =>
      typeof value === "string" &&
      value &&
      value !== "PLACEHOLDER" &&
      (key !== "databaseURL" || value.includes("firebasedatabase.app"))
  );
}
