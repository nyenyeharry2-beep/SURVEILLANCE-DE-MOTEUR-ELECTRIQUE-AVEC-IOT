import { useEffect, useState } from 'react';
import {
  Alert,
  Image,
  Pressable,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  TextInput,
  View,
} from 'react-native';
import { useRouter } from 'expo-router';
import * as DocumentPicker from 'expo-document-picker';
import * as ImagePicker from 'expo-image-picker';
import { loadEventConfig, loadTemplateConfig, saveEventConfig, saveTemplateConfig } from '../lib/storage';
import { EventConfig } from '../lib/types';

export default function ConfigScreen() {
  const router = useRouter();
  const [config, setConfig] = useState<EventConfig>({
    date: '',
    venue: '',
    whatsappMessage: '',
    embedGuestName: true,
    templateUri: null,
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    (async () => {
      const [event, template] = await Promise.all([loadEventConfig(), loadTemplateConfig()]);
      setConfig(event);
      if (template.templateUri) {
        setConfig((c) => ({ ...c, templateUri: template.templateUri }));
      }
      setLoading(false);
    })();
  }, []);

  const pickTemplate = async () => {
    Alert.alert('Choisir le modèle', 'Sélectionnez une source', [
      {
        text: 'Galerie',
        onPress: async () => {
          const result = await ImagePicker.launchImageLibraryAsync({
            mediaTypes: ['images'],
            quality: 1,
          });
          if (!result.canceled) {
            setConfig((c) => ({ ...c, templateUri: result.assets[0].uri }));
          }
        },
      },
      {
        text: 'Fichier',
        onPress: async () => {
          const result = await DocumentPicker.getDocumentAsync({ type: 'image/*' });
          if (!result.canceled) {
            setConfig((c) => ({ ...c, templateUri: result.assets[0].uri }));
          }
        },
      },
      { text: 'Annuler', style: 'cancel' },
    ]);
  };

  const save = async () => {
    await saveEventConfig(config);
    const template = await loadTemplateConfig();
    await saveTemplateConfig({ ...template, templateUri: config.templateUri, embedGuestName: config.embedGuestName, whatsappMessage: config.whatsappMessage });
    router.push('/add-guest');
  };

  if (loading) return null;

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <Text style={styles.section}>Modèle d'invitation (PNG/JPG HD)</Text>
      <Pressable style={styles.templateBox} onPress={pickTemplate}>
        <Image
          source={config.templateUri ? { uri: config.templateUri } : require('../assets/template-invitation.png')}
          style={styles.templateImg}
          resizeMode="cover"
        />
        <Text style={styles.templateHint}>Appuyez pour téléverser une affiche</Text>
      </Pressable>

      <Text style={styles.label}>Date</Text>
      <TextInput
        style={styles.input}
        value={config.date}
        onChangeText={(date) => setConfig((c) => ({ ...c, date }))}
        placeholder="Vendredi, le 11 Septembre 2026"
      />

      <Text style={styles.label}>Lieu / Salle</Text>
      <TextInput
        style={styles.input}
        value={config.venue}
        onChangeText={(venue) => setConfig((c) => ({ ...c, venue }))}
        placeholder="Commune de Kipushi, Ville de KIPUSHI"
      />

      <Text style={styles.label}>Message WhatsApp</Text>
      <TextInput
        style={[styles.input, styles.textarea]}
        value={config.whatsappMessage}
        onChangeText={(whatsappMessage) => setConfig((c) => ({ ...c, whatsappMessage }))}
        multiline
        numberOfLines={5}
      />
      <Text style={styles.hint}>Variables : {'{NAME}'}, {'{DATE}'}, {'{VENUE}'}, {'{TABLE}'}, {'{SEATS}'}</Text>

      <View style={styles.row}>
        <Text style={styles.label}>Incruster le nom de l'invité sur l'image</Text>
        <Switch
          value={config.embedGuestName}
          onValueChange={(embedGuestName) => setConfig((c) => ({ ...c, embedGuestName }))}
          trackColor={{ true: '#9b59b6' }}
        />
      </View>

      <Pressable style={styles.saveBtn} onPress={save}>
        <Text style={styles.saveBtnText}>Enregistrer et Commencer</Text>
      </Pressable>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { padding: 20, paddingBottom: 40 },
  section: { fontSize: 14, fontWeight: '700', color: '#5a2d82', marginBottom: 8 },
  templateBox: {
    borderRadius: 12,
    overflow: 'hidden',
    borderWidth: 2,
    borderColor: '#e8dff0',
    marginBottom: 20,
  },
  templateImg: { width: '100%', height: 180 },
  templateHint: { textAlign: 'center', padding: 8, color: '#888', fontSize: 12 },
  label: { fontSize: 14, fontWeight: '600', color: '#444', marginBottom: 6, marginTop: 8 },
  input: {
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 10,
    padding: 12,
    fontSize: 15,
  },
  textarea: { minHeight: 120, textAlignVertical: 'top' },
  hint: { fontSize: 11, color: '#999', marginTop: 4 },
  row: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 16 },
  saveBtn: {
    backgroundColor: '#3498db',
    padding: 16,
    borderRadius: 12,
    marginTop: 24,
    alignItems: 'center',
  },
  saveBtnText: { color: '#fff', fontSize: 16, fontWeight: '700' },
});
