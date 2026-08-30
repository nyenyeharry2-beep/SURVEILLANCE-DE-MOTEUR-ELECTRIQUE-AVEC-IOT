import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
  base: './',
  plugins: [
    react(),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: [
        'favicon.svg',
        'icons.svg',
        'tessdata/*.gz',
        'tesseract/*',
        'piper/**/*',
      ],
      manifest: {
        name: 'Lumen Reader',
        short_name: 'Lumen',
        description:
          'Lecteur PDF hors ligne avec synthèse vocale, OCR et stockage local',
        start_url: '/',
        scope: '/',
        display: 'standalone',
        orientation: 'portrait-primary',
        background_color: '#0f172a',
        theme_color: '#4f46e5',
        lang: 'fr',
        categories: ['books', 'education', 'productivity'],
        icons: [
          {
            src: '/pwa-192.png',
            sizes: '192x192',
            type: 'image/png',
            purpose: 'any',
          },
          {
            src: '/pwa-512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any',
          },
          {
            src: '/pwa-512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'maskable',
          },
        ],
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg,wasm,gz,mjs,webp,webmanifest,onnx,json}'],
        navigateFallback: '/index.html',
        maximumFileSizeToCacheInBytes: 70 * 1024 * 1024,
        runtimeCaching: [
          {
            urlPattern: ({ request }) => request.destination === 'document',
            handler: 'NetworkFirst',
            options: {
              cacheName: 'pages',
              networkTimeoutSeconds: 3,
            },
          },
        ],
      },
      devOptions: {
        enabled: true,
      },
    }),
  ],
  optimizeDeps: {
    include: ['pdfjs-dist', 'tesseract.js', 'dexie', '@realtimex/piper-tts-web', 'onnxruntime-web'],
  },
  build: {
    chunkSizeWarningLimit: 5000,
  },
});
