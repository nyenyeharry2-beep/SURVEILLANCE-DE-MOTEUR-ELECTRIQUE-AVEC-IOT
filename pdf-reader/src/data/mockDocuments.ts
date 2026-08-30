import type { DocumentItem, TextSegment } from '../types/document';

export const mockDocumentsSeed: Array<Omit<DocumentItem, 'hasPdfBlob'>> = [
  {
    id: 'doc-demo-1',
    title: 'Introduction à la lecture numérique',
    author: 'Marie Dupont',
    pageCount: 48,
    coverColor: '#4f46e5',
    progress: 35,
    addedAt: '2026-08-28T10:00:00.000Z',
    isScanned: false,
    pdfSource: 'mock',
    segments: [
      { id: 's1', type: 'chapter', content: 'Chapitre 1 — Les fondements', page: 1 },
      { id: 's2', type: 'title', content: 'Pourquoi lire autrement ?', page: 1 },
      {
        id: 's3',
        type: 'paragraph',
        content:
          'La lecture numérique transforme notre rapport au texte. Écouter un document tout en le visualisant permet une compréhension plus profonde.',
        page: 1,
      },
      {
        id: 's4',
        type: 'paragraph',
        content:
          'Cette application permet d\'importer vos PDF, de les parcourir visuellement et de les écouter grâce à une synthèse vocale.',
        page: 2,
      },
    ],
  },
  {
    id: 'doc-demo-2',
    title: 'Guide pratique du PDF',
    author: 'Jean Martin',
    pageCount: 120,
    coverColor: '#0d9488',
    progress: 0,
    addedAt: '2026-08-27T14:30:00.000Z',
    isScanned: false,
    pdfSource: 'mock',
    segments: [
      { id: 's1', type: 'title', content: 'Comprendre le format PDF', page: 1 },
      {
        id: 's2',
        type: 'paragraph',
        content:
          'Le Portable Document Format préserve la mise en page originale sur tous les appareils.',
        page: 1,
      },
    ],
  },
];

export function createFallbackSegments(): TextSegment[] {
  return [
    {
      id: 'seg-1',
      type: 'paragraph',
      content: 'Aucun texte disponible pour ce document.',
      page: 1,
    },
  ];
}
