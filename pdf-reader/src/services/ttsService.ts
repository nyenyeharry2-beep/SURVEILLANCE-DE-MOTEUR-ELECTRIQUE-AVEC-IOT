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

let nativeVoiceIndex: number | undefined;
let pausedOnNative = false;
let webPaused = false;

async function resolveNativeVoiceIndex(language: string): Promise<number | undefined> {
  try {
    const { voices } = await TextToSpeech.getSupportedVoices();
    const langPrefix = language.slice(0, 2);
    const match = voices.findIndex((voice) => voice.lang.startsWith(langPrefix));
    return match >= 0 ? match : undefined;
  } catch {
    return undefined;
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

        if (nativeVoiceIndex === undefined) {
          nativeVoiceIndex = await resolveNativeVoiceIndex(options.language);
        }

        await TextToSpeech.speak({
          text,
          lang: options.language,
          rate: Math.max(0.5, Math.min(2, options.rate)),
          pitch: 1,
          volume: 1,
          voice: nativeVoiceIndex,
        });

        this.speaking = false;
        if (!pausedOnNative) {
          handlers.onEnd?.();
        }
      } catch {
        this.speaking = false;
        handlers.onError?.('Erreur de synthèse vocale Android');
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
    const { languages } = await TextToSpeech.getSupportedLanguages();
    return languages.length > 0 ? languages : ['fr-FR', 'en-US'];
  } catch {
    return ['fr-FR', 'en-US'];
  }
}
