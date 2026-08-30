import * as ort from 'onnxruntime-web/wasm';
// @ts-expect-error pas de types pour le module Emscripten
import createPiperPhonemize from '@diffusionstudio/piper-wasm/build/piper_phonemize.js';
import { resolveRootAsset, resolveRootAssetDir } from '../utils/assetUrl';

export const BUNDLED_VOICE_ID = 'fr_FR-siwis-low';
export const BUNDLED_VOICE_LABEL = 'Français — Siwis (intégrée)';

export interface BundledTtsProgress {
  message: string;
  percent?: number;
}

interface PiperModelConfig {
  audio: { sample_rate: number };
  inference: { noise_scale: number; length_scale: number; noise_w: number };
  espeak: { voice: string };
  speaker_id_map: Record<string, number>;
}

let initPromise: Promise<void> | null = null;
let ortSession: ort.InferenceSession | null = null;
let modelConfig: PiperModelConfig | null = null;
let currentAudio: HTMLAudioElement | null = null;
let currentObjectUrl: string | null = null;
let speaking = false;
let paused = false;
let stopRequested = false;

function pcmToWav(buffer: Float32Array, sampleRate: number): ArrayBuffer {
  const headerLength = 44;
  const view = new DataView(new ArrayBuffer(buffer.length * 2 + headerLength));
  view.setUint32(0, 1179011410, true);
  view.setUint32(4, view.buffer.byteLength - 8, true);
  view.setUint32(8, 1163280727, true);
  view.setUint32(12, 544501094, true);
  view.setUint32(16, 16, true);
  view.setUint16(20, 1, true);
  view.setUint16(22, 1, true);
  view.setUint32(24, sampleRate, true);
  view.setUint32(28, sampleRate * 2, true);
  view.setUint16(32, 2, true);
  view.setUint16(34, 16, true);
  view.setUint32(36, 1635017060, true);
  view.setUint32(40, buffer.length * 2, true);

  let offset = headerLength;
  for (let index = 0; index < buffer.length; index += 1) {
    const sample = buffer[index];
    view.setInt16(
      offset,
      sample >= 1 ? 32767 : sample <= -1 ? -32768 : (sample * 32768) | 0,
      true,
    );
    offset += 2;
  }

  return view.buffer;
}

async function phonemize(text: string, espeakVoice: string): Promise<number[]> {
  return new Promise(async (resolve, reject) => {
    try {
      const factory = await createPiperPhonemize({
        print: (data: string) => {
          resolve(JSON.parse(data).phoneme_ids as number[]);
        },
        printErr: (message: string) => {
          reject(new Error(message));
        },
        locateFile: (url: string) => {
          if (url.endsWith('.wasm')) {
            return resolveRootAsset('piper/wasm/piper_phonemize.wasm');
          }
          if (url.endsWith('.data')) {
            return resolveRootAsset('piper/wasm/piper_phonemize.data');
          }
          return url;
        },
      });

      factory.callMain([
        '-l',
        espeakVoice,
        '--input',
        JSON.stringify([{ text: text.trim() }]),
        '--espeak_data',
        '/espeak-ng-data',
      ]);
    } catch (error) {
      reject(error);
    }
  });
}

