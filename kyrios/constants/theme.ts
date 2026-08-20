export const Colors = {
  background: '#0A0A0A',
  surface: '#141414',
  surfaceLight: '#1E1E1E',
  surfaceElevated: '#2A2A2A',
  border: '#2E2E2E',
  text: '#FFFFFF',
  textSecondary: '#8E8E93',
  textMuted: '#636366',
  accent: '#FF5A5F',
  accentOrange: '#FF6B35',
  accentPink: '#FF4081',
  online: '#34C759',
  unread: '#FF3B30',
  sentBubble: '#2C2C2E',
  receivedBubble: '#3A3A3C',
  checkmark: '#34C759',
  white: '#FFFFFF',
  black: '#000000',
  transparent: 'transparent',
};

export const Gradients = {
  accent: ['#FF6B35', '#FF4081'] as const,
  accentSoft: ['#FF6B3533', '#FF408133'] as const,
  story: ['#FF6B35', '#FF4081', '#9C27B0'] as const,
};

export const Spacing = {
  xs: 4,
  sm: 8,
  md: 12,
  lg: 16,
  xl: 20,
  xxl: 24,
  xxxl: 32,
};

export const BorderRadius = {
  sm: 8,
  md: 12,
  lg: 16,
  xl: 20,
  xxl: 24,
  full: 999,
};

export const FontSize = {
  xs: 11,
  sm: 13,
  md: 15,
  lg: 17,
  xl: 20,
  xxl: 28,
  title: 34,
};

export const FontWeight = {
  regular: '400' as const,
  medium: '500' as const,
  semibold: '600' as const,
  bold: '700' as const,
};
