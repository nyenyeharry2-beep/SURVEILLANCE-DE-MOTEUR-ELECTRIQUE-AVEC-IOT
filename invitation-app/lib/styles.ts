import { TemplateConfig } from './types';

export interface InvitationStyle {
  id: string;
  name: string;
  subtitle: string;
  template: number; // require() asset
  previewColors: [string, string]; // gradient for style card preview
  config: Partial<TemplateConfig>;
}

export const INVITATION_STYLES: InvitationStyle[] = [
  {
    id: 'kipushi-floral',
    name: 'Style Kipushi Floral',
    subtitle: 'Élégant & Romantique',
    template: require('../assets/template-sarah.png'),
    previewColors: ['#FFFFFF', '#E8DFF0'],
    config: {
      guestNameZone: { x: 0.48, y: 0.168, width: 0.48, fontSize: 20, color: '#2c2c2c', align: 'left' },
      qrCodeZone: { x: 0.065, y: 0.78, size: 0.16 },
      placementZone: { x: 0.26, y: 0.915, width: 0.68, fontSize: 14, color: '#6B2D82', align: 'center' },
    },
  },
  {
    id: 'royal-bordeaux',
    name: 'Royal Bordeaux',
    subtitle: 'Classique & noble',
    template: require('../assets/template-royal-bordeaux.png'),
    previewColors: ['#5C1A1A', '#C9A86C'],
    config: {
      guestNameZone: { x: 0.08, y: 0.42, width: 0.84, fontSize: 38, color: '#FFFFFF', align: 'center' },
      qrCodeZone: { x: 0.35, y: 0.72, size: 0.22 },
      placementZone: { x: 0.1, y: 0.62, width: 0.8, fontSize: 14, color: '#D4B896', align: 'center' },
    },
  },
  {
    id: 'ivory-prestige',
    name: 'Ivory Prestige',
    subtitle: 'Lumineux & raffiné',
    template: require('../assets/template-ivory.png'),
    previewColors: ['#FFFDF8', '#D4B896'],
    config: {
      guestNameZone: { x: 0.1, y: 0.38, width: 0.8, fontSize: 32, color: '#5C1A1A', align: 'center' },
      qrCodeZone: { x: 0.38, y: 0.7, size: 0.2 },
      placementZone: { x: 0.1, y: 0.58, width: 0.8, fontSize: 14, color: '#9A7B4F', align: 'center' },
    },
  },
  {
    id: 'ville-kipushi',
    name: 'Style Ville de Kipushi',
    subtitle: 'Moderne & Raffiné',
    template: require('../assets/template-ville.png'),
    previewColors: ['#4A2060', '#8B6FA8'],
    config: {
      guestNameZone: { x: 0.08, y: 0.35, width: 0.84, fontSize: 30, color: '#FFFFFF', align: 'center' },
      qrCodeZone: { x: 0.72, y: 0.78, size: 0.18 },
      placementZone: { x: 0.08, y: 0.52, width: 0.84, fontSize: 14, color: '#E8DFF0', align: 'center' },
    },
  },
];

export function getStyleById(id: string): InvitationStyle {
  return INVITATION_STYLES.find((s) => s.id === id) ?? INVITATION_STYLES[0];
}

export function buildTemplateConfig(styleId: string, base: TemplateConfig): TemplateConfig {
  const style = getStyleById(styleId);
  return { ...base, ...style.config, styleId };
}
