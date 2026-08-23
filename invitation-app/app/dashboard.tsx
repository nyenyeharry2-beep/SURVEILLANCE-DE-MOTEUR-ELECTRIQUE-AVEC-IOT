import { useCallback, useState } from 'react';
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
import { ScreenHeader } from '../components/ui/ScreenHeader';
import { deleteGuest, loadEventConfig, loadGuests, upsertGuest } from '../lib/storage';
import { exportGuestsCsv, filterGuests, getUniqueTables } from '../lib/export';
import { sendWhatsAppInvitation } from '../lib/whatsapp';
import { Guest } from '../lib/types';
import { theme } from '../lib/theme';

export default function DashboardScreen() {
  const router = useRouter();
  const [guests, setGuests] = useState<Guest[]>([]);
  const [search, setSearch] = useState('');
  const [tableFilter, setTableFilter] = useState('Toutes');

  const refresh = useCallback(async () => {
    setGuests(await loadGuests());
  }, []);

  useFocusEffect(useCallback(() => { refresh(); }, [refresh]));

  const filtered = filterGuests(guests, search, tableFilter);
  const tables = getUniqueTables(guests);
  const totalSeats = filtered.reduce((sum, g) => sum + g.seats, 0);

  const handleSend = async (guest: Guest) => {
    const event = await loadEventConfig();
    const ok = await sendWhatsAppInvitation(guest, event);
    if (ok) {
      await upsertGuest({ ...guest, sent: true, sentAt: new Date().toISOString() });
      refresh();
    }
  };

  const handleDelete = (guest: Guest) => {
    Alert.alert('Supprimer', `Supprimer ${guest.fullName} ?`, [
      { text: 'Annuler', style: 'cancel' },
      { text: 'Supprimer', style: 'destructive', onPress: async () => { await deleteGuest(guest.id); refresh(); } },
    ]);
  };

  return (
    <View style={styles.root}>
      <View style={styles.headerPad}>
        <ScreenHeader title="Liste des invités" showSettings={false} />
      </View>

      <View style={styles.searchRow}>
        <Ionicons name="search" size={18} color={theme.textMuted} />
        <TextInput
          style={styles.searchInput}
          placeholder="Rechercher par nom ou téléphone..."
          placeholderTextColor="#bbb"
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
        contentContainerStyle={{ paddingHorizontal: 20 }}
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
          <Ionicons name="download-outline" size={22} color={theme.gold} />
        </Pressable>
      </View>

      <FlatList
        data={filtered}
        keyExtractor={(g) => g.id}
        contentContainerStyle={{ paddingHorizontal: 20, paddingBottom: 24 }}
        renderItem={({ item }) => (
          <GuestListItem
            guest={item}
            onPress={() => router.push({ pathname: '/preview', params: { guestId: item.id } })}
            onSend={() => handleSend(item)}
            onDelete={() => handleDelete(item)}
          />
        )}
        ListEmptyComponent={<Text style={styles.empty}>Aucun invité pour le moment.</Text>}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: theme.creamBg },
  headerPad: { paddingHorizontal: 20, paddingTop: 16 },
  searchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    borderRadius: 14,
    paddingHorizontal: 14,
    marginHorizontal: 20,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#E0D5C8',
    gap: 8,
  },
  searchInput: { flex: 1, paddingVertical: 12, fontSize: 15, color: theme.textDark },
  filterList: { maxHeight: 44, marginBottom: 12 },
  filterChip: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 20,
    backgroundColor: '#fff',
    marginRight: 8,
    borderWidth: 1,
    borderColor: '#E0D5C8',
  },
  filterChipActive: { backgroundColor: theme.gold, borderColor: theme.gold },
  filterText: { fontSize: 13, color: theme.textMuted, fontWeight: '600' },
  filterTextActive: { color: '#fff' },
  stats: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 20, marginBottom: 12 },
  statsText: { fontSize: 13, color: theme.textMuted },
  empty: { textAlign: 'center', color: theme.textMuted, marginTop: 40 },
});
