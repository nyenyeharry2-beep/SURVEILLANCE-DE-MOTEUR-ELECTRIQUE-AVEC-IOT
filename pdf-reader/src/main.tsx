import { Capacitor } from '@capacitor/core';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { registerSW } from 'virtual:pwa-register';
import './index.css';
import App from './App.tsx';

if (!Capacitor.isNativePlatform()) {
  registerSW({
    immediate: true,
    onOfflineReady() {
      console.info('Lumen Reader est prêt pour une utilisation hors ligne.');
    },
  });
}

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App />
  </StrictMode>,
);
