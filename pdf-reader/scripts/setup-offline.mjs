import { cpSync, createWriteStream, existsSync, mkdirSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { pipeline } from 'node:stream/promises';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const publicDir = join(root, 'public');
const tesseractDir = join(publicDir, 'tesseract');
const tessdataDir = join(publicDir, 'tessdata');
const piperWasmDir = join(publicDir, 'piper/wasm');
const piperModelsDir = join(publicDir, 'piper/models');

const TESSDATA_URL = 'https://tessdata.projectnaptha.com/4.0.0';
const LANGS = ['eng', 'fra'];

const PIPER_VOICE_ID = 'fr_FR-siwis-low';
const PIPER_MODEL_URL =
  'https://huggingface.co/diffusionstudio/piper-voices/resolve/main/fr/fr_FR/siwis/low/fr_FR-siwis-low.onnx';

function copyTesseractAssets() {
  mkdirSync(tesseractDir, { recursive: true });
  cpSync(join(root, 'node_modules/tesseract.js/dist/worker.min.js'), join(tesseractDir, 'worker.min.js'));

  const coreDir = join(root, 'node_modules/tesseract.js-core');
  for (const file of [
    'tesseract-core.wasm.js', 'tesseract-core.wasm',
    'tesseract-core-simd.wasm.js', 'tesseract-core-simd.wasm',
    'tesseract-core-lstm.wasm.js', 'tesseract-core-lstm.wasm',
    'tesseract-core-simd-lstm.wasm.js', 'tesseract-core-simd-lstm.wasm',
    'tesseract-core-relaxedsimd.wasm.js', 'tesseract-core-relaxedsimd.wasm',
    'tesseract-core-relaxedsimd-lstm.wasm.js', 'tesseract-core-relaxedsimd-lstm.wasm',
  ]) {
    cpSync(join(coreDir, file), join(tesseractDir, file));
  }
}

async function downloadTessdata() {
  mkdirSync(tessdataDir, { recursive: true });
  for (const lang of LANGS) {
    const target = join(tessdataDir, `${lang}.traineddata.gz`);
    if (existsSync(target)) continue;
    const response = await fetch(`${TESSDATA_URL}/${lang}.traineddata.gz`);
    if (!response.ok) throw new Error(`Impossible de télécharger ${lang}.traineddata.gz`);
    writeFileSync(target, Buffer.from(await response.arrayBuffer()));
  }
}

function copyPiperAssets() {
  mkdirSync(piperWasmDir, { recursive: true });
  mkdirSync(piperModelsDir, { recursive: true });

  const onnxDir = join(root, 'node_modules/onnxruntime-web/dist');
  cpSync(join(onnxDir, 'ort-wasm-simd-threaded.wasm'), join(piperWasmDir, 'ort-wasm-simd-threaded.wasm'));
  cpSync(join(onnxDir, 'ort-wasm-simd-threaded.mjs'), join(piperWasmDir, 'ort-wasm-simd-threaded.mjs'));

  const piperBuild = join(root, 'node_modules/@diffusionstudio/piper-wasm/build');
  cpSync(join(piperBuild, 'piper_phonemize.wasm'), join(piperWasmDir, 'piper_phonemize.wasm'));
  cpSync(join(piperBuild, 'piper_phonemize.data'), join(piperWasmDir, 'piper_phonemize.data'));
}

async function downloadPiperModel() {
  for (const [url, filename] of [
    [PIPER_MODEL_URL, `${PIPER_VOICE_ID}.onnx`],
    [`${PIPER_MODEL_URL}.json`, `${PIPER_VOICE_ID}.onnx.json`],
  ]) {
    const target = join(piperModelsDir, filename);
    if (existsSync(target)) continue;
    const response = await fetch(url);
    if (!response.ok || !response.body) throw new Error(`Impossible de télécharger ${url}`);
    await pipeline(response.body, createWriteStream(target));
    console.log(`Téléchargé ${filename}`);
  }
}

copyTesseractAssets();
copyPiperAssets();
await downloadTessdata();
await downloadPiperModel();
console.log('Ressources offline prêtes (tesseract, tessdata, piper)');
