import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';
import { mockDocumentsSeed } from '../data/mockDocuments';
import {
  addHistory,
  deleteDocument,
  getAllDocuments,
  getReadingState,
  saveDocument,
  saveReadingState,
} from '../services/db';
import { importPdfFile, mapStoredDocument } from '../services/documentImportService';
import type { DocumentItem, ImportProgress, ReadingState } from '../types/document';

interface LibraryContextValue {
  documents: DocumentItem[];
  loading: boolean;
  importPdf: (file: File, onProgress?: (progress: ImportProgress) => void) => Promise<DocumentItem>;
  updateProgress: (id: string, progress: number, segmentIndex?: number, page?: number) => Promise<void>;
  getDocument: (id: string) => DocumentItem | undefined;
  removeDocument: (id: string) => Promise<void>;
  refreshDocuments: () => Promise<void>;
  recordOpen: (document: DocumentItem) => Promise<void>;
  getSavedReadingState: (documentId: string) => Promise<ReadingState | undefined>;
}

const LibraryContext = createContext<LibraryContextValue | null>(null);

async function seedDemoDocumentsIfEmpty(): Promise<void> {
  const existing = await getAllDocuments();
  if (existing.length > 0) {
    return;
  }

  for (const demo of mockDocumentsSeed) {
    await saveDocument({
      ...demo,
      pdfBlob: undefined,
    });
  }
}

export function LibraryProvider({ children }: { children: ReactNode }) {
  const [documents, setDocuments] = useState<DocumentItem[]>([]);
  const [loading, setLoading] = useState(true);

  const refreshDocuments = useCallback(async () => {
    const rows = await getAllDocuments();
    setDocuments(rows.map(mapStoredDocument));
  }, []);

  useEffect(() => {
    seedDemoDocumentsIfEmpty()
      .then(refreshDocuments)
      .finally(() => setLoading(false));
  }, [refreshDocuments]);

  const importPdf = useCallback(
    async (file: File, onProgress?: (progress: ImportProgress) => void) => {
      const imported = await importPdfFile(file, onProgress);
      await refreshDocuments();
      return imported;
    },
    [refreshDocuments],
  );

  const updateProgress = useCallback(
    async (id: string, progress: number, segmentIndex = 0, page = 1) => {
      const rows = await getAllDocuments();
      const row = rows.find((item) => item.id === id);
      if (!row) {
        return;
      }

      const next = {
        ...row,
        progress: Math.min(100, Math.max(0, progress)),
      };

      await saveDocument(next);
      await saveReadingState({
        documentId: id,
        segmentIndex,
        page,
        updatedAt: new Date().toISOString(),
      });

      setDocuments((prev) =>
        prev.map((doc) =>
          doc.id === id ? { ...doc, progress: next.progress } : doc,
        ),
      );
    },
    [],
  );

  const getDocument = useCallback(
    (id: string) => documents.find((doc) => doc.id === id),
    [documents],
  );

  const removeDocument = useCallback(
    async (id: string) => {
      await deleteDocument(id);
      await refreshDocuments();
    },
    [refreshDocuments],
  );

  const recordOpen = useCallback(async (document: DocumentItem) => {
    await addHistory({
      id: `hist-${crypto.randomUUID().slice(0, 8)}`,
      documentId: document.id,
      documentTitle: document.title,
      action: 'open',
      createdAt: new Date().toISOString(),
    });
  }, []);

  const getSavedReadingState = useCallback(async (documentId: string) => {
    return getReadingState(documentId);
  }, []);

  const value = useMemo(
    () => ({
      documents,
      loading,
      importPdf,
      updateProgress,
      getDocument,
      removeDocument,
      refreshDocuments,
      recordOpen,
      getSavedReadingState,
    }),
    [
      documents,
      loading,
      importPdf,
      updateProgress,
      getDocument,
      removeDocument,
      refreshDocuments,
      recordOpen,
      getSavedReadingState,
    ],
  );

  return <LibraryContext.Provider value={value}>{children}</LibraryContext.Provider>;
}

export function useLibrary(): LibraryContextValue {
  const context = useContext(LibraryContext);
  if (!context) {
    throw new Error('useLibrary must be used within LibraryProvider');
  }
  return context;
}
