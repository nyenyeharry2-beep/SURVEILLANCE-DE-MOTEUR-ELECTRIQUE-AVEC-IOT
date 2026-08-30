export interface TextSegment {
  id: string;
  type: 'title' | 'paragraph' | 'chapter';
  content: string;
  page: number;
}

export type PdfSource = 'native' | 'ocr' | 'mock';

export interface DocumentItem {
  id: string;
  title: string;
  author: string;
  pageCount: number;
  coverColor: string;
  progress: number;
  addedAt: string;
  segments: TextSegment[];
  isScanned: boolean;
  pdfSource: PdfSource;
  fileName?: string;
  hasPdfBlob: boolean;
}

export interface ReadingState {
  documentId: string;
  segmentIndex: number;
  page: number;
  updatedAt: string;
}

export interface UserPreferences {
  id: 'default';
  speed: number;
  voiceUri: string | null;
  language: string;
  autoPlay: boolean;
}

export interface HistoryEntry {
  id: string;
  documentId: string;
  documentTitle: string;
  action: 'open' | 'read' | 'import';
  createdAt: string;
}

export interface UserProfile {
  id: string;
  name: string;
  email: string;
  passwordHash: string;
  createdAt: string;
}

export type PlaybackState = 'idle' | 'playing' | 'paused';

export interface AiMessage {
  id: string;
  role: 'user' | 'assistant';
  content: string;
  createdAt: string;
}

export interface StoredDocumentRow {
  id: string;
  title: string;
  author: string;
  pageCount: number;
  coverColor: string;
  progress: number;
  addedAt: string;
  segments: TextSegment[];
  isScanned: boolean;
  pdfSource: PdfSource;
  fileName?: string;
  pdfBlob?: Blob;
}

export interface ImportProgress {
  stage: 'loading' | 'extracting' | 'detecting' | 'ocr' | 'processing' | 'saving' | 'done' | 'error';
  message: string;
  progress: number;
}
