import { Image, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { ScreenHeader, Card } from '../components/ui/ScreenHeader';
import { theme, EVENT_SUBTITLE } from '../lib/theme';

export default function HomeScreen() {
  const router = useRouter();

  return (
    <ScrollView style={styles.scroll} contentContainerStyle={styles.container}>
      <Image source={require('../assets/floral-corner.png')} style={styles.floralTop} />
      <Image source={require('../assets/template-sarah.png')} style={styles.hero} resizeMode="cover" />

      <ScreenHeader title="Générateur d'Invitations" subtitle={EVENT_SUBTITLE} showSettings={false} />

      <Card>
        <Text style={styles.cardTitle}>Bienvenue</Text>
        <Text style={styles.cardDesc}>
          Créez des invitations uniques pour chaque invité à partir de l'affiche officielle de Moïse & Sarah.
        </Text>
      </Card>

      <MenuButton icon="settings-outline" label="Configurer l'événement" desc="Date, lieu, message WhatsApp" onPress={() => router.push('/config')} color={theme.configBlue} />
      <MenuButton icon="person-add-outline" label="Ajouter un invité" desc="Nom, WhatsApp, table, style" onPress={() => router.push('/add-guest')} color={theme.gold} />
      <MenuButton icon="people-outline" label="Liste & Tables" desc="Recherche, filtres, export CSV" onPress={() => router.push('/dashboard')} color={theme.purple} />

      <Image source={require('../assets/floral-corner.png')} style={styles.floralBottom} />
    </ScrollView>
  );
}

function MenuButton({
  icon,
  label,
  desc,
  onPress,
  color,
}: {
  icon: keyof typeof Ionicons.glyphMap;
  label: string;
  desc: string;
  onPress: () => void;
  color: string;
}) {
  return (
    <Pressable style={styles.menuBtn} onPress={onPress}>
      <View style={[styles.menuIcon, { backgroundColor: color + '18' }]}>
        <Ionicons name={icon} size={24} color={color} />
      </View>
      <View style={styles.menuText}>
        <Text style={styles.menuLabel}>{label}</Text>
        <Text style={styles.menuDesc}>{desc}</Text>
      </View>
      <Ionicons name="chevron-forward" size={20} color="#ccc" />
    </Pressable>
  );
}

const styles = StyleSheet.create({
  scroll: { flex: 1, backgroundColor: theme.creamBg },
  container: { padding: 20, paddingBottom: 40 },
  floralTop: { position: 'absolute', top: 0, right: 0, width: 100, height: 100, opacity: 0.9 },
  floralBottom: { position: 'absolute', bottom: 0, left: 0, width: 80, height: 80, opacity: 0.7, transform: [{ rotate: '180deg' }] },
  hero: {
    width: '100%',
    height: 220,
    borderRadius: 20,
    marginBottom: 8,
    borderWidth: 3,
    borderColor: theme.goldLight,
    ...theme.shadow,
  },
  cardTitle: { fontSize: 17, fontWeight: '800', color: theme.textDark, marginBottom: 6 },
  cardDesc: { fontSize: 14, color: theme.textMuted, lineHeight: 20 },
  menuBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    padding: 16,
    borderRadius: 18,
    marginBottom: 12,
    gap: 14,
    ...theme.shadow,
  },
  menuIcon: { width: 48, height: 48, borderRadius: 14, alignItems: 'center', justifyContent: 'center' },
  menuText: { flex: 1 },
  menuLabel: { fontSize: 16, fontWeight: '700', color: theme.textDark },
  menuDesc: { fontSize: 12, color: theme.textMuted, marginTop: 2 },
});
