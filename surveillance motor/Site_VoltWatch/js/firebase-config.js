/**
 * Colle ici la config de ton projet Firebase (Console Firebase → App Web).
 * Remplace chaque "PLACEHOLDER" par la vraie valeur.
 *
 * Tant que les PLACEHOLDER sont là, le site reste en mode démo.
 */
export const firebaseConfig = {
  apiKey: "PLACEHOLDER",
  authDomain: "PLACEHOLDER",
  projectId: "PLACEHOLDER",
  storageBucket: "PLACEHOLDER",
  messagingSenderId: "PLACEHOLDER",
  appId: "PLACEHOLDER",
};

export function isFirebaseConfigured() {
  return Object.values(firebaseConfig).every(
    (value) => typeof value === "string" && value && value !== "PLACEHOLDER"
  );
}
