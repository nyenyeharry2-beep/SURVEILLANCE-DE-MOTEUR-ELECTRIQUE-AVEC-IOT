import { useState } from 'react';
import {
  Alert,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { useRouter } from 'expo-router';
import * as Contacts from 'expo-contacts';
import { Ionicons } from '@expo/vector-icons';
import { createGuestId, upsertGuest } from '../lib/storage';
import { Guest } from '../lib/types';

export default function AddGuestScreen() {
  const router = useRouter();
  const [fullName, setFullName] = useState('');
  const [whatsapp, setWhatsapp] = useState('');
  const [seats, setSeats] = useState('1');
  const [tableZone, setTableZone] = useState('');
  const [styleName] = useState('Kipushi Floral');

  const importFromContacts = async () => {
    const { status } = await Contacts.requestPermissionsAsync();
    if (status !== 'granted') {
      Alert.alert('Permission refusée', 'Autorisez l\'accès aux contacts pour importer.');
      return;
    }

    const { data } = await Contacts.getContactsAsync({
      fields: [Contacts.Fields.Name, Contacts.Fields.PhoneNumbers],
    });

    if (data.length === 0) {
      Alert.alert('Aucun contact', 'Aucun contact trouvé sur cet appareil.');
      return;
    }

    const contact = data.find((c) => c.phoneNumbers?.length) || data[0];
    const name = contact.name || `${contact.firstName || ''} ${contact.lastName || ''}`.trim();
    const phone = contact.phoneNumbers?.[0]?.number || '';
    setFullName(name);
    setWhatsapp(phone.replace(/\D/g, ''));
  };

  const saveToContacts = async () => {
    if (!fullName.trim()) {
      Alert.alert('Erreur', 'Entrez un nom avant d\'enregistrer le contact.');
      return;
    }
    const { status } = await Contacts.requestPermissionsAsync();
    if (status !== 'granted') {
      Alert.alert('Permission refusée', 'Autorisez l\'accès aux contacts.');
      return;
    }

    await Contacts.addContactAsync({
      contactType: Contacts.ContactTypes.Person,
      name: fullName.trim(),
      [Contacts.Fields.PhoneNumbers]: [{ number: whatsapp, label: 'mobile' }],
    });
    Alert.alert('Succès', `${fullName} a été ajouté à vos contacts.`);
  };

  const generate = async () => {
    if (!fullName.trim() || !whatsapp.trim()) {
      Alert.alert('Champs requis', 'Nom complet et numéro WhatsApp sont obligatoires.');
      return;
    }

    const guest: Guest = {
      id: createGuestId(),
      fullName: fullName.trim(),
      whatsapp: whatsapp.trim(),
      seats: Math.max(1, parseInt(seats, 10) || 1),
      tableZone: tableZone.trim(),
      sent: false,
      createdAt: new Date().toISOString(),
    };

    await upsertGuest(guest);
    router.push({ pathname: '/preview', params: { guestId: guest.id } });
  };

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <Text style={styles.styleBadge}>Style : {styleName} — Élégant & Romantique</Text>

      <Text style={styles.label}>Nom complet</Text>
      <TextInput style={styles.input} value={fullName} onChangeText={setFullName} placeholder="Ex: Adriel NKUBA" />

      <Text style={styles.label}>Numéro WhatsApp</Text>
      <View style={styles.phoneRow}>
        <Ionicons name="logo-whatsapp" size={22} color="#25D366" />
        <TextInput
          style={[styles.input, styles.phoneInput]}
          value={whatsapp}
          onChangeText={setWhatsapp}
          placeholder="243XXXXXXXXX"
          keyboardType="phone-pad"
        />
      </View>

      <View style={styles.row2}>
        <View style={styles.half}>
          <Text style={styles.label}>Places</Text>
          <TextInput style={styles.input} value={seats} onChangeText={setSeats} keyboardType="number-pad" />
        </View>
        <View style={styles.half}>
          <Text style={styles.label}>Table / Zone</Text>
          <TextInput style={styles.input} value={tableZone} onChangeText={setTableZone} placeholder="Table Royale 1" />
        </View>
      </View>

      <View style={styles.contactRow}>
        <Pressable style={styles.contactBtn} onPress={importFromContacts}>
          <Ionicons name="book-outline" size={18} color="#5a2d82" />
          <Text style={styles.contactBtnText}>Importer contact</Text>
        </Pressable>
        <Pressable style={styles.contactBtn} onPress={saveToContacts}>
          <Ionicons name="person-add" size={18} color="#5a2d82" />
          <Text style={styles.contactBtnText}>Enregistrer contact</Text>
        </Pressable>
      </View>

      <Pressable style={styles.generateBtn} onPress={generate}>
        <Text style={styles.generateBtnText}>Générer l'invitation</Text>
      </Pressable>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { padding: 20, paddingBottom: 40 },
  styleBadge: {
    backgroundColor: '#f3ebf8',
    padding: 10,
    borderRadius: 8,
    color: '#5a2d82',
    fontWeight: '600',
    marginBottom: 16,
    textAlign: 'center',
  },
  label: { fontSize: 14, fontWeight: '600', color: '#444', marginBottom: 6, marginTop: 8 },
  input: {
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 10,
    padding: 12,
    fontSize: 15,
  },
  phoneRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  phoneInput: { flex: 1 },
  row2: { flexDirection: 'row', gap: 12 },
  half: { flex: 1 },
  contactRow: { flexDirection: 'row', gap: 10, marginTop: 16 },
  contactBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    padding: 12,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#d4c4e8',
    backgroundColor: '#fff',
  },
  contactBtnText: { fontSize: 12, fontWeight: '600', color: '#5a2d82' },
  generateBtn: {
    backgroundColor: '#c9a86c',
    padding: 16,
    borderRadius: 12,
    marginTop: 24,
    alignItems: 'center',
  },
  generateBtnText: { color: '#fff', fontSize: 16, fontWeight: '700' },
});
