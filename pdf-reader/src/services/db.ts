import Dexie, { type Table } from 'dexie';
import type {
  HistoryEntry,
  ReadingState,
  StoredDocumentRow,
  UserPreferences,
  UserProfile,
} from '../types/document';

const DEFAULT_PREFERENCES: UserPreferences = {
  id: 'default',
  speed: 1,
  voiceUri: null,
  language: 'fr-FR',
  autoPlay: false,
};

class LumenDatabase extends Dexie {
  documents!: Table<StoredDocumentRow, string>;
  readingStates!: Table<ReadingState, string>;
  preferences!: Table<UserPreferences, string>;
  users!: Table<UserProfile, string>;
  history!: Table<HistoryEntry, string>;

  constructor() {
    super('LumenReaderDB');
    this.version(1).stores({
      documents: 'id, addedAt, title',
      readingStates: 'documentId',
      preferences: 'id',
      users: 'id, email',
      history: 'id, documentId, createdAt',
    });
  }
}

export const db = new LumenDatabase();

export async function getPreferences(): Promise<UserPreferences> {
  const existing = await db.preferences.get('default');
  if (existing) {
    return existing;
  }
  await db.preferences.put(DEFAULT_PREFERENCES);
  return DEFAULT_PREFERENCES;
}

export async function savePreferences(prefs: UserPreferences): Promise<void> {
  await db.preferences.put(prefs);
}

export async function getAllDocuments(): Promise<StoredDocumentRow[]> {
  return db.documents.orderBy('addedAt').reverse().toArray();
}

export async function getDocumentById(id: string): Promise<StoredDocumentRow | undefined> {
  return db.documents.get(id);
}

export async function saveDocument(doc: StoredDocumentRow): Promise<void> {
  await db.documents.put(doc);
}

export async function deleteDocument(id: string): Promise<void> {
  await db.documents.delete(id);
  await db.readingStates.delete(id);
}

export async function getReadingState(documentId: string): Promise<ReadingState | undefined> {
  return db.readingStates.get(documentId);
}

export async function saveReadingState(state: ReadingState): Promise<void> {
  await db.readingStates.put(state);
}

export async function addHistory(entry: HistoryEntry): Promise<void> {
  await db.history.put(entry);
  const all = await db.history.orderBy('createdAt').reverse().toArray();
  if (all.length > 100) {
    await db.history.bulkDelete(all.slice(100).map((item) => item.id));
  }
}

export async function getHistory(limit = 20): Promise<HistoryEntry[]> {
  return db.history.orderBy('createdAt').reverse().limit(limit).toArray();
}

export async function getUserByEmail(email: string): Promise<UserProfile | undefined> {
  return db.users.where('email').equals(email.toLowerCase()).first();
}

export async function saveUser(user: UserProfile): Promise<void> {
  await db.users.put(user);
}

export async function getUserById(id: string): Promise<UserProfile | undefined> {
  return db.users.get(id);
}
