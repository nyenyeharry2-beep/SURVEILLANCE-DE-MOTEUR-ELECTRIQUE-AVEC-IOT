import { Capacitor } from '@capacitor/core';
import {
  BUNDLED_VOICE_LABEL,
  getBundledVoiceLabel,
  initializeBundledTts,
  isBundledPaused,
  isBundledSpeaking,
  isBundledTtsAvailable,
  pauseBundledSpeech,
  resetBundledTts,
  resumeBundledSpeech,
  speakWithBundledVoice,
  stopBundledSpeech,
  type BundledTtsProgress,
} from './bundledTtsService';

export type { BundledTtsProgress as PiperInitProgress };

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

export { BUNDLED_VOICE_LABEL };

let webPaused = false;

export function isNativeTtsAvailable(): boolean {
  return Capacitor.isNativePlatform();
}

export function usesBundledVoice(): boolean {
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
    if (selected) return selected;
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
  return text.replace(/\s+/g, ' ').trim();
}

export async function initializeNativeTts(
  _preferredLanguage = 'fr-FR',
  onProgress?: (progress: BundledTtsProgress) => void,
): Promise<void> {
  if (!usesBundledVoice()) return;
  await initializeBundledTts(onProgress);
}

export function getResolvedNativeLanguage(): string {
  return 'fr-FR';
}

export function getResolvedNativeVoiceName(): string {
  return getBundledVoiceLabel();
}

export async function openNativeTtsInstall(): Promise<void> {
  return;
}

export async function getNativeVoicesForLanguage(_language: string) {
  return [{ index: 0, voiceURI: 'bundled', name: BUNDLED_VOICE_LABEL, lang: 'fr-FR', localService: true }];
}

export async function getNativeLanguages(): Promise<string[]> {
  return ['fr-FR'];
}

export class TextToSpeechEngine {
  private speaking = false;

  async speak(text: string, options: SpeakOptions, handlers: SpeakHandlers = {}): Promise<void> {
    await this.stop();
    webPaused = false;

    if (!text.trim()) {
      handlers.onEnd?.();
      return;
    }

    if (usesBundledVoice() && isBundledTtsAvailable()) {
      try {
        handlers.onStart?.();
        this.speaking = true;
        await speakWithBundledVoice(text, options.rate);
        this.speaking = false;
        if (!isBundledPaused()) handlers.onEnd?.();
      } catch (error) {
        this.speaking = false;
        resetBundledTts();
        handlers.onError?.(
          error instanceof Error ? error.message : 'Erreur de synthèse vocale',
        );
      }
      return;
    }

    if (!window.speechSynthesis) {
      handlers.onError?.('Synthèse vocale non disponible');
      return;
    }

    const utterance = new SpeechSynthesisUtterance(prepareText(text));
    utterance.rate = options.rate;
    utterance.lang = options.language;
    const voice = pickWebVoice(options.language, options.voiceUri);
    if (voice) utterance.voice = voice;

    utterance.onstart = () => { this.speaking = true; handlers.onStart?.(); };
    utterance.onend = () => { this.speaking = false; if (!webPaused) handlers.onEnd?.(); };
    utterance.onerror = () => { this.speaking = false; handlers.onError?.('Erreur de synthèse vocale'); };
    window.speechSynthesis.speak(utterance);
  }

  async pause(): Promise<void> {
    if (usesBundledVoice()) {
      this.speaking = false;
      await pauseBundledSpeech();
      return;
    }
    if (window.speechSynthesis) {
      webPaused = true;
      window.speechSynthesis.pause();
    }
  }

  async resume(): Promise<void> {
    if (usesBundledVoice()) {
      await resumeBundledSpeech();
      this.speaking = isBundledSpeaking();
      return;
    }
    if (window.speechSynthesis) {
      webPaused = false;
      window.speechSynthesis.resume();
    }
  }

  async stop(): Promise<void> {
    webPaused = false;
    this.speaking = false;
    if (usesBundledVoice()) {
      await stopBundledSpeech();
      return;
    }
    if (window.speechSynthesis) window.speechSynthesis.cancel();
  }

  isSpeaking(): boolean {
    if (usesBundledVoice()) return isBundledSpeaking() || this.speaking;
    return window.speechSynthesis?.speaking ?? false;
  }

  isPaused(): boolean {
    if (usesBundledVoice()) return isBundledPaused();
    return window.speechSynthesis?.paused ?? webPaused;
  }
}

export const ttsEngine = new TextToSpeechEngine();
