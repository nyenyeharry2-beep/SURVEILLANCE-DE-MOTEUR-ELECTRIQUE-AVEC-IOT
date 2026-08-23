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
import {
  loadEventConfig,
  loadGuests,
  loadTemplateConfig,
  upsertGuest,
} from '../lib/storage';
import { sendWhatsAppInvitation } from '../lib/whatsapp';
import { Guest, TemplateConfig } from '../lib/types';
import { EventConfig } from '../lib/types';

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
      setTemplate({ ...tmpl, templateUri: evt.templateUri || tmpl.templateUri, embedGuestName: evt.embedGuestName });
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
        <ActivityIndicator size="large" color="#5a2d82" />
      </View>
    );
  }

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <Text style={styles.header}>Aperçu final — {guest.fullName}</Text>

      <View style={styles.previewBox}>
        <InvitationCanvas ref={shotRef} guest={guest} config={template} width={320} />
      </View>

      <Pressable style={styles.sendBtn} onPress={handleSend} disabled={sending}>
        {sending ? (
          <ActivityIndicator color="#fff" />
        ) : (
          <>
            <Ionicons name="send" size={20} color="#fff" />
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
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  container: { padding: 20, alignItems: 'center', paddingBottom: 40 },
  header: { fontSize: 16, fontWeight: '700', color: '#5a2d82', marginBottom: 16 },
  previewBox: {
    borderRadius: 12,
    overflow: 'hidden',
    borderWidth: 2,
    borderColor: '#e8dff0',
    marginBottom: 20,
    elevation: 4,
  },
  sendBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: '#25D366',
    padding: 16,
    borderRadius: 12,
    width: '100%',
  },
  sendBtnText: { color: '#fff', fontSize: 16, fontWeight: '700' },
  closeBtn: { marginTop: 16, padding: 8 },
  closeBtnText: { color: '#888', fontSize: 14 },
});
