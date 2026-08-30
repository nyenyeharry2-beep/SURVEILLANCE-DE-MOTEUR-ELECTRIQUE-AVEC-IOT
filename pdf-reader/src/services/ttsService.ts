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

const FRENCH_LANGUAGE_CANDIDATES = ['fr-FR', 'fr-fr', 'fr-CA', 'fr-BE', 'fr'];

let initPromise: Promise<void> | null = null;
let resolvedLanguage = 'fr-FR';
let resolvedVoiceName: string | null = null;
let cachedVoices: NativeVoiceOption[] = [];

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

function prepareTextForNaturalSpeech(text: string): string {
  return text
    .replace(/\r\n/g, '\n')
    .replace(/\n{2,}/g, '. ')
    .replace(/\n/g, ' ')
    .replace(/\s+/g, ' ')
    .replace(/([.!?…])(?=[A-ZÀÂÄÉÈÊËÏÎÔÙÛÜŸÇ])/g, '$1 ')
    .replace(/([.!?…])(?!\s|$)/g, '$1 ')
    .replace(/([;:])\s*/g, '$1 ')
    .replace(/\s*—\s*/g, ', ')
    .replace(/\s*\.\.\.\s*/g, '. ')
    .trim();
}

function splitIntoSpeechUnits(text: string, maxLength = 420): string[] {
  const prepared = prepareTextForNaturalSpeech(text);
  if (!prepared) {
    return [];
  }

  const sentences = prepared
    .split(/(?<=[.!?…])\s+/)
    .map((part) => part.trim())
    .filter(Boolean);

  if (sentences.length === 0) {
    return prepared.length <= maxLength ? [prepared] : chunkText(prepared, maxLength);
  }

  const units: string[] = [];
  let buffer = '';

  for (const sentence of sentences) {
    const next = buffer ? `${buffer} ${sentence}` : sentence;
    if (next.length <= maxLength) {
      buffer = next;
      continue;
    }

    if (buffer) {
      units.push(buffer);
    }

    if (sentence.length <= maxLength) {
      buffer = sentence;
    } else {
      units.push(...chunkText(sentence, maxLength));
      buffer = '';
    }
  }

  if (buffer) {
    units.push(buffer);
  }

  return units;
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

function languagePrefix(language: string): string {
  return language.slice(0, 2).toLowerCase();
}

function scoreNativeVoice(voice: NativeVoiceOption, preferredLanguage: string): number {
  const lang = voice.lang.toLowerCase();
  const preferred = preferredLanguage.toLowerCase();
  const preferredShort = languagePrefix(preferred);
  const haystack = `${voice.voiceURI} ${voice.name}`.toLowerCase();

  let score = 0;

  if (lang === preferred) {
    score += 120;
  } else if (lang.startsWith(preferredShort)) {
    score += 100;
  } else if (preferredShort === 'fr' && lang.startsWith('fr')) {
    score += 90;
  }

  if (preferred.startsWith('fr') && lang === 'fr-fr') {
    score += 25;
  }

  if (voice.localService) {
    score += 8;
  }

  if (haystack.includes('wavenet') || haystack.includes('neural')) {
    score += 60;
  }
  if (haystack.includes('premium') || haystack.includes('enhanced') || haystack.includes('natural')) {
    score += 45;
  }
  if (haystack.includes('fr-fr') || haystack.includes('fra') || haystack.includes('french')) {
    score += 30;
  }
  if (haystack.includes('network')) {
    score += 20;
  }

  return score;
}

async function loadNativeVoices(): Promise<NativeVoiceOption[]> {
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

export async function getNativeVoicesForLanguage(language: string): Promise<NativeVoiceOption[]> {
  if (!isNativeTtsAvailable()) {
    return [];
  }

  await initializeNativeTts(language);
  const prefix = languagePrefix(language);
  const matching = cachedVoices.filter((voice) => voice.lang.toLowerCase().startsWith(prefix));

  return matching.sort(
    (left, right) => scoreNativeVoice(right, language) - scoreNativeVoice(left, language),
  );
}

async function pickNativeVoice(
  preferredLanguage: string,
  voiceUri: string | null,
): Promise<{ lang: string; voiceIndex: number; voiceName: string } | null> {
  const voices = cachedVoices.length > 0 ? cachedVoices : await loadNativeVoices();
  const prefix = languagePrefix(preferredLanguage);

  if (voiceUri) {
    const selected = voices.find((voice) => voice.voiceURI === voiceUri);
    if (selected && selected.lang.toLowerCase().startsWith(prefix)) {
      return {
        lang: selected.lang,
        voiceIndex: selected.index,
        voiceName: selected.name,
      };
    }
  }

  const matching = voices
    .filter((voice) => voice.lang.toLowerCase().startsWith(prefix))
    .sort((left, right) => scoreNativeVoice(right, preferredLanguage) - scoreNativeVoice(left, preferredLanguage));

  if (matching.length > 0) {
    const best = matching[0];
    return {
      lang: best.lang,
      voiceIndex: best.index,
      voiceName: best.name,
    };
  }

  return null;
}

async function isLanguageSupported(lang: string): Promise<boolean> {
  try {
    const result = await TextToSpeech.isLanguageSupported({ lang });
    return result.supported;
  } catch {
    return false;
  }
}

async function pickNativeLanguageTag(preferred: string): Promise<string> {
  const prefix = languagePrefix(preferred);
  const candidates =
    prefix === 'fr'
      ? [preferred, ...FRENCH_LANGUAGE_CANDIDATES]
      : [preferred, preferred.includes('-') ? preferred.split('-')[0] : `${preferred}-${preferred.toUpperCase()}`];

  const unique = [...new Set(candidates.filter(Boolean))];

  for (const lang of unique) {
    if (await isLanguageSupported(lang)) {
      return lang;
    }
  }

  const voices = cachedVoices.length > 0 ? cachedVoices : await loadNativeVoices();
  const voiceMatch = voices.find((voice) => voice.lang.toLowerCase().startsWith(prefix));
  if (voiceMatch) {
    return voiceMatch.lang;
  }

  if (prefix === 'fr') {
    throw new Error(
      'Aucune voix française détectée. Installez « Google Text-to-Speech » puis la voix Français (France) via « Installer la voix ».',
    );
  }

  return preferred;
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
        await loadNativeVoices();
        const voice = await pickNativeVoice(preferredLanguage, null);
        if (voice) {
          resolvedLanguage = voice.lang;
          resolvedVoiceName = voice.voiceName;
          return;
        }

        resolvedLanguage = await pickNativeLanguageTag(preferredLanguage);
        resolvedVoiceName = null;
        return;
      } catch (error) {
        if (error instanceof Error && error.message.includes('Aucune voix française')) {
          throw error;
        }
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

export function getResolvedNativeVoiceName(): string | null {
  return resolvedVoiceName;
}

async function speakNativeChunk(
  text: string,
  options: SpeakOptions,
): Promise<void> {
  const voice = await pickNativeVoice(options.language, options.voiceUri);
  const lang = voice?.lang ?? (await pickNativeLanguageTag(options.language));
  resolvedLanguage = lang;
  resolvedVoiceName = voice?.voiceName ?? null;

  const naturalRate = Math.max(0.5, Math.min(1.6, options.rate * 0.96));

  const speakOptions: Parameters<typeof TextToSpeech.speak>[0] = {
    text,
    lang,
    rate: naturalRate,
    pitch: 1,
    volume: 1,
    voice: voice?.voiceIndex,
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
        const refreshedVoice = await pickNativeVoice(options.language, options.voiceUri);
        if (refreshedVoice) {
          speakOptions.lang = refreshedVoice.lang;
          speakOptions.voice = refreshedVoice.voiceIndex;
        }
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
      'Voix française non installée. Allez dans Réglages → Installer la voix, puis choisissez une voix « Français ».',
    );
  }

  if (message.includes('Not yet initialized') || message.includes('not available')) {
    throw new Error('Moteur vocal en cours de démarrage. Réessayez dans quelques secondes.');
  }

  if (message.includes('Failed to read text')) {
    throw new Error(
      'Impossible de lire le texte. Choisissez une voix française dans Réglages, puis réessayez.',
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

        const units = splitIntoSpeechUnits(text);
        for (const unit of units) {
          if (pausedOnNative) {
            break;
          }
          await speakNativeChunk(unit, options);
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

    const utterance = new SpeechSynthesisUtterance(prepareTextForNaturalSpeech(text));
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
    await initializeNativeTts('fr-FR');
    const french = cachedVoices
      .map((voice) => voice.lang)
      .filter((lang, index, list) => lang.toLowerCase().startsWith('fr') && list.indexOf(lang) === index);

    if (french.length > 0) {
      return french.sort();
    }

    const { languages } = await TextToSpeech.getSupportedLanguages();
    const sorted = languages.filter((lang) => lang.toLowerCase().startsWith('fr'));
    return sorted.length > 0 ? sorted : ['fr-FR', 'en-US'];
  } catch {
    return ['fr-FR', 'fr-CA', 'en-US'];
  }
}
