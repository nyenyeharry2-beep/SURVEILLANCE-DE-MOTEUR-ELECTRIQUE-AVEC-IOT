import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';
import { Guest } from './types';

export function guestsToCsv(guests: Guest[]): string {
  const header = 'Nom,Téléphone WhatsApp,Table/Zone,Places,Statut envoi,Date envoi';
  const rows = guests.map((g) =>
    [
      `"${g.fullName.replace(/"/g, '""')}"`,
      g.whatsapp,
      `"${(g.tableZone || '').replace(/"/g, '""')}"`,
      g.seats,
      g.sent ? 'Envoyé' : 'Non envoyé',
      g.sentAt || '',
    ].join(','),
  );
  return [header, ...rows].join('\n');
}

export async function exportGuestsCsv(guests: Guest[]): Promise<void> {
  const csv = guestsToCsv(guests);
  const path = `${FileSystem.cacheDirectory}invites_tables.csv`;
  await FileSystem.writeAsStringAsync(path, csv, { encoding: FileSystem.EncodingType.UTF8 });
  if (await Sharing.isAvailableAsync()) {
    await Sharing.shareAsync(path, { mimeType: 'text/csv', dialogTitle: 'Exporter la liste des invités' });
  }
}

export function filterGuests(
  guests: Guest[],
  search: string,
  tableFilter: string,
): Guest[] {
  const q = search.trim().toLowerCase();
  return guests.filter((g) => {
    const matchesSearch =
      !q ||
      g.fullName.toLowerCase().includes(q) ||
      g.whatsapp.includes(q);
    const matchesTable =
      !tableFilter || tableFilter === 'Toutes' || g.tableZone === tableFilter;
    return matchesSearch && matchesTable;
  });
}

export function getUniqueTables(guests: Guest[]): string[] {
  const tables = new Set(guests.map((g) => g.tableZone).filter(Boolean));
  return ['Toutes', ...Array.from(tables).sort()];
}
