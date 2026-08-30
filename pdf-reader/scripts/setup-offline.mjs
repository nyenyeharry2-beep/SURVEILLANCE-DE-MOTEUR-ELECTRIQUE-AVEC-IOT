import { cpSync, existsSync, mkdirSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const publicDir = join(root, 'public');
const tesseractDir = join(publicDir, 'tesseract');
const tessdataDir = join(publicDir, 'tessdata');

const TESSDATA_URL = 'https://tessdata.projectnaptha.com/4.0.0';
const LANGS = ['eng', 'fra'];

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

copyTesseractAssets();
await downloadTessdata();
console.log('Ressources offline prêtes dans public/tesseract et public/tessdata');
