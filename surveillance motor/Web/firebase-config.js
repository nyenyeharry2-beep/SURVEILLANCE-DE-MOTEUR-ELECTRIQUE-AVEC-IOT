/**
 * Configuration Firebase Web — À PERSONNALISER
 * Récupérer ces valeurs dans Firebase Console → Paramètres du projet → Vos applications
 */
const firebaseConfig = {
  apiKey: "VOTRE_API_KEY",
  authDomain: "VOTRE_PROJET.firebaseapp.com",
  databaseURL: "https://VOTRE_PROJET-default-rtdb.REGION.firebasedatabase.app",
  projectId: "VOTRE_PROJET",
  storageBucket: "VOTRE_PROJET.appspot.com",
  messagingSenderId: "VOTRE_SENDER_ID",
  appId: "VOTRE_APP_ID"
};

/* Exposer pour app.js */
window.MOTORGUARD_FIREBASE_CONFIG = firebaseConfig;
