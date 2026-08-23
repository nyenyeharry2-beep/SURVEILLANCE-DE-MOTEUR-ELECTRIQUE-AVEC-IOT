import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { Guest } from '../lib/types';

interface Props {
  guest: Guest;
  onPress: () => void;
  onSend: () => void;
  onDelete: () => void;
}

export function GuestListItem({ guest, onPress, onSend, onDelete }: Props) {
  return (
    <Pressable style={styles.card} onPress={onPress}>
      <View style={styles.info}>
        <Text style={styles.name}>{guest.fullName}</Text>
        <Text style={styles.phone}>{guest.whatsapp}</Text>
        <Text style={styles.meta}>
          {guest.tableZone || 'Sans table'} • {guest.seats} place{guest.seats > 1 ? 's' : ''}
        </Text>
        <View style={[styles.badge, guest.sent ? styles.sent : styles.pending]}>
          <Text style={styles.badgeText}>{guest.sent ? 'Envoyé' : 'Non envoyé'}</Text>
        </View>
      </View>
      <View style={styles.actions}>
        <Pressable onPress={onSend} style={styles.actionBtn}>
          <Ionicons name="logo-whatsapp" size={22} color="#25D366" />
        </Pressable>
        <Pressable onPress={onDelete} style={styles.actionBtn}>
          <Ionicons name="trash-outline" size={20} color="#c0392b" />
        </Pressable>
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  card: {
    flexDirection: 'row',
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: '#e8dff0',
    elevation: 1,
  },
  info: { flex: 1 },
  name: { fontSize: 16, fontWeight: '700', color: '#3d2456' },
  phone: { fontSize: 13, color: '#666', marginTop: 2 },
  meta: { fontSize: 12, color: '#8b6fa8', marginTop: 4 },
  badge: {
    alignSelf: 'flex-start',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 8,
    marginTop: 6,
  },
  sent: { backgroundColor: '#d4edda' },
  pending: { backgroundColor: '#fff3cd' },
  badgeText: { fontSize: 11, fontWeight: '600', color: '#333' },
  actions: { justifyContent: 'center', gap: 8 },
  actionBtn: { padding: 6 },
});
