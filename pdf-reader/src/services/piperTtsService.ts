import { HF_BASE, PATH_MAP, TtsSession, stored, type VoiceId } from '@realtimex/piper-tts-web';

export const DEFAULT_PIPER_VOICE: VoiceId = 'fr_FR-siwis-medium';

export const BUNDLED_FRENCH_VOICES: Array<{ id: VoiceId; label: string }> = [
  { id: 'fr_FR-siwis-medium', label: 'Français — Siwis (femme, intégrée)' },
];

export interface PiperInitProgress {
  stage: 'wasm' | 'model' | 'ready';
  message: string;
  percent?: number;
}

let session: TtsSession | null = null;
let initPromise: Promise<void> | null = null;
let currentVoiceId: VoiceId = DEFAULT_PIPER_VOICE;
let currentAudio: HTMLAudioElement | null = null;
let currentObjectUrl: string | null = null;
let speaking = false;
let paused = false;
let stopRequested = false;

async function writeOpfsBlob(url: string, blob: Blob): Promise<void> {
  const root = await navigator.storage.getDirectory();
  const dir = await root.getDirectoryHandle('piper', { create: true });
  const path = url.split('/').at(-1);
  if (!path) {
    return;
  }
  const file = await dir.getFileHandle(path, { create: true });
  const writable = await file.createWritable();
  await writable.write(blob);
  await writable.close();
}

async function seedBundledVoice(
  voiceId: VoiceId,
  onProgress?: (progress: PiperInitProgress) => void,
): Promise<void> {
  const alreadyStored = await stored();
  if (alreadyStored.includes(voiceId)) {
    return;
  }

  const path = PATH_MAP[voiceId];
  if (!path) {
    throw new Error(`Voix ${voiceId} introuvable.`);
  }

  const modelUrl = `${HF_BASE}/${path}`;
  const jsonUrl = `${modelUrl}.json`;

  onProgress?.({
    stage: 'model',
    message: 'Chargement de la voix française intégrée…',
    percent: 10,
  });

  const [modelResponse, jsonResponse] = await Promise.all([
    fetch(`./piper/models/${voiceId}.onnx`),
    fetch(`./piper/models/${voiceId}.onnx.json`),
  ]);

  if (!modelResponse.ok || !jsonResponse.ok) {
    throw new Error('Fichiers vocaux intégrés introuvables dans l’application.');
  }

  const modelBlob = await modelResponse.blob();
  onProgress?.({
    stage: 'model',
    message: 'Installation de la voix dans l’app…',
    percent: 55,
  });

  await writeOpfsBlob(modelUrl, modelBlob);
  await writeOpfsBlob(jsonUrl, await jsonResponse.blob());

  onProgress?.({
    stage: 'model',
    message: 'Voix française prête.',
    percent: 90,
  });
}

function revokeCurrentAudioUrl(): void {
  if (currentObjectUrl) {
    URL.revokeObjectURL(currentObjectUrl);
    currentObjectUrl = null;
  }
}

function stopCurrentAudio(): void {
  if (currentAudio) {
    currentAudio.pause();
    currentAudio.currentTime = 0;
    currentAudio = null;
  }
  revokeCurrentAudioUrl();
  speaking = false;
}

async function playBlob(wav: Blob, rate: number): Promise<void> {
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

export function isPiperTtsAvailable(): boolean {
  return typeof window !== 'undefined' && 'storage' in navigator;
}

export async function initializePiperTts(
  voiceId: VoiceId = DEFAULT_PIPER_VOICE,
  onProgress?: (progress: PiperInitProgress) => void,
): Promise<void> {
  if (!isPiperTtsAvailable()) {
    throw new Error('Stockage vocal indisponible sur cet appareil.');
  }

  if (initPromise && currentVoiceId === voiceId) {
    return initPromise;
  }

  if (session && currentVoiceId !== voiceId) {
    session = null;
    initPromise = null;
  }

  currentVoiceId = voiceId;

  initPromise = (async () => {
    onProgress?.({
      stage: 'wasm',
      message: 'Préparation du moteur vocal intégré…',
      percent: 5,
    });

    await seedBundledVoice(voiceId, onProgress);

    onProgress?.({
      stage: 'wasm',
      message: 'Initialisation de la voix…',
      percent: 92,
    });

    session = await TtsSession.create({
      voiceId,
      allowLocalModels: true,
      fallbackStrategy: 'local',
      wasmPaths: {
        onnxWasm: './piper/wasm/',
        piperWasm: './piper/wasm/piper_phonemize.wasm',
        piperData: './piper/wasm/piper_phonemize.data',
      },
      progress: (progress) => {
        if (progress.total > 0) {
          onProgress?.({
            stage: 'model',
            message: 'Chargement du modèle vocal…',
            percent: 90 + Math.round((progress.loaded / progress.total) * 8),
          });
        }
      },
    });

    onProgress?.({
      stage: 'ready',
      message: 'Voix française intégrée prête.',
      percent: 100,
    });
  })();

  try {
    await initPromise;
  } catch (error) {
    initPromise = null;
    session = null;
    throw error;
  }
}

export function getActivePiperVoiceId(): VoiceId {
  return currentVoiceId;
}

export function getActivePiperVoiceLabel(): string {
  return (
    BUNDLED_FRENCH_VOICES.find((voice) => voice.id === currentVoiceId)?.label ??
    'Français intégré'
  );
}

export async function speakWithPiper(
  text: string,
  rate: number,
  voiceId: VoiceId = currentVoiceId,
): Promise<void> {
  if (!session || currentVoiceId !== voiceId) {
    await initializePiperTts(voiceId);
  }

  if (!session) {
    throw new Error('Moteur vocal non initialisé.');
  }

  const wav = await session.predict(text.trim());
  if (stopRequested) {
    return;
  }

  await playBlob(wav, rate);
}

export async function stopPiperSpeech(): Promise<void> {
  stopRequested = true;
  stopCurrentAudio();
  paused = false;
}

export async function pausePiperSpeech(): Promise<void> {
  if (currentAudio && speaking) {
    currentAudio.pause();
    paused = true;
    speaking = false;
  }
}

export async function resumePiperSpeech(): Promise<void> {
  if (currentAudio && paused) {
    paused = false;
    speaking = true;
    await currentAudio.play();
  }
}

export function isPiperSpeaking(): boolean {
  return speaking;
}

export function isPiperPaused(): boolean {
  return paused;
}

export function resetPiperTtsInit(): void {
  initPromise = null;
  session = null;
}
