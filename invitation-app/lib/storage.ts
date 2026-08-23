import AsyncStorage from '@react-native-async-storage/async-storage';
import { DEFAULT_EVENT, DEFAULT_TEMPLATE_CONFIG, EventConfig, Guest, TemplateConfig } from './types';

const KEYS = {
  event: '@invitation/event',
  guests: '@invitation/guests',
  template: '@invitation/template',
};

export async function loadEventConfig(): Promise<EventConfig> {
  const raw = await AsyncStorage.getItem(KEYS.event);
  if (!raw) return DEFAULT_EVENT;
  return { ...DEFAULT_EVENT, ...JSON.parse(raw) };
}

export async function saveEventConfig(config: EventConfig): Promise<void> {
  await AsyncStorage.setItem(KEYS.event, JSON.stringify(config));
}

export async function loadGuests(): Promise<Guest[]> {
  const raw = await AsyncStorage.getItem(KEYS.guests);
  if (!raw) return [];
  const guests: Guest[] = JSON.parse(raw);
  return guests.map((g) => ({ ...g, styleId: g.styleId || 'kipushi-floral' }));
}

export async function saveGuests(guests: Guest[]): Promise<void> {
  await AsyncStorage.setItem(KEYS.guests, JSON.stringify(guests));
}

export async function upsertGuest(guest: Guest): Promise<void> {
  const guests = await loadGuests();
  const index = guests.findIndex((g) => g.id === guest.id);
  if (index >= 0) {
    guests[index] = guest;
  } else {
    guests.unshift(guest);
  }
  await saveGuests(guests);
}

export async function deleteGuest(id: string): Promise<void> {
  const guests = await loadGuests();
  await saveGuests(guests.filter((g) => g.id !== id));
}

export async function loadTemplateConfig(): Promise<TemplateConfig> {
  const raw = await AsyncStorage.getItem(KEYS.template);
  if (!raw) return DEFAULT_TEMPLATE_CONFIG;
  return { ...DEFAULT_TEMPLATE_CONFIG, ...JSON.parse(raw) };
}

export async function saveTemplateConfig(config: TemplateConfig): Promise<void> {
  await AsyncStorage.setItem(KEYS.template, JSON.stringify(config));
}

export function createGuestId(): string {
  return `guest_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
}
