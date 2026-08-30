import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { usePreferences } from '../context/PreferencesContext';
import { getHistory } from '../services/db';
import {
  getNativeLanguages,
  getNativeVoicesForLanguage,
  getResolvedNativeLanguage,
  getResolvedNativeVoiceName,
  initializeNativeTts,
  isNativeTtsAvailable,
  openNativeTtsInstall,
  ttsEngine,
  type NativeVoiceOption,
} from '../services/ttsService';
import type { HistoryEntry } from '../types/document';
import './AuthPages.css';

const LANGUAGE_LABELS: Record<string, string> = {
  'fr-FR': 'Français (France)',
  'fr-fr': 'Français (France)',
  'fr-CA': 'Français (Canada)',
  'fr-BE': 'Français (Belgique)',
  'fr-CH': 'Français (Suisse)',
  'en-US': 'Anglais (États-Unis)',
  'en-GB': 'Anglais (Royaume-Uni)',
};

function formatLanguage(code: string): string {
  return LANGUAGE_LABELS[code] ?? code;
}

export function SettingsPage() {
  const { user, logout, updateName } = useAuth();
  const { preferences, updatePreferences } = usePreferences();
  const [name, setName] = useState(user?.name ?? '');
  const [history, setHistory] = useState<HistoryEntry[]>([]);
  const [message, setMessage] = useState<string | null>(null);
  const [languages, setLanguages] = useState<string[]>(['fr-FR', 'fr-CA', 'en-US']);
  const [nativeVoices, setNativeVoices] = useState<NativeVoiceOption[]>([]);
  const [resolvedLang, setResolvedLang] = useState<string | null>(null);
  const [resolvedVoice, setResolvedVoice] = useState<string | null>(null);
  const [ttsStatus, setTtsStatus] = useState<string | null>(null);
  const autoVoiceSelectedRef = useRef(false);

  useEffect(() => {
    if (user) {
      setName(user.name);
    }
  }, [user]);

  useEffect(() => {
    getHistory(10).then(setHistory);
    if (!isNativeTtsAvailable()) {
      return;
    }

    void initializeNativeTts(preferences.language)
      .then(async () => {
        const langs = await getNativeLanguages();
        setLanguages(langs.length > 0 ? langs : ['fr-FR']);
        setResolvedLang(getResolvedNativeLanguage());
        setResolvedVoice(getResolvedNativeVoiceName());

        const voices = await getNativeVoicesForLanguage(preferences.language);
        setNativeVoices(voices);

        if (voices.length > 0 && !preferences.voiceUri && !autoVoiceSelectedRef.current) {
          autoVoiceSelectedRef.current = true;
          await updatePreferences({
            voiceUri: voices[0].voiceURI,
            language: voices[0].lang.startsWith('fr') ? voices[0].lang : 'fr-FR',
          });
        }
      })
      .catch((error) => {
        setTtsStatus(
          error instanceof Error
            ? error.message
            : 'Moteur vocal non disponible sur cet appareil.',
        );
      });
  }, [preferences.language, updatePreferences]);

  useEffect(() => {
    if (!isNativeTtsAvailable()) {
      return;
    }

    void getNativeVoicesForLanguage(preferences.language).then(setNativeVoices);
  }, [preferences.language]);

  return (
    <div className="profile-page">
      <section className="auth-card">
        <h2>Lecture audio</h2>
        <p className="profile-email">
          {isNativeTtsAvailable()
            ? 'Choisissez une voix française pour une lecture naturelle.'
            : 'Synthèse vocale du navigateur.'}
        </p>
        <label>
          Langue de lecture
          <select
            value={preferences.language}
            onChange={(event) => updatePreferences({ language: event.target.value, voiceUri: null })}
          >
            {languages.slice(0, 20).map((lang) => (
              <option key={lang} value={lang}>
                {formatLanguage(lang)}
              </option>
            ))}
          </select>
        </label>
        {isNativeTtsAvailable() && nativeVoices.length > 0 && (
          <label>
            Voix
            <select
              value={preferences.voiceUri ?? nativeVoices[0]?.voiceURI ?? ''}
              onChange={(event) => updatePreferences({ voiceUri: event.target.value })}
            >
              {nativeVoices.map((voice) => (
                <option key={voice.voiceURI} value={voice.voiceURI}>
                  {voice.name}
                  {voice.localService ? '' : ' (réseau — plus naturelle)'}
                </option>
              ))}
            </select>
          </label>
        )}
        <label>
          Vitesse : {preferences.speed.toFixed(2)}x
          <input
            type="range"
            min="0.5"
            max="1.4"
            step="0.05"
            value={preferences.speed}
            onChange={(event) => updatePreferences({ speed: Number(event.target.value) })}
          />
        </label>
        <p className="profile-email">Une vitesse entre 0,85x et 1,0x sonne plus naturelle.</p>
        <label className="profile-checkbox">
          <input
            type="checkbox"
            checked={preferences.autoPlay}
            onChange={(event) => updatePreferences({ autoPlay: event.target.checked })}
          />
          Démarrer l&apos;audio automatiquement à l&apos;ouverture d&apos;un document
        </label>
        {isNativeTtsAvailable() && (
          <>
            {(resolvedVoice || resolvedLang) && (
              <p className="profile-email">
                Active : {resolvedVoice ?? 'voix par défaut'} ({formatLanguage(resolvedLang ?? 'fr-FR')})
              </p>
            )}
            {ttsStatus && <p className="profile-message">{ttsStatus}</p>}
            <button
              type="button"
              onClick={() => void openNativeTtsInstall()}
            >
              Installer / mettre à jour la voix
            </button>
            <button
              type="button"
              className="settings-link settings-link--secondary"
              onClick={() => {
                void ttsEngine.speak(
                  'Bonjour. Je lis vos documents en français, avec un rythme naturel et des pauses entre les phrases.',
                  {
                    rate: preferences.speed,
                    language: preferences.language,
                    voiceUri: preferences.voiceUri,
                  },
                  {
                    onStart: () => setTtsStatus('Test en cours…'),
                    onEnd: () => setTtsStatus('Test réussi — voix française active.'),
                    onError: (message) => setTtsStatus(message),
                  },
                );
              }}
            >
              Tester la voix
            </button>
          </>
        )}
      </section>

      {user ? (
        <section className="auth-card">
          <h2>Mon compte</h2>
          <p className="profile-email">{user.email}</p>
          <label>
            Nom
            <input value={name} onChange={(event) => setName(event.target.value)} />
          </label>
          <button
            type="button"
            onClick={async () => {
              await updateName(name);
              setMessage('Profil mis à jour.');
            }}
          >
            Enregistrer
          </button>
          <button type="button" className="profile-logout" onClick={logout}>
            Se déconnecter
          </button>
          {message && <p className="profile-message">{message}</p>}
        </section>
      ) : (
        <section className="auth-card">
          <h2>Compte (optionnel)</h2>
          <p className="profile-email">
            Connexion non obligatoire. L&apos;audio et les PDF fonctionnent sans compte.
          </p>
          <Link to="/login" className="settings-link">
            Se connecter
          </Link>
          <Link to="/register" className="settings-link settings-link--secondary">
            Créer un compte
          </Link>
        </section>
      )}

      <section className="auth-card">
        <h2>Historique</h2>
        {history.length === 0 ? (
          <p className="profile-email">Aucune activité.</p>
        ) : (
          <ul className="profile-history">
            {history.map((entry) => (
              <li key={entry.id}>
                <span>{entry.action}</span> {entry.documentTitle}
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  );
}

export function ProfilePage() {
  return <SettingsPage />;
}
