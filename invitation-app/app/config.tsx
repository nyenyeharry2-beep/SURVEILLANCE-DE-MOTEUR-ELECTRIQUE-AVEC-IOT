import { useEffect, useState } from 'react';
import {
  Alert,
  Image,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useRouter } from 'expo-router';
import * as DocumentPicker from 'expo-document-picker';
import * as ImagePicker from 'expo-image-picker';
import { OutlinedInput, BlueCheckbox } from '../components/ui/OutlinedInput';
import { loadEventConfig, loadTemplateConfig, saveEventConfig, saveTemplateConfig } from '../lib/storage';
import { EventConfig } from '../lib/types';
import { theme } from '../lib/theme';

export default function ConfigScreen() {
  const router = useRouter();
  const [config, setConfig] = useState<EventConfig>({
    date: '',
    venue: '',
    whatsappMessage: '',
    embedGuestName: true,
    templateUri: null,
    defaultStyleId: 'kipushi-floral',
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    (async () => {
      const [event, template] = await Promise.all([loadEventConfig(), loadTemplateConfig()]);
      setConfig({ ...event, templateUri: event.templateUri || template.templateUri });
      setLoading(false);
    })();
  }, []);

  const pickTemplate = async () => {
    Alert.alert('Affiche Sarah & Moïse', 'Téléversez votre affiche HD', [
      {
        text: 'Galerie',
        onPress: async () => {
          const result = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], quality: 1 });
          if (!result.canceled) setConfig((c) => ({ ...c, templateUri: result.assets[0].uri }));
        },
      },
      {
        text: 'Fichier',
        onPress: async () => {
          const result = await DocumentPicker.getDocumentAsync({ type: 'image/*' });
          if (!result.canceled) setConfig((c) => ({ ...c, templateUri: result.assets[0].uri }));
        },
      },
      { text: 'Annuler', style: 'cancel' },
    ]);
  };

  const save = async () => {
    await saveEventConfig(config);
    const template = await loadTemplateConfig();
    await saveTemplateConfig({
      ...template,
      templateUri: config.templateUri,
      embedGuestName: config.embedGuestName,
      whatsappMessage: config.whatsappMessage,
      styleId: config.defaultStyleId,
    });
    router.push('/add-guest');
  };

  if (loading) return <View style={styles.root} />;

  return (
    <ScrollView style={styles.root} contentContainerStyle={styles.container}>
      <Image source={require('../assets/floral-corner.png')} style={styles.floral} />

      <Text style={styles.title}>Configurer l'événement</Text>
      <Text style={styles.intro}>
        Remplissez ces informations une seule fois. Elles seront utilisées pour toutes les invitations.
      </Text>

      <Pressable style={styles.posterBox} onPress={pickTemplate}>
        <Image
          source={config.templateUri ? { uri: config.templateUri } : require('../assets/template-sarah.png')}
          style={styles.poster}
          resizeMode="cover"
        />
        <Text style={styles.posterHint}>Appuyez pour remplacer l'affiche Sarah & Moïse</Text>
      </Pressable>

      <OutlinedInput
        dark
        label="Date (ex: 11 Septembre 2026)"
        value={config.date}
        onChangeText={(date) => setConfig((c) => ({ ...c, date }))}
        placeholder="Vendredi, le 11 Septembre 2026"
      />

      <OutlinedInput
        dark
        label="Lieu / Salle"
        value={config.venue}
        onChangeText={(venue) => setConfig((c) => ({ ...c, venue }))}
        placeholder="Commune de Kipushi, Ville de KIPUSHI"
      />

      <OutlinedInput
        dark
        label="Message WhatsApp"
        value={config.whatsappMessage}
        onChangeText={(whatsappMessage) => setConfig((c) => ({ ...c, whatsappMessage }))}
        multiline
        numberOfLines={5}
        style={{ minHeight: 100, textAlignVertical: 'top' }}
        hint="Utilisez {NAME}, {DATE} et {VENUE} pour personnaliser automatiquement le message."
      />

      <BlueCheckbox
        checked={config.embedGuestName}
        onToggle={() => setConfig((c) => ({ ...c, embedGuestName: !c.embedGuestName }))}
        label="Incruster le nom de l'invité sur l'image"
      />

      <Pressable style={styles.saveBtn} onPress={save}>
        <Text style={styles.saveBtnText}>Enregistrer et Commencer</Text>
      </Pressable>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: theme.configBg },
  container: { padding: 24, paddingBottom: 48 },
  floral: { position: 'absolute', top: 0, right: 0, width: 120, height: 120, opacity: 0.85 },
  title: { fontSize: 24, fontWeight: '800', color: theme.configText, marginTop: 16, marginBottom: 8 },
  intro: { fontSize: 14, color: theme.configMuted, lineHeight: 20, marginBottom: 20 },
  posterBox: { borderRadius: 14, overflow: 'hidden', marginBottom: 20, borderWidth: 1, borderColor: theme.configBorder },
  poster: { width: '100%', height: 160 },
  posterHint: { textAlign: 'center', padding: 8, color: theme.configMuted, fontSize: 11, backgroundColor: '#111' },
  saveBtn: {
    backgroundColor: theme.configBlue,
    padding: 18,
    borderRadius: 14,
    marginTop: 24,
    alignItems: 'center',
  },
  saveBtnText: { color: '#fff', fontSize: 17, fontWeight: '800' },
});
