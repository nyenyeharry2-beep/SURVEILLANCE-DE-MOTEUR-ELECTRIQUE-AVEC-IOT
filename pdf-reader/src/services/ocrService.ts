import Tesseract from 'tesseract.js';
import type { PageText } from './pdfService';
import { renderPageToCanvas } from './pdfService';
import type { PDFDocumentProxy } from 'pdfjs-dist';

export async function ocrPdfPages(
  pdf: PDFDocumentProxy,
  onProgress?: (current: number, total: number, message: string) => void,
): Promise<PageText[]> {
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

    const result = await Tesseract.recognize(canvas, 'fra+eng', {
      logger: () => undefined,
    });

    pages.push({
      pageNumber,
      text: result.data.text.replace(/\s+/g, ' ').trim(),
    });
  }

  return pages;
}