function prepareText(text: string): string {
  return text
    .replace(/\r\n/g, '\n')
    .replace(/\n{2,}/g, '. ')
    .replace(/\n/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function splitSentences(text: string, maxLength = 360): string[] {
  const prepared = prepareText(text);
  if (!prepared) {
    return [];
  }

  const sentences = prepared.split(/(?<=[.!?…])\s+/).filter(Boolean);
  if (sentences.length === 0) {
    return prepared.length <= maxLength ? [prepared] : [prepared.slice(0, maxLength)];
  }

  const units: string[] = [];
  let buffer = '';

  for (const sentence of sentences) {
    const next = buffer ? `${buffer} ${sentence}` : sentence;
    if (next.length <= maxLength) {
      buffer = next;
    } else {
      if (buffer) {
        units.push(buffer);
      }
      buffer = sentence.length <= maxLength ? sentence : sentence.slice(0, maxLength);
    }
  }

  if (buffer) {
    units.push(buffer);
  }

  return units;
}

async function synthesizeChunk(text: string): Promise<Blob> {
  if (!ortSession || !modelConfig) {
    throw new Error('Voix intégrée non initialisée.');
  }

  const phonemeIds = await phonemize(text, modelConfig.espeak.voice);

  const feeds: Record<string, ort.Tensor> = {
    input: new ort.Tensor('int64', phonemeIds, [1, phonemeIds.length]),
    input_lengths: new ort.Tensor('int64', [phonemeIds.length]),
    scales: new ort.Tensor('float32', new Float32Array([
      modelConfig.inference.noise_scale,
      modelConfig.inference.length_scale,
      modelConfig.inference.noise_w,
    ])),
  };

  if (Object.keys(modelConfig.speaker_id_map).length > 0) {
    feeds.sid = new ort.Tensor('int64', [0]);
  }

  const result = await ortSession.run(feeds);
  const pcm = result.output.data as Float32Array;
  const wavBuffer = pcmToWav(pcm, modelConfig.audio.sample_rate);
  return new Blob([wavBuffer], { type: 'audio/x-wav' });
}

function stopCurrentAudio(): void {
  if (currentAudio) {
    currentAudio.pause();
    currentAudio.currentTime = 0;
    currentAudio = null;
  }
  if (currentObjectUrl) {
    URL.revokeObjectURL(currentObjectUrl);
    currentObjectUrl = null;
  }
  speaking = false;
}

async function playWav(wav: Blob, rate: number): Promise<void> {
  stopCurrentAudio();
  stopRequested = false;
  currentObjectUrl = URL.createObjectURL(wav);
  currentAudio = new Audio(currentObjectUrl);
  currentAudio.playbackRate = Math.max(0.5, Math.min(1.4, rate));
  speaking = true;
  paused = false;

  await new Promise<void>((resolve, reject) => {
    if (!currentAudio) {
      resolve();
      return;
    }
    const audio = currentAudio;
    audio.onended = () => {
      speaking = false;
      resolve();
    };
    audio.onerror = () => {
      speaking = false;
      reject(new Error('Erreur de lecture audio.'));
    };
    void audio.play().catch(reject);
  });
}

export function isBundledTtsAvailable(): boolean {
  return typeof window !== 'undefined';
}

export async function initializeBundledTts(
  onProgress?: (progress: BundledTtsProgress) => void,
): Promise<void> {
  if (initPromise) {
    return initPromise;
  }

  initPromise = (async () => {
    onProgress?.({ message: 'Chargement de la voix intégrée…', percent: 10 });

    ort.env.wasm.wasmPaths = resolveRootAssetDir('piper/wasm');
    ort.env.wasm.numThreads = 1;
    ort.env.wasm.simd = true;

    const modelUrl = resolveRootAsset(`piper/models/${BUNDLED_VOICE_ID}.onnx`);
    const configUrl = resolveRootAsset(`piper/models/${BUNDLED_VOICE_ID}.onnx.json`);

    onProgress?.({ message: 'Préparation du modèle vocal…', percent: 35 });

    const [modelResponse, configResponse] = await Promise.all([
      fetch(modelUrl),
      fetch(configUrl),
    ]);

    if (!modelResponse.ok || !configResponse.ok) {
      throw new Error('Voix intégrée introuvable dans l’application.');
    }

    modelConfig = (await configResponse.json()) as PiperModelConfig;

    onProgress?.({ message: 'Initialisation de la voix…', percent: 70 });

    const modelBuffer = await modelResponse.arrayBuffer();
    ortSession = await ort.InferenceSession.create(modelBuffer, {
      executionProviders: ['wasm'],
    });

    onProgress?.({ message: 'Voix française prête.', percent: 100 });
  })();

  try {
    await initPromise;
  } catch (error) {
    initPromise = null;
    ortSession = null;
    modelConfig = null;
    throw error;
  }
}

export async function speakWithBundledVoice(
  text: string,
  rate: number,
  onProgress?: (progress: BundledTtsProgress) => void,
): Promise<void> {
  if (!ortSession) {
    await initializeBundledTts(onProgress);
  }

  const units = splitSentences(text);
  for (const unit of units) {
    if (stopRequested || paused) {
      return;
    }
    const wav = await synthesizeChunk(unit);
    if (stopRequested) {
      return;
    }
    await playWav(wav, rate);
  }
}

export async function stopBundledSpeech(): Promise<void> {
  stopRequested = true;
  stopCurrentAudio();
  paused = false;
}

export async function pauseBundledSpeech(): Promise<void> {
  if (currentAudio && speaking) {
    currentAudio.pause();
    paused = true;
    speaking = false;
  }
}

export async function resumeBundledSpeech(): Promise<void> {
  if (currentAudio && paused) {
    paused = false;
    speaking = true;
    stopRequested = false;
    await currentAudio.play();
  }
}

export function isBundledSpeaking(): boolean {
  return speaking;
}

export function isBundledPaused(): boolean {
  return paused;
}

export function resetBundledTts(): void {
  initPromise = null;
  ortSession = null;
  modelConfig = null;
}

export function getBundledVoiceLabel(): string {
  return BUNDLED_VOICE_LABEL;
}
