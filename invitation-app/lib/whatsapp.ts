import { Linking, Platform, Share } from 'react-native';
import * as FileSystem from 'expo-file-system/legacy';
import { EventConfig, Guest } from './types';

export function formatWhatsAppMessage(template: string, guest: Guest, event: EventConfig): string {
  return template
    .replace(/\{NAME\}/g, guest.fullName)
    .replace(/\{DATE\}/g, event.date)
    .replace(/\{VENUE\}/g, event.venue)
    .replace(/\{TABLE\}/g, guest.tableZone || '—')
    .replace(/\{SEATS\}/g, String(guest.seats));
}

export function normalizeWhatsAppNumber(raw: string): string {
  return raw.replace(/\D/g, '');
}

export async function sendWhatsAppInvitation(
  guest: Guest,
  event: EventConfig,
  imageUri?: string | null,
): Promise<boolean> {
  const phone = normalizeWhatsAppNumber(guest.whatsapp);
  const message = formatWhatsAppMessage(event.whatsappMessage, guest, event);
  const encodedMessage = encodeURIComponent(message);

  if (imageUri && Platform.OS !== 'web') {
    try {
      await Share.share({
        message: `${message}\n\n(Votre invitation personnalisée est jointe)`,
        url: imageUri,
      });
      return true;
    } catch {
      // Fall through to wa.me link
    }
  }

  const url = `https://wa.me/${phone}?text=${encodedMessage}`;
  const canOpen = await Linking.canOpenURL(url);
  if (canOpen) {
    await Linking.openURL(url);
    return true;
  }
  return false;
}

export async function saveImageToCache(uri: string, guestId: string): Promise<string> {
  const dest = `${FileSystem.cacheDirectory}invitation_${guestId}.png`;
  if (uri.startsWith('file://') || uri.startsWith('/')) {
    await FileSystem.copyAsync({ from: uri, to: dest });
  }
  return dest;
}
