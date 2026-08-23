import React from 'react';
import { Pressable, StyleSheet, Text, View, ViewStyle } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { theme, EVENT_SUBTITLE } from '../../lib/theme';

interface Props {
  title: string;
  subtitle?: string;
  showSettings?: boolean;
  dark?: boolean;
}

export function ScreenHeader({ title, subtitle = EVENT_SUBTITLE, showSettings = true, dark = false }: Props) {
  const router = useRouter();

  return (
    <View style={styles.wrapper}>
      <View style={styles.row}>
        <View style={styles.titles}>
          <Text style={[styles.title, dark && styles.titleDark]}>{title}</Text>
          <Text style={styles.subtitle}>{subtitle}</Text>
        </View>
        {showSettings && (
          <Pressable onPress={() => router.push('/config')} style={styles.gearBtn} hitSlop={8}>
            <Ionicons name="settings-outline" size={22} color={dark ? '#fff' : theme.textDark} />
          </Pressable>
        )}
      </View>
    </View>
  );
}

export function Card({ children, style }: { children: React.ReactNode; style?: ViewStyle }) {
  return <View style={[styles.card, style]}>{children}</View>;
}

const styles = StyleSheet.create({
  wrapper: { marginBottom: 16 },
  row: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between' },
  titles: { flex: 1 },
  title: { fontSize: 22, fontWeight: '800', color: theme.textDark, textAlign: 'center' },
  titleDark: { color: '#fff' },
  subtitle: { fontSize: 15, fontWeight: '600', color: theme.gold, textAlign: 'center', marginTop: 4 },
  gearBtn: { padding: 4, marginTop: 2 },
  card: {
    backgroundColor: theme.cardBg,
    borderRadius: 20,
    padding: 20,
    marginBottom: 16,
    ...theme.shadow,
  },
});
