import { Image, Pressable, StyleSheet, Text, View } from 'react-native';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
export default function HomeScreen() {
  const router = useRouter();

  return (
    <View style={styles.container}>
      <Image source={require('../assets/template-invitation.png')} style={styles.hero} resizeMode="cover" />
      <Text style={styles.title}>Mariage de Moïse & Sarah</Text>
      <Text style={styles.subtitle}>Générateur d'invitations personnalisées</Text>

      <View style={styles.menu}>
        <MenuButton
          icon="settings-outline"
          label="Configurer l'événement"
          onPress={() => router.push('/config')}
        />
        <MenuButton
          icon="person-add-outline"
          label="Ajouter un invité"
          onPress={() => router.push('/add-guest')}
        />
        <MenuButton
          icon="people-outline"
          label="Liste & Tables"
          onPress={() => router.push('/dashboard')}
        />
      </View>

      <Text style={styles.hint}>
        Modèle fixe + superposition dynamique du nom, placement et QR code unique
      </Text>
    </View>
  );
}

function MenuButton({
  icon,
  label,
  onPress,
}: {
  icon: keyof typeof Ionicons.glyphMap;
  label: string;
  onPress: () => void;
}) {
  return (
    <Pressable style={styles.menuBtn} onPress={onPress}>
      <Ionicons name={icon} size={22} color="#5a2d82" />
      <Text style={styles.menuLabel}>{label}</Text>
      <Ionicons name="chevron-forward" size={18} color="#aaa" />
    </Pressable>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, padding: 20 },
  hero: {
    width: '100%',
    height: 200,
    borderRadius: 16,
    marginBottom: 16,
    borderWidth: 2,
    borderColor: '#e8dff0',
  },
  title: { fontSize: 24, fontWeight: '800', color: '#5a2d82', textAlign: 'center' },
  subtitle: { fontSize: 14, color: '#888', textAlign: 'center', marginBottom: 24 },
  menu: { gap: 10 },
  menuBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    padding: 16,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#e8dff0',
    gap: 12,
  },
  menuLabel: { flex: 1, fontSize: 16, fontWeight: '600', color: '#333' },
  hint: { marginTop: 24, fontSize: 12, color: '#999', textAlign: 'center', lineHeight: 18 },
});
