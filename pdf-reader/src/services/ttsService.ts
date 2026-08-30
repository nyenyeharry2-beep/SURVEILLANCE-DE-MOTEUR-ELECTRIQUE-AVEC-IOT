import { Capacitor } from '@capacitor/core';
import { TextToSpeech } from '@capacitor-community/text-to-speech';

export interface SpeakHandlers {
  onStart?: () => void;
  onEnd?: () => void;
  onError?: (message: string) => void;
}

export interface SpeakOptions {
  rate: number;
  language: string;
  voiceUri: string | null;
}

const LANGUAGE_FALLBACKS = ['fr-FR', 'fr', 'en-US', 'en', 'en-GB'];

let initPromise: Promise<void> | null = null;
let resolvedLanguage = 'fr-FR';

export function resetNativeTtsInit(): void {
  initPromise = null;
}
let pausedOnNative = false;
let webPaused = false;

export function isNativeTtsAvailable(): boolean {
  return Capacitor.isNativePlatform();
}

export function getAvailableVoices(): SpeechSynthesisVoice[] {
  if (typeof window === 'undefined' || !window.speechSynthesis) {
    return [];
  }
  return window.speechSynthesis.getVoices();
}

function pickWebVoice(language: string, voiceUri: string | null): SpeechSynthesisVoice | null {
  const voices = getAvailableVoices();
  if (voiceUri) {
    const selected = voices.find((voice) => voice.voiceURI === voiceUri);
    if (selected) {
      return selected;
    }
  }

  const langPrefix = language.slice(0, 2);
  return (
    voices.find((voice) => voice.lang.startsWith(langPrefix) && voice.localService) ??
    voices.find((voice) => voice.lang.startsWith(langPrefix)) ??
    voices[0] ??
    null
  );
}

function chunkText(text: string, maxLength = 3200): string[] {
  const trimmed = text.trim();
  if (trimmed.length <= maxLength) {
    return [trimmed];
  }

  const chunks: string[] = [];
  let remaining = trimmed;

  while (remaining.length > maxLength) {
    const slice = remaining.slice(0, maxLength);
    const breakAt = Math.max(
      slice.lastIndexOf('. '),
      slice.lastIndexOf('! '),
      slice.lastIndexOf('? '),
      slice.lastIndexOf(' '),
    );
    const cut = breakAt > 80 ? breakAt + 1 : maxLength;
    chunks.push(remaining.slice(0, cut).trim());
    remaining = remaining.slice(cut).trim();
  }

  if (remaining) {
    chunks.push(remaining);
  }

  return chunks.filter(Boolean);
}

async function delay(ms: number): Promise<void> {
  await new Promise((resolve) => setTimeout(resolve, ms));
}

async function isLanguageSupported(lang: string): Promise<boolean> {
  try {
    const result = await TextToSpeech.isLanguageSupported({ lang });
    return result.supported;
  } catch {
    return false;
  }
}

async function pickNativeLanguage(preferred: string): Promise<string> {
  const candidates = [
    preferred,
    preferred.includes('-') ? preferred.split('-')[0] : `${preferred}-${preferred.toUpperCase()}`,
    ...LANGUAGE_FALLBACKS,
  ];

  const unique = [...new Set(candidates.filter(Boolean))];

  for (const lang of unique) {
    if (await isLanguageSupported(lang)) {
      return lang;
    }
  }

  try {
    const { languages } = await TextToSpeech.getSupportedLanguages();
    if (languages.length > 0) {
      return languages[0];
    }
  } catch {
    // ignore
  }

  return 'en-US';
}

export async function initializeNativeTts(preferredLanguage = 'fr-FR'): Promise<void> {
  if (!isNativeTtsAvailable()) {
    return;
  }

  if (initPromise) {
    return initPromise;
  }

  initPromise = (async () => {
    for (let attempt = 0; attempt < 40; attempt += 1) {
      try {
        await TextToSpeech.getSupportedLanguages();
        resolvedLanguage = await pickNativeLanguage(preferredLanguage);
        if (await isLanguageSupported(resolvedLanguage)) {
          return;
        }
      } catch {
        // moteur TTS pas encore prêt
      }
      await delay(200);
    }

    throw new Error(
      'Moteur vocal non disponible. Installez « Google Text-to-Speech » et la voix française (Réglages → Installer la voix).',
    );
  })();

  try {
    await initPromise;
  } catch (error) {
    resetNativeTtsInit();
    throw error;
  }
}

export async function openNativeTtsInstall(): Promise<void> {
  if (!isNativeTtsAvailable()) {
    return;
  }
  await TextToSpeech.openInstall();
}

export function getResolvedNativeLanguage(): string {
  return resolvedLanguage;
}

