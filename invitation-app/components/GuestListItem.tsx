import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { getStyleById } from '../lib/styles';
import { Guest } from '../lib/types';
import { theme } from '../lib/theme';

interface Props {
  guest: Guest;
  onPress: () => void;
  onSend: () => void;
  onDelete: () => void;
}

export function GuestListItem({ guest, onPress, onSend, onDelete }: Props) {
  const styleName = getStyleById(guest.styleId || 'kipushi-floral').name;

  return (
    <Pressable style={styles.card} onPress={onPress}>
      <View style={styles.info}>
        <Text style={styles.name}>{guest.fullName}</Text>
        <Text style={styles.phone}>{guest.whatsapp}</Text>
        <Text style={styles.meta}>
          {guest.tableZone || 'Sans table'} • {guest.seats} place{guest.seats > 1 ? 's' : ''} • {styleName}
        </Text>
        <View style={[styles.badge, guest.sent ? styles.sent : styles.pending]}>
          <Text style={styles.badgeText}>{guest.sent ? 'Envoyé' : 'Non envoyé'}</Text>
        </View>
      </View>
      <View style={styles.actions}>
        <Pressable onPress={onSend} style={styles.actionBtn}>
          <Ionicons name="logo-whatsapp" size={22} color={theme.greenWhatsApp} />
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
    borderRadius: 16,
    padding: 16,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: '#E0D5C8',
    ...theme.shadow,
  },
  info: { flex: 1 },
  name: { fontSize: 16, fontWeight: '800', color: theme.textDark },
  phone: { fontSize: 13, color: theme.textMuted, marginTop: 2 },
  meta: { fontSize: 11, color: theme.gold, marginTop: 4 },
  badge: { alignSelf: 'flex-start', paddingHorizontal: 10, paddingVertical: 3, borderRadius: 10, marginTop: 8 },
  sent: { backgroundColor: '#D4EDDA' },
  pending: { backgroundColor: '#FFF3CD' },
  badgeText: { fontSize: 11, fontWeight: '700', color: '#333' },
  actions: { justifyContent: 'center', gap: 10 },
  actionBtn: { padding: 6 },
});
