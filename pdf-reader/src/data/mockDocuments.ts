import type { DocumentItem } from '../types/document';

export const mockDocuments: DocumentItem[] = [
  {
    id: 'doc-1',
    title: 'Introduction à la lecture numérique',
    author: 'Marie Dupont',
    pageCount: 48,
    coverColor: '#4f46e5',
    progress: 35,
    addedAt: '2026-08-28T10:00:00.000Z',
    segments: [
      {
        id: 's1',
        type: 'chapter',
        content: 'Chapitre 1 — Les fondements',
        page: 1,
      },
      {
        id: 's2',
        type: 'title',
        content: 'Pourquoi lire autrement ?',
        page: 1,
      },
      {
        id: 's3',
        type: 'paragraph',
        content:
          'La lecture numérique transforme notre rapport au texte. Écouter un document tout en le visualisant permet une compréhension plus profonde et un apprentissage multisensoriel.',
        page: 1,
      },
      {
        id: 's4',
        type: 'paragraph',
        content:
          'Cette application vous permet d\'importer vos PDF, de les parcourir visuellement et de les écouter grâce à une synthèse vocale naturelle.',
        page: 2,
      },
      {
        id: 's5',
        type: 'chapter',
        content: 'Chapitre 2 — Utilisation',
        page: 3,
      },
      {
        id: 's6',
        type: 'paragraph',
        content:
          'Importez un fichier PDF depuis votre bibliothèque, ouvrez-le dans le lecteur, puis appuyez sur Lire pour démarrer la lecture audio synchronisée avec le texte.',
        page: 3,
      },
    ],
  },
  {
    id: 'doc-2',
    title: 'Guide pratique du PDF',
    author: 'Jean Martin',
    pageCount: 120,
    coverColor: '#0d9488',
    progress: 0,
    addedAt: '2026-08-27T14:30:00.000Z',
    segments: [
      {
        id: 's1',
        type: 'title',
        content: 'Comprendre le format PDF',
        page: 1,
      },
      {
        id: 's2',
        type: 'paragraph',
        content:
          'Le Portable Document Format est un standard ouvert créé par Adobe. Il préserve la mise en page originale sur tous les appareils.',
        page: 1,
      },
      {
        id: 's3',
        type: 'paragraph',
        content:
          'Les PDF peuvent contenir du texte natif, des images scannées ou un mélange des deux. Notre application détecte automatiquement le type de document.',
        page: 2,
      },
    ],
  },
  {
    id: 'doc-3',
    title: 'Histoire de la synthèse vocale',
    author: 'Sophie Laurent',
    pageCount: 256,
    coverColor: '#db2777',
    progress: 72,
    addedAt: '2026-08-20T09:15:00.000Z',
    segments: [
      {
        id: 's1',
        type: 'chapter',
        content: 'Chapitre 1 — Origines',
        page: 1,
      },
      {
        id: 's2',
        type: 'paragraph',
        content:
          'La synthèse vocale remonte aux années 1930 avec les premiers systèmes électromécaniques capables de produire des sons articulés.',
        page: 1,
      },
      {
        id: 's3',
        type: 'paragraph',
        content:
          'Aujourd\'hui, les moteurs neuronaux produisent des voix quasi humaines, disponibles directement dans le navigateur ou via des services cloud.',
        page: 2,
      },
    ],
  },
];

export const mockImportTemplates: Omit<DocumentItem, 'id' | 'addedAt'>[] = [
  {
    title: 'Mon nouveau document',
    author: 'Importé localement',
    pageCount: 24,
    coverColor: '#ea580c',
    progress: 0,
    segments: [
      {
        id: 's1',
        type: 'title',
        content: 'Document importé',
        page: 1,
      },
      {
        id: 's2',
        type: 'paragraph',
        content:
          'Ceci est un exemple de texte fictif. En Phase 3, le contenu sera extrait automatiquement de votre fichier PDF.',
        page: 1,
      },
    ],
  },
];
