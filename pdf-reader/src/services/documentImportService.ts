import type { DocumentItem, ImportProgress, StoredDocumentRow } from '../types/document';
import {
  addHistory,
  getDocumentById,
  saveDocument,
} from './db';
import {
  detectScannedPdf,
  extractTextFromPdf,
  loadPdfFromArrayBuffer,
} from './pdfService';
import { ocrPdfPages } from './ocrService';
import { processPagesToSegments } from './textProcessing';

const COVER_COLORS = ['#4f46e5', '#0d9488', '#db2777', '#ea580c', '#2563eb', '#7c3aed'];

function pickCoverColor(): string {
  return COVER_COLORS[Math.floor(Math.random() * COVER_COLORS.length)];
}

function deriveTitle(fileName: string): string {
  return fileName.replace(/\.pdf$/i, '').replace(/[-_]+/g, ' ').trim() || 'Document PDF';
}

function toDocumentItem(row: StoredDocumentRow): DocumentItem {
  return {
    id: row.id,
    title: row.title,
    author: row.author,
    pageCount: row.pageCount,
    coverColor: row.coverColor,
    progress: row.progress,
    addedAt: row.addedAt,
    segments: row.segments,
    isScanned: row.isScanned,
    pdfSource: row.pdfSource,
    fileName: row.fileName,
    hasPdfBlob: Boolean(row.pdfBlob),
  };
}

export function mapStoredDocument(row: StoredDocumentRow): DocumentItem {
  return toDocumentItem(row);
}

export async function importPdfFile(
  file: File,
  onProgress?: (progress: ImportProgress) => void,
): Promise<DocumentItem> {
  onProgress?.({
    stage: 'loading',
    message: 'Chargement du PDF…',
    progress: 5,
  });

  const buffer = await file.arrayBuffer();
  const pdf = await loadPdfFromArrayBuffer(buffer);

  onProgress?.({
    stage: 'extracting',
    message: 'Extraction du texte…',
    progress: 20,
  });

  let pages = await extractTextFromPdf(pdf);
  const isScanned = detectScannedPdf(pages);

  onProgress?.({
    stage: 'detecting',
    message: isScanned ? 'PDF scanné détecté — OCR en cours…' : 'PDF texte détecté',
    progress: isScanned ? 35 : 55,
  });

  if (isScanned) {
    pages = await ocrPdfPages(pdf, (current, total, message) => {
      onProgress?.({
        stage: 'ocr',
        message,
        progress: 35 + Math.round((current / total) * 40),
      });
    });
  }

  onProgress?.({
    stage: 'processing',
    message: 'Découpage en segments…',
    progress: 85,
  });

  const segments = processPagesToSegments(pages);
  const id = `doc-${crypto.randomUUID().slice(0, 8)}`;
  const stored: StoredDocumentRow = {
    id,
    title: deriveTitle(file.name),
    author: 'Importé localement',
    pageCount: pdf.numPages,
    coverColor: pickCoverColor(),
    progress: 0,
    addedAt: new Date().toISOString(),
    segments,
    isScanned,
    pdfSource: isScanned ? 'ocr' : 'native',
    fileName: file.name,
    pdfBlob: file,
  };

  onProgress?.({
    stage: 'saving',
    message: 'Enregistrement…',
    progress: 95,
  });

  await saveDocument(stored);
  await addHistory({
    id: `hist-${crypto.randomUUID().slice(0, 8)}`,
    documentId: id,
    documentTitle: stored.title,
    action: 'import',
    createdAt: new Date().toISOString(),
  });

  onProgress?.({
    stage: 'done',
    message: 'Import terminé',
    progress: 100,
  });

  return toDocumentItem(stored);
}

export async function loadPdfBlob(documentId: string): Promise<Blob | null> {
  const row = await getDocumentById(documentId);
  return row?.pdfBlob ?? null;
}
