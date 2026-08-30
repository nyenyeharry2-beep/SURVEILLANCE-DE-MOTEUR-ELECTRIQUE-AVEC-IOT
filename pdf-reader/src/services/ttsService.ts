import { Capacitor } from '@capacitor/core';
import {
  BUNDLED_FRENCH_VOICES,
  DEFAULT_PIPER_VOICE,
  getActivePiperVoiceId,
  getActivePiperVoiceLabel,
  initializePiperTts,
  isPiperPaused,
  isPiperSpeaking,
  isPiperTtsAvailable,
  pausePiperSpeech,
  resetPiperTtsInit,
  resumePiperSpeech,
  speakWithPiper,
  stopPiperSpeech,
  type PiperInitProgress,
} from './piperTtsService';

export type { PiperInitProgress };

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

export { BUNDLED_FRENCH_VOICES, DEFAULT_PIPER_VOICE };

let webPaused = false;

export function isNativeTtsAvailable(): boolean {
  return Capacitor.isNativePlatform() && isPiperTtsAvailable();
}

export function usesBundledVoice(): boolean {
  return Capacitor.isNativePlatform() || isPiperTtsAvailable();
}

export function getAvailableVoices(): SpeechSynthesisVoice[] {
  if (typeof window === 'undefined' || !window.speechSynthesis) {
    return [];
  }
  return window.speechSynthesis.getVoices();
}

function resolvePiperVoiceId(voiceUri: string | null) {
  if (voiceUri && BUNDLED_FRENCH_VOICES.some((voice) => voice.id === voiceUri)) {
    return voiceUri;
  }
  return DEFAULT_PIPER_VOICE;
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

export async function initializeNativeTts(
  preferredLanguage = 'fr-FR',
  onProgress?: (progress: PiperInitProgress) => void,
): Promise<void> {
  if (!usesBundledVoice()) {
    return;
  }

  void preferredLanguage;
  await initializePiperTts(DEFAULT_PIPER_VOICE, onProgress);
}

export function getResolvedNativeLanguage(): string {
  return 'fr-FR';
}

export function getResolvedNativeVoiceName(): string {
  return getActivePiperVoiceLabel();
}

export async function openNativeTtsInstall(): Promise<void> {
  return;
}

export async function getNativeVoicesForLanguage(language: string) {
  void language;
  return BUNDLED_FRENCH_VOICES.map((voice) => ({
    index: 0,
    voiceURI: voice.id,
    name: voice.label,
    lang: 'fr-FR',
    localService: true,
  }));
}

export async function getNativeLanguages(): Promise<string[]> {
  return ['fr-FR'];
}

export class TextToSpeechEngine {
  private speaking = false;

  async speak(
    text: string,
    options: SpeakOptions,
    handlers: SpeakHandlers = {},
  ): Promise<void> {
    await this.stop();
    webPaused = false;

    if (!text.trim()) {
      handlers.onEnd?.();
      return;
    }

    if (usesBundledVoice()) {
      try {
        const voiceId = resolvePiperVoiceId(options.voiceUri);
        await initializePiperTts(voiceId);
        handlers.onStart?.();
        this.speaking = true;

        const units = splitIntoSpeechUnits(text);
        for (const unit of units) {
          if (isPiperPaused() || !this.speaking) {
            break;
          }
          await speakWithPiper(unit, options.rate, voiceId);
        }

        this.speaking = false;
        if (!isPiperPaused()) {
          handlers.onEnd?.();
        }
      } catch (error) {
        this.speaking = false;
        resetPiperTtsInit();
        handlers.onError?.(
          error instanceof Error ? error.message : 'Erreur de synthèse vocale',
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
    if (usesBundledVoice()) {
      this.speaking = false;
      await pausePiperSpeech();
      return;
    }

    if (window.speechSynthesis) {
      webPaused = true;
      window.speechSynthesis.pause();
    }
  }

  async resume(): Promise<void> {
    if (usesBundledVoice()) {
      await resumePiperSpeech();
      this.speaking = isPiperSpeaking();
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
      await stopPiperSpeech();
      return;
    }

    if (window.speechSynthesis) {
      window.speechSynthesis.cancel();
    }
  }

  isSpeaking(): boolean {
    if (usesBundledVoice()) {
      return isPiperSpeaking() || this.speaking;
    }
    return window.speechSynthesis?.speaking ?? false;
  }

  isPaused(): boolean {
    if (usesBundledVoice()) {
      return isPiperPaused();
    }
    return window.speechSynthesis?.paused ?? webPaused;
  }
}

export const ttsEngine = new TextToSpeechEngine();

export function getActiveVoiceId(): string {
  return getActivePiperVoiceId();
}
