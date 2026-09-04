import { useCallback, useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import ViewShot from 'react-native-view-shot';
import { Ionicons } from '@expo/vector-icons';
import { InvitationCanvas } from '../components/InvitationCanvas';
import { ScreenHeader } from '../components/ui/ScreenHeader';
import { buildTemplateConfig } from '../lib/styles';
import {
  loadEventConfig,
  loadGuests,
  loadTemplateConfig,
  upsertGuest,
} from '../lib/storage';
import { sendWhatsAppInvitation } from '../lib/whatsapp';
import { Guest, TemplateConfig, EventConfig } from '../lib/types';
import { theme } from '../lib/theme';

export default function PreviewScreen() {
  const { guestId } = useLocalSearchParams<{ guestId: string }>();
  const router = useRouter();
  const shotRef = useRef<React.ElementRef<typeof ViewShot>>(null);
  const [guest, setGuest] = useState<Guest | null>(null);
  const [event, setEvent] = useState<EventConfig | null>(null);
  const [template, setTemplate] = useState<TemplateConfig | null>(null);
  const [sending, setSending] = useState(false);

  useEffect(() => {
    (async () => {
      const [guests, evt, tmpl] = await Promise.all([
        loadGuests(),
        loadEventConfig(),
        loadTemplateConfig(),
      ]);
      const found = guests.find((g) => g.id === guestId);
      setGuest(found || null);
      setEvent(evt);
      const styleId = found?.styleId || evt.defaultStyleId || 'kipushi-floral';
      setTemplate(
        buildTemplateConfig(styleId, {
          ...tmpl,
          templateUri: evt.templateUri || tmpl.templateUri,
          embedGuestName: evt.embedGuestName,
          styleId,
        }),
      );
    })();
  }, [guestId]);

  const captureImage = useCallback(async (): Promise<string | null> => {
    if (!shotRef.current?.capture) return null;
    try {
      return await shotRef.current.capture();
    } catch {
      return null;
    }
  }, []);

  const handleSend = async () => {
    if (!guest || !event) return;
    setSending(true);
    try {
      const imageUri = await captureImage();
      const ok = await sendWhatsAppInvitation(guest, event, imageUri);
      if (ok) {
        const updated = { ...guest, sent: true, sentAt: new Date().toISOString() };
        await upsertGuest(updated);
        setGuest(updated);
        Alert.alert('Invitation envoyée', 'WhatsApp a été ouvert avec le message personnalisé.');
      } else {
        Alert.alert('Erreur', 'Impossible d\'ouvrir WhatsApp.');
      }
    } finally {
      setSending(false);
    }
  };

  if (!guest || !event || !template) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color={theme.gold} />
      </View>
    );
  }

  return (
    <ScrollView style={styles.root} contentContainerStyle={styles.container}>
      <ScreenHeader title="Aperçu final" showSettings={false} />

      <View style={styles.phoneFrame}>
        <InvitationCanvas ref={shotRef} guest={guest} config={template} width={300} />
      </View>

      <Pressable style={styles.sendBtn} onPress={handleSend} disabled={sending}>
        {sending ? (
          <ActivityIndicator color="#fff" />
        ) : (
          <>
            <Ionicons name="paper-plane" size={20} color="#fff" />
            <Text style={styles.sendBtnText}>Envoyer l'invitation</Text>
          </>
        )}
      </Pressable>

      <Pressable style={styles.closeBtn} onPress={() => router.back()}>
        <Text style={styles.closeBtnText}>Fermer</Text>
      </Pressable>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: theme.creamBg },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: theme.creamBg },
  container: { padding: 20, alignItems: 'center', paddingBottom: 40 },
  phoneFrame: {
    borderRadius: 20,
    overflow: 'hidden',
    backgroundColor: '#2A2A2A',
    padding: 12,
    marginBottom: 24,
    ...theme.shadow,
  },
  sendBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
    backgroundColor: theme.greenWhatsApp,
    padding: 18,
    borderRadius: 16,
    width: '100%',
  },
  sendBtnText: { color: '#fff', fontSize: 17, fontWeight: '800' },
  closeBtn: { marginTop: 20, padding: 8 },
  closeBtnText: { color: theme.configBlue, fontSize: 15, fontWeight: '600' },
});