async function speakNativeChunk(
  text: string,
  options: SpeakOptions,
): Promise<void> {
  const lang = await pickNativeLanguage(options.language);
  resolvedLanguage = lang;

  const speakOptions: Parameters<typeof TextToSpeech.speak>[0] = {
    text,
    lang,
    rate: Math.max(0.5, Math.min(2, options.rate)),
    pitch: 1,
    volume: 1,
  };

  let lastError: unknown;

  for (let attempt = 0; attempt < 4; attempt += 1) {
    try {
      await TextToSpeech.speak(speakOptions);
      return;
    } catch (error) {
      lastError = error;
      resetNativeTtsInit();
      await delay(300 * (attempt + 1));
      try {
        await initializeNativeTts(options.language);
      } catch {
        // nouvelle tentative au prochain tour
      }
    }
  }

  const message =
    lastError instanceof Error ? lastError.message : String(lastError ?? 'Erreur inconnue');

  if (
    message.includes('not supported') ||
    message.includes('language') ||
    message.includes('This language is not supported')
  ) {
    throw new Error(
      'Voix française non installée. Allez dans Réglages → Installer la voix, ou installez « Google Text-to-Speech » sur votre téléphone.',
    );
  }

  if (message.includes('Not yet initialized') || message.includes('not available')) {
    throw new Error('Moteur vocal en cours de démarrage. Réessayez dans quelques secondes.');
  }

  if (message.includes('Failed to read text')) {
    throw new Error(
      'Impossible de lire le texte. Vérifiez que la voix est installée (Réglages → Installer la voix) puis réessayez.',
    );
  }

  throw new Error(`Synthèse vocale : ${message}`);
}

export class TextToSpeechEngine {
  private speaking = false;

  async speak(
    text: string,
    options: SpeakOptions,
    handlers: SpeakHandlers = {},
  ): Promise<void> {
    await this.stop();
    pausedOnNative = false;
    webPaused = false;

    if (!text.trim()) {
      handlers.onEnd?.();
      return;
    }

    if (isNativeTtsAvailable()) {
      try {
        await initializeNativeTts(options.language);
        handlers.onStart?.();
        this.speaking = true;

        const chunks = chunkText(text);
        for (const chunk of chunks) {
          if (pausedOnNative) {
            break;
          }
          await speakNativeChunk(chunk, options);
        }

        this.speaking = false;
        if (!pausedOnNative) {
          handlers.onEnd?.();
        }
      } catch (error) {
        this.speaking = false;
        handlers.onError?.(
          error instanceof Error ? error.message : 'Erreur de synthèse vocale Android',
        );
      }
      return;
    }

    if (!window.speechSynthesis) {
      handlers.onError?.('Synthèse vocale non disponible sur cet appareil');
      return;
    }

    const utterance = new SpeechSynthesisUtterance(text);
    utterance.rate = options.rate;
    utterance.lang = options.language;

    const voice = pickWebVoice(options.language, options.voiceUri);
    if (voice) {
      utterance.voice = voice;
    }

    utterance.onstart = () => {
      this.speaking = true;
      handlers.onStart?.();
    };
    utterance.onend = () => {
      this.speaking = false;
      if (!webPaused) {
        handlers.onEnd?.();
      }
    };
    utterance.onerror = () => {
      this.speaking = false;
      handlers.onError?.('Erreur de synthèse vocale');
    };

    window.speechSynthesis.speak(utterance);
  }

  async pause(): Promise<void> {
    if (isNativeTtsAvailable()) {
      pausedOnNative = true;
      this.speaking = false;
      await TextToSpeech.stop();
      return;
    }

    if (window.speechSynthesis) {
      webPaused = true;
      window.speechSynthesis.pause();
    }
  }

  async resume(): Promise<void> {
    if (isNativeTtsAvailable()) {
      pausedOnNative = false;
      return;
    }

    if (window.speechSynthesis) {
      webPaused = false;
      window.speechSynthesis.resume();
    }
  }

  async stop(): Promise<void> {
    pausedOnNative = false;
    webPaused = false;
    this.speaking = false;

    if (isNativeTtsAvailable()) {
      try {
        await TextToSpeech.stop();
      } catch {
        // ignore
      }
      return;
    }

    if (window.speechSynthesis) {
      window.speechSynthesis.cancel();
    }
  }

  isSpeaking(): boolean {
    if (isNativeTtsAvailable()) {
      return this.speaking;
    }
    return window.speechSynthesis?.speaking ?? false;
  }

  isPaused(): boolean {
    if (isNativeTtsAvailable()) {
      return pausedOnNative;
    }
    return window.speechSynthesis?.paused ?? webPaused;
  }
}

export const ttsEngine = new TextToSpeechEngine();

export async function getNativeLanguages(): Promise<string[]> {
  if (!isNativeTtsAvailable()) {
    return ['fr-FR', 'en-US'];
  }
  try {
    await initializeNativeTts();
    const { languages } = await TextToSpeech.getSupportedLanguages();
    return languages.length > 0 ? languages : ['fr-FR', 'en-US'];
  } catch {
    return ['fr-FR', 'en-US'];
  }
}
