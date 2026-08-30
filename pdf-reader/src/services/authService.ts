import { getUserByEmail, getUserById, saveUser } from './db';
import type { UserProfile } from '../types/document';

const SESSION_KEY = 'lumen-reader-session';

async function hashPassword(password: string): Promise<string> {
  const encoded = new TextEncoder().encode(password);
  const digest = await crypto.subtle.digest('SHA-256', encoded);
  return Array.from(new Uint8Array(digest))
    .map((byte) => byte.toString(16).padStart(2, '0'))
    .join('');
}

export async function registerUser(
  name: string,
  email: string,
  password: string,
): Promise<UserProfile> {
  const normalizedEmail = email.trim().toLowerCase();

  if (!name.trim() || !normalizedEmail || password.length < 6) {
    throw new Error('Informations invalides. Mot de passe minimum 6 caractères.');
  }

  const existing = await getUserByEmail(normalizedEmail);
  if (existing) {
    throw new Error('Un compte existe déjà avec cet email.');
  }

  const user: UserProfile = {
    id: `user-${crypto.randomUUID().slice(0, 8)}`,
    name: name.trim(),
    email: normalizedEmail,
    passwordHash: await hashPassword(password),
    createdAt: new Date().toISOString(),
  };

  await saveUser(user);
  localStorage.setItem(SESSION_KEY, user.id);
  return user;
}

export async function loginUser(email: string, password: string): Promise<UserProfile> {
  const normalizedEmail = email.trim().toLowerCase();
  const user = await getUserByEmail(normalizedEmail);

  if (!user) {
    throw new Error('Email ou mot de passe incorrect.');
  }

  const passwordHash = await hashPassword(password);
  if (user.passwordHash !== passwordHash) {
    throw new Error('Email ou mot de passe incorrect.');
  }

  localStorage.setItem(SESSION_KEY, user.id);
  return user;
}

export function logoutUser(): void {
  localStorage.removeItem(SESSION_KEY);
}

export async function getCurrentUser(): Promise<UserProfile | null> {
  const userId = localStorage.getItem(SESSION_KEY);
  if (!userId) {
    return null;
  }
  return (await getUserById(userId)) ?? null;
}

export async function updateUserName(userId: string, name: string): Promise<UserProfile> {
  const user = await getUserById(userId);
  if (!user) {
    throw new Error('Utilisateur introuvable.');
  }

  const updated = { ...user, name: name.trim() };
  await saveUser(updated);
  return updated;
}
