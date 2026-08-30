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

export interface NativeVoiceOption {
  index: number;
  voiceURI: string;
  name: string;
  lang: string;
  localService: boolean;
}

const FRENCH_LANGS = ['fr-FR', 'fr-CA', 'fr-BE', 'fr'];

let engineReady = false;
let resolvedLanguage = 'fr-FR';
let resolvedVoiceName: string | null = null;
let cachedVoices: NativeVoiceOption[] = [];
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

function prepareText(text: string): string {
  return text
    .replace(/\r\n/g, '\n')
    .replace(/\n{2,}/g, '. ')
    .replace(/\n/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function chunkText(text: string, maxLength = 2800): string[] {
  const trimmed = prepareText(text);
  if (!trimmed) {
    return [];
  }
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

async function waitForNativeEngine(): Promise<void> {
  for (let attempt = 0; attempt < 20; attempt += 1) {
    try {
      await TextToSpeech.getSupportedLanguages();
      engineReady = true;
      return;
    } catch {
      await delay(150);
    }
  }
}

async function loadNativeVoices(): Promise<NativeVoiceOption[]> {
  await waitForNativeEngine();
  const { voices } = await TextToSpeech.getSupportedVoices();
  cachedVoices = voices.map((voice, index) => ({
    index,
    voiceURI: voice.voiceURI,
    name: voice.name,
    lang: voice.lang,
    localService: voice.localService,
  }));
  return cachedVoices;
}

function pickFrenchVoice(voiceUri: string | null): NativeVoiceOption | null {
  if (voiceUri) {
    const selected = cachedVoices.find((voice) => voice.voiceURI === voiceUri);
    if (selected?.lang.toLowerCase().startsWith('fr')) {
      return selected;
    }
  }

  const frenchVoices = cachedVoices.filter((voice) => voice.lang.toLowerCase().startsWith('fr'));
  if (frenchVoices.length === 0) {
    return null;
  }

  return frenchVoices.find((voice) => voice.lang.toLowerCase() === 'fr-fr') ?? frenchVoices[0];
}

export async function initializeNativeTts(preferredLanguage = 'fr-FR'): Promise<void> {
  if (!isNativeTtsAvailable()) {
    return;
  }

  await loadNativeVoices();
  const voice = pickFrenchVoice(null);
  if (voice) {
    resolvedLanguage = voice.lang;
    resolvedVoiceName = voice.name;
    return;
  }

  void preferredLanguage;
  resolvedLanguage = 'fr-FR';
  resolvedVoiceName = null;
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

export function getResolvedNativeVoiceName(): string | null {
  return resolvedVoiceName;
}

export async function getNativeVoicesForLanguage(language: string): Promise<NativeVoiceOption[]> {
  if (!isNativeTtsAvailable()) {
    return [];
  }
  await loadNativeVoices();
  const prefix = language.slice(0, 2).toLowerCase();
  return cachedVoices.filter((voice) => voice.lang.toLowerCase().startsWith(prefix));
}

export async function getNativeLanguages(): Promise<string[]> {
  if (!isNativeTtsAvailable()) {
    return ['fr-FR', 'en-US'];
  }
  try {
    await loadNativeVoices();
    const french = [
      ...new Set(
        cachedVoices
          .map((voice) => voice.lang)
          .filter((lang) => lang.toLowerCase().startsWith('fr')),
      ),
    ];
    return french.length > 0 ? french : ['fr-FR'];
  } catch {
    return ['fr-FR'];
  }
}

async function speakNative(text: string, options: SpeakOptions): Promise<void> {
  if (!engineReady) {
    await waitForNativeEngine();
  }
  if (cachedVoices.length === 0) {
    await loadNativeVoices();
  }

  const voice = pickFrenchVoice(options.voiceUri);
  const langCandidates = [
    voice?.lang,
    options.language,
    ...FRENCH_LANGS,
  ].filter(Boolean) as string[];

  const uniqueLangs = [...new Set(langCandidates)];
  const rate = Math.max(0.5, Math.min(1.6, options.rate));
  const chunks = chunkText(text);

  let lastError: unknown;

  for (const chunk of chunks) {
    if (pausedOnNative) {
      return;
    }

    let spoke = false;
    for (const lang of uniqueLangs) {
      const voiceIndexes = voice ? [voice.index, -1] : [-1];
      for (const voiceIndex of voiceIndexes) {
        try {
          await TextToSpeech.speak({
            text: chunk,
            lang,
            rate,
            pitch: 1,
            volume: 1,
            voice: voiceIndex,
          });
          resolvedLanguage = lang;
          resolvedVoiceName = voice?.name ?? resolvedVoiceName;
          spoke = true;
          break;
        } catch (error) {
          lastError = error;
        }
      }
      if (spoke) {
        break;
      }
    }

    if (!spoke) {
      throw lastError instanceof Error ? lastError : new Error('Lecture vocale impossible');
    }
  }
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
        handlers.onStart?.();
        this.speaking = true;
        await speakNative(text, options);
        this.speaking = false;
        if (!pausedOnNative) {
          handlers.onEnd?.();
        }
      } catch (error) {
        this.speaking = false;
        handlers.onError?.(
          error instanceof Error
            ? `${error.message}. Essayez Réglages → Mettre à jour les voix du téléphone.`
            : 'Erreur de synthèse vocale Android',
        );
      }
      return;
    }

    if (!window.speechSynthesis) {
      handlers.onError?.('Synthèse vocale non disponible sur cet appareil');
      return;
    }

    const utterance = new SpeechSynthesisUtterance(prepareText(text));
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
      try {
        await TextToSpeech.stop();
      } catch {
        // ignore
      }
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
