import { useCallback, useEffect, useState } from 'react';
import {
  Alert,
  FlatList,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { useRouter, useFocusEffect } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { GuestListItem } from '../components/GuestListItem';
import { deleteGuest, loadEventConfig, loadGuests, upsertGuest } from '../lib/storage';
import { exportGuestsCsv, filterGuests, getUniqueTables } from '../lib/export';
import { sendWhatsAppInvitation } from '../lib/whatsapp';
import { Guest } from '../lib/types';

export default function DashboardScreen() {
  const router = useRouter();
  const [guests, setGuests] = useState<Guest[]>([]);
  const [search, setSearch] = useState('');
  const [tableFilter, setTableFilter] = useState('Toutes');

  const refresh = useCallback(async () => {
    setGuests(await loadGuests());
  }, []);

  useFocusEffect(
    useCallback(() => {
      refresh();
    }, [refresh]),
  );

  const filtered = filterGuests(guests, search, tableFilter);
  const tables = getUniqueTables(guests);
  const totalSeats = filtered.reduce((sum, g) => sum + g.seats, 0);

  const handleSend = async (guest: Guest) => {
    const event = await loadEventConfig();
    const ok = await sendWhatsAppInvitation(guest, event);
    if (ok) {
      const updated = { ...guest, sent: true, sentAt: new Date().toISOString() };
      await upsertGuest(updated);
      refresh();
    }
  };

  const handleDelete = (guest: Guest) => {
    Alert.alert('Supprimer', `Supprimer ${guest.fullName} ?`, [
      { text: 'Annuler', style: 'cancel' },
      {
        text: 'Supprimer',
        style: 'destructive',
        onPress: async () => {
          await deleteGuest(guest.id);
          refresh();
        },
      },
    ]);
  };

  return (
    <View style={styles.container}>
      <View style={styles.searchRow}>
        <Ionicons name="search" size={18} color="#999" />
        <TextInput
          style={styles.searchInput}
          placeholder="Rechercher par nom ou téléphone..."
          value={search}
          onChangeText={setSearch}
        />
      </View>

      <FlatList
        horizontal
        data={tables}
        keyExtractor={(t) => t}
        showsHorizontalScrollIndicator={false}
        style={styles.filterList}
        renderItem={({ item }) => (
          <Pressable
            style={[styles.filterChip, tableFilter === item && styles.filterChipActive]}
            onPress={() => setTableFilter(item)}
          >
            <Text style={[styles.filterText, tableFilter === item && styles.filterTextActive]}>{item}</Text>
          </Pressable>
        )}
      />

      <View style={styles.stats}>
        <Text style={styles.statsText}>{filtered.length} invité(s) • {totalSeats} place(s)</Text>
        <Pressable onPress={() => exportGuestsCsv(filtered)}>
          <Ionicons name="download-outline" size={22} color="#5a2d82" />
        </Pressable>
      </View>

      <FlatList
        data={filtered}
        keyExtractor={(g) => g.id}
        renderItem={({ item }) => (
          <GuestListItem
            guest={item}
            onPress={() => router.push({ pathname: '/preview', params: { guestId: item.id } })}
            onSend={() => handleSend(item)}
            onDelete={() => handleDelete(item)}
          />
        )}
        ListEmptyComponent={
          <Text style={styles.empty}>Aucun invité. Ajoutez-en depuis le menu principal.</Text>
        }
        contentContainerStyle={{ paddingBottom: 20 }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, padding: 16 },
  searchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    borderRadius: 10,
    paddingHorizontal: 12,
    borderWidth: 1,
    borderColor: '#ddd',
    marginBottom: 12,
    gap: 8,
  },
  searchInput: { flex: 1, paddingVertical: 10, fontSize: 15 },
  filterList: { maxHeight: 40, marginBottom: 12 },
  filterChip: {
    paddingHorizontal: 14,
    paddingVertical: 6,
    borderRadius: 16,
    backgroundColor: '#eee',
    marginRight: 8,
  },
  filterChipActive: { backgroundColor: '#5a2d82' },
  filterText: { fontSize: 13, color: '#555' },
  filterTextActive: { color: '#fff', fontWeight: '600' },
  stats: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  statsText: { fontSize: 13, color: '#888' },
  empty: { textAlign: 'center', color: '#999', marginTop: 40 },
});
