import { cpSync, createWriteStream, existsSync, mkdirSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { pipeline } from 'node:stream/promises';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const publicDir = join(root, 'public');
const tesseractDir = join(publicDir, 'tesseract');
const tessdataDir = join(publicDir, 'tessdata');
const piperDir = join(publicDir, 'piper');
const piperWasmDir = join(piperDir, 'wasm');
const piperModelsDir = join(piperDir, 'models');

const TESSDATA_URL = 'https://tessdata.projectnaptha.com/4.0.0';
const LANGS = ['eng', 'fra'];

const PIPER_VOICE_ID = 'fr_FR-siwis-medium';
const PIPER_MODEL_URL =
  'https://huggingface.co/diffusionstudio/piper-voices/resolve/main/fr/fr_FR/siwis/medium/fr_FR-siwis-medium.onnx';
const PIPER_MODEL_JSON_URL = `${PIPER_MODEL_URL}.json`;

function copyTesseractAssets() {
  mkdirSync(tesseractDir, { recursive: true });

  const workerSrc = join(root, 'node_modules/tesseract.js/dist/worker.min.js');
  cpSync(workerSrc, join(tesseractDir, 'worker.min.js'));

  const coreDir = join(root, 'node_modules/tesseract.js-core');
  const coreFiles = [
    'tesseract-core.wasm.js',
    'tesseract-core.wasm',
    'tesseract-core-simd.wasm.js',
    'tesseract-core-simd.wasm',
    'tesseract-core-lstm.wasm.js',
    'tesseract-core-lstm.wasm',
    'tesseract-core-simd-lstm.wasm.js',
    'tesseract-core-simd-lstm.wasm',
    'tesseract-core-relaxedsimd.wasm.js',
    'tesseract-core-relaxedsimd.wasm',
    'tesseract-core-relaxedsimd-lstm.wasm.js',
    'tesseract-core-relaxedsimd-lstm.wasm',
  ];

  for (const file of coreFiles) {
    cpSync(join(coreDir, file), join(tesseractDir, file));
  }
}

async function downloadTessdata() {
  mkdirSync(tessdataDir, { recursive: true });

  for (const lang of LANGS) {
    const target = join(tessdataDir, `${lang}.traineddata.gz`);
    if (existsSync(target)) {
      continue;
    }

    const response = await fetch(`${TESSDATA_URL}/${lang}.traineddata.gz`);
    if (!response.ok) {
      throw new Error(`Impossible de télécharger ${lang}.traineddata.gz`);
    }

    const buffer = Buffer.from(await response.arrayBuffer());
    writeFileSync(target, buffer);
    console.log(`Téléchargé ${lang}.traineddata.gz (${buffer.length} octets)`);
  }
}

function copyPiperWasmAssets() {
  mkdirSync(piperWasmDir, { recursive: true });

  const onnxDir = join(root, 'node_modules/onnxruntime-web/dist');
  const onnxFiles = [
    'ort-wasm-simd-threaded.wasm',
    'ort-wasm-simd-threaded.jsep.wasm',
    'ort-wasm-simd-threaded.jsep.mjs',
    'ort-wasm-simd-threaded.mjs',
  ];

  for (const file of onnxFiles) {
    cpSync(join(onnxDir, file), join(piperWasmDir, file));
  }

  const piperWasmSrc = join(root, 'node_modules/@diffusionstudio/piper-wasm/build');
  cpSync(join(piperWasmSrc, 'piper_phonemize.wasm'), join(piperWasmDir, 'piper_phonemize.wasm'));
  cpSync(join(piperWasmSrc, 'piper_phonemize.data'), join(piperWasmDir, 'piper_phonemize.data'));
}

async function downloadFile(url, target) {
  if (existsSync(target)) {
    return;
  }

  const response = await fetch(url);
  if (!response.ok || !response.body) {
    throw new Error(`Impossible de télécharger ${url}`);
  }

  await pipeline(response.body, createWriteStream(target));
  console.log(`Téléchargé ${target}`);
}

async function downloadPiperModel() {
  mkdirSync(piperModelsDir, { recursive: true });

  await downloadFile(
    PIPER_MODEL_URL,
    join(piperModelsDir, `${PIPER_VOICE_ID}.onnx`),
  );
  await downloadFile(
    PIPER_MODEL_JSON_URL,
    join(piperModelsDir, `${PIPER_VOICE_ID}.onnx.json`),
  );
}

copyTesseractAssets();
copyPiperWasmAssets();
await downloadTessdata();
await downloadPiperModel();
console.log('Ressources offline prêtes dans public/tesseract, public/tessdata et public/piper');
