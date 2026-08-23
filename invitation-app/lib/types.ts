export interface TemplateConfig {
  templateUri: string | null;
  embedGuestName: boolean;
  guestNameZone: TextZone;
  qrCodeZone: QrZone;
  placementZone: TextZone;
  whatsappMessage: string;
  eventDate: string;
  eventTime: string;
  eventVenue: string;
  coupleNames: string;
}

export interface TextZone {
  x: number;
  y: number;
  width: number;
  fontSize: number;
  color: string;
  align: 'left' | 'center' | 'right';
}

export interface QrZone {
  x: number;
  y: number;
  size: number;
}

export interface Guest {
  id: string;
  fullName: string;
  whatsapp: string;
  seats: number;
  tableZone: string;
  sent: boolean;
  sentAt?: string;
  createdAt: string;
}

export interface EventConfig {
  date: string;
  venue: string;
  whatsappMessage: string;
  embedGuestName: boolean;
  templateUri: string | null;
}

export const DEFAULT_WHATSAPP_MESSAGE =
  'Bonjour {NAME}, nous avons l\'honneur de vous inviter au mariage civil de nos enfants, Moïse NKUBA & Sarah KASONGO, le {DATE} à {VENUE}. Votre présence fera notre immense joie.';

export const DEFAULT_EVENT: EventConfig = {
  date: 'Vendredi, le 11 Septembre 2026',
  venue: 'Commune de Kipushi, Ville de KIPUSHI',
  whatsappMessage: DEFAULT_WHATSAPP_MESSAGE,
  embedGuestName: true,
  templateUri: null,
};

export const DEFAULT_TEMPLATE_CONFIG: TemplateConfig = {
  templateUri: null,
  embedGuestName: true,
  guestNameZone: { x: 0.48, y: 0.155, width: 0.48, fontSize: 22, color: '#2c2c2c', align: 'left' },
  qrCodeZone: { x: 0.065, y: 0.78, size: 0.17 },
  placementZone: { x: 0.26, y: 0.91, width: 0.68, fontSize: 16, color: '#5a2d82', align: 'center' },
  whatsappMessage: DEFAULT_WHATSAPP_MESSAGE,
  eventDate: DEFAULT_EVENT.date,
  eventTime: '11h00',
  eventVenue: DEFAULT_EVENT.venue,
  coupleNames: 'Moïse NKUBA & Sarah KASONGO',
};
