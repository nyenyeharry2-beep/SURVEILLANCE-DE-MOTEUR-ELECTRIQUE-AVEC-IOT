import { useEffect, useState } from 'react';
import {
  Alert,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useRouter } from 'expo-router';
import * as Contacts from 'expo-contacts';
import { ScreenHeader, Card } from '../components/ui/ScreenHeader';
import { OutlinedInput } from '../components/ui/OutlinedInput';
import { StyleSelector } from '../components/StyleSelector';
import { createGuestId, loadEventConfig, upsertGuest } from '../lib/storage';
import { Guest } from '../lib/types';
import { theme } from '../lib/theme';

export default function AddGuestScreen() {
  const router = useRouter();
  const [fullName, setFullName] = useState('');
  const [whatsapp, setWhatsapp] = useState('');
  const [seats, setSeats] = useState('1');
  const [tableZone, setTableZone] = useState('');
  const [styleId, setStyleId] = useState('kipushi-floral');

  useEffect(() => {
    loadEventConfig().then((e) => setStyleId(e.defaultStyleId || 'kipushi-floral'));
  }, []);

  const importFromContacts = async () => {
    const { status } = await Contacts.requestPermissionsAsync();
    if (status !== 'granted') {
      Alert.alert('Permission refusée', 'Autorisez l\'accès aux contacts pour importer.');
      return;
    }
    const { data } = await Contacts.getContactsAsync({
      fields: [Contacts.Fields.Name, Contacts.Fields.PhoneNumbers],
    });
    if (!data.length) {
      Alert.alert('Aucun contact', 'Aucun contact trouvé.');
      return;
    }
    const contact = data.find((c) => c.phoneNumbers?.length) || data[0];
    const name = contact.name || `${contact.firstName || ''} ${contact.lastName || ''}`.trim();
    setFullName(name);
    setWhatsapp((contact.phoneNumbers?.[0]?.number || '').replace(/\D/g, ''));
  };

  const saveToContacts = async () => {
    if (!fullName.trim()) {
      Alert.alert('Erreur', 'Entrez un nom avant d\'enregistrer.');
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
    Alert.alert('Succès', `${fullName} ajouté à vos contacts.`);
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
      styleId,
      sent: false,
      createdAt: new Date().toISOString(),
    };

    await upsertGuest(guest);
    router.push({ pathname: '/preview', params: { guestId: guest.id } });
  };

  return (
    <ScrollView style={styles.root} contentContainerStyle={styles.container}>
      <ScreenHeader title="Ajouter un invité" />

      <Card>
        <Text style={styles.infoTitle}>Nouvel invité d'honneur</Text>
        <Text style={styles.infoDesc}>
          Crée une invitation raffinée, prête à être partagée en quelques secondes.
        </Text>
      </Card>

      <Card>
        <Text style={styles.sectionTitle}>Informations invité</Text>

        <OutlinedInput
          label="Nom complet"
          value={fullName}
          onChangeText={setFullName}
          placeholder="Ex: Adriel NKUBA"
          icon="person-outline"
          rightAction={{ icon: 'add-circle', onPress: importFromContacts }}
        />

        <OutlinedInput
          label="Numéro WhatsApp"
          value={whatsapp}
          onChangeText={setWhatsapp}
          placeholder="243XXXXXXXXX"
          keyboardType="phone-pad"
          icon="call-outline"
          hint="Format conseillé : 243XXXXXXXXX"
        />

        <View style={styles.row2}>
          <View style={styles.placesCol}>
            <OutlinedInput label="Places" value={seats} onChangeText={setSeats} keyboardType="number-pad" />
          </View>
          <View style={styles.tableCol}>
            <OutlinedInput
              label="Table / Zone"
              value={tableZone}
              onChangeText={setTableZone}
              placeholder="Table Royale 1"
              hint="Ex. : Table Royale 1"
            />
          </View>
        </View>

        <Pressable style={styles.saveContactBtn} onPress={saveToContacts}>
          <Text style={styles.saveContactText}>Enregistrer dans le téléphone</Text>
        </Pressable>
      </Card>

      <StyleSelector selectedId={styleId} onSelect={setStyleId} />

      <Pressable style={styles.generateBtn} onPress={generate}>
        <Text style={styles.generateBtnText}>Générer l'invitation</Text>
      </Pressable>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: theme.creamBg },
  container: { padding: 20, paddingBottom: 48 },
  infoTitle: { fontSize: 16, fontWeight: '800', color: theme.textDark, marginBottom: 6 },
  infoDesc: { fontSize: 14, color: theme.textMuted, lineHeight: 20 },
  sectionTitle: { fontSize: 15, fontWeight: '800', color: theme.textDark, marginBottom: 4 },
  row2: { flexDirection: 'row', gap: 12 },
  placesCol: { width: 90 },
  tableCol: { flex: 1 },
  saveContactBtn: {
    alignSelf: 'center',
    paddingVertical: 8,
    marginTop: 4,
  },
  saveContactText: { fontSize: 13, color: theme.gold, fontWeight: '600' },
  generateBtn: {
    backgroundColor: theme.goldLight,
    padding: 18,
    borderRadius: 16,
    marginTop: 16,
    alignItems: 'center',
    ...theme.shadow,
  },
  generateBtnText: { color: theme.textDark, fontSize: 17, fontWeight: '800' },
});
