import * as pdfjsLib from 'pdfjs-dist';
import type { PDFDocumentProxy } from 'pdfjs-dist';

pdfjsLib.GlobalWorkerOptions.workerSrc = new URL(
  'pdfjs-dist/build/pdf.worker.min.mjs',
  import.meta.url,
).toString();

export interface PageText {
  pageNumber: number;
  text: string;
}

export async function loadPdfFromArrayBuffer(data: ArrayBuffer): Promise<PDFDocumentProxy> {
  const loadingTask = pdfjsLib.getDocument({ data });
  return loadingTask.promise;
}

export async function extractTextFromPdf(pdf: PDFDocumentProxy): Promise<PageText[]> {
  const pages: PageText[] = [];

  for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber += 1) {
    const page = await pdf.getPage(pageNumber);
    const textContent = await page.getTextContent();
    const text = textContent.items
      .map((item) => ('str' in item ? item.str : ''))
      .join(' ')
      .replace(/\s+/g, ' ')
      .trim();

    pages.push({ pageNumber, text });
  }

  return pages;
}

export function detectScannedPdf(pages: PageText[]): boolean {
  if (pages.length === 0) {
    return true;
  }

  const totalChars = pages.reduce((sum, page) => sum + page.text.length, 0);
  const averageChars = totalChars / pages.length;
  const sparsePages = pages.filter((page) => page.text.length < 25).length;
  const sparseRatio = sparsePages / pages.length;

  return averageChars < 90 || sparseRatio >= 0.55;
}

export async function renderPageToCanvas(
  pdf: PDFDocumentProxy,
  pageNumber: number,
  canvas: HTMLCanvasElement,
  scale = 1.4,
): Promise<void> {
  const page = await pdf.getPage(pageNumber);
  const viewport = page.getViewport({ scale });
  const context = canvas.getContext('2d');

  if (!context) {
    throw new Error('Impossible d\'initialiser le canvas PDF.');
  }

  canvas.width = viewport.width;
  canvas.height = viewport.height;

  await page.render({
    canvasContext: context,
    canvas,
    viewport,
  }).promise;
}

export { pdfjsLib };
