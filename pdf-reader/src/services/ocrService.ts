import { createWorker, type Worker } from 'tesseract.js';
import type { PageText } from './pdfService';
import { renderPageToCanvas } from './pdfService';
import type { PDFDocumentProxy } from 'pdfjs-dist';

let sharedWorker: Worker | null = null;
let workerPromise: Promise<Worker> | null = null;

async function getOcrWorker(): Promise<Worker> {
  if (sharedWorker) {
    return sharedWorker;
  }

  if (!workerPromise) {
    workerPromise = createWorker(['fra', 'eng'], 1, {
      workerPath: '/tesseract/worker.min.js',
      corePath: '/tesseract',
      langPath: '/tessdata',
      workerBlobURL: false,
      cacheMethod: 'none',
      gzip: true,
    });
  }

  sharedWorker = await workerPromise;
  return sharedWorker;
}

export async function ocrPdfPages(
  pdf: PDFDocumentProxy,
  onProgress?: (current: number, total: number, message: string) => void,
): Promise<PageText[]> {
  const worker = await getOcrWorker();
  const pages: PageText[] = [];
  const canvas = document.createElement('canvas');
  const total = pdf.numPages;

  for (let pageNumber = 1; pageNumber <= total; pageNumber += 1) {
    onProgress?.(
      pageNumber,
      total,
      `OCR page ${pageNumber} / ${total}…`,
    );

    await renderPageToCanvas(pdf, pageNumber, canvas, 1.8);
    const result = await worker.recognize(canvas);

    pages.push({
      pageNumber,
      text: result.data.text.replace(/\s+/g, ' ').trim(),
    });
  }

  return pages;
}

export async function terminateOcrWorker(): Promise<void> {
  if (sharedWorker) {
    await sharedWorker.terminate();
    sharedWorker = null;
    workerPromise = null;
  }
}
