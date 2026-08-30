import {
  createContext,
  useCallback,
  useContext,
  useMemo,
  useState,
  type ReactNode,
} from 'react';
import { mockDocuments, mockImportTemplates } from '../data/mockDocuments';
import type { DocumentItem } from '../types/document';

interface LibraryContextValue {
  documents: DocumentItem[];
  importMockDocument: () => DocumentItem;
  updateProgress: (id: string, progress: number) => void;
  getDocument: (id: string) => DocumentItem | undefined;
}

const LibraryContext = createContext<LibraryContextValue | null>(null);

function createId(): string {
  return `doc-${crypto.randomUUID().slice(0, 8)}`;
}

export function LibraryProvider({ children }: { children: ReactNode }) {
  const [documents, setDocuments] = useState<DocumentItem[]>(mockDocuments);

  const importMockDocument = useCallback(() => {
    const template =
      mockImportTemplates[
        Math.floor(Math.random() * mockImportTemplates.length)
      ];
    const newDoc: DocumentItem = {
      ...template,
      id: createId(),
      addedAt: new Date().toISOString(),
      title: `${template.title} ${documents.length + 1}`,
    };

    setDocuments((prev) => [newDoc, ...prev]);
    return newDoc;
  }, [documents.length]);

  const updateProgress = useCallback((id: string, progress: number) => {
    setDocuments((prev) =>
      prev.map((doc) =>
        doc.id === id ? { ...doc, progress: Math.min(100, Math.max(0, progress)) } : doc,
      ),
    );
  }, []);

  const getDocument = useCallback(
    (id: string) => documents.find((doc) => doc.id === id),
    [documents],
  );

  const value = useMemo(
    () => ({ documents, importMockDocument, updateProgress, getDocument }),
    [documents, importMockDocument, updateProgress, getDocument],
  );

  return (
    <LibraryContext.Provider value={value}>{children}</LibraryContext.Provider>
  );
}

export function useLibrary(): LibraryContextValue {
  const context = useContext(LibraryContext);
  if (!context) {
    throw new Error('useLibrary must be used within LibraryProvider');
  }
  return context;
}
