import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { usePreferences } from '../context/PreferencesContext';
import { getHistory } from '../services/db';
import {
  BUNDLED_FRENCH_VOICES,
  DEFAULT_PIPER_VOICE,
  getResolvedNativeVoiceName,
  initializeNativeTts,
  isNativeTtsAvailable,
  ttsEngine,
  type PiperInitProgress,
} from '../services/ttsService';
import type { HistoryEntry } from '../types/document';
import './AuthPages.css';

export function SettingsPage() {
  const { user, logout, updateName } = useAuth();
  const { preferences, updatePreferences } = usePreferences();
  const [name, setName] = useState(user?.name ?? '');
  const [history, setHistory] = useState<HistoryEntry[]>([]);
  const [message, setMessage] = useState<string | null>(null);
  const [initMessage, setInitMessage] = useState<string | null>(null);
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

    void initializeNativeTts(preferences.language, (progress: PiperInitProgress) => {
      setInitMessage(progress.message);
    })
      .then(async () => {
        setInitMessage(null);
        if (!preferences.voiceUri && !autoVoiceSelectedRef.current) {
          autoVoiceSelectedRef.current = true;
          await updatePreferences({
            voiceUri: DEFAULT_PIPER_VOICE,
            language: 'fr-FR',
          });
        }
      })
      .catch((error) => {
        setTtsStatus(
          error instanceof Error
            ? error.message
            : 'Impossible de préparer la voix intégrée.',
        );
      });
  }, [preferences.language, updatePreferences]);

  return (
    <div className="profile-page">
      <section className="auth-card">
        <h2>Lecture audio</h2>
        <p className="profile-email">
          Voix française intégrée dans l&apos;application — aucun téléchargement externe requis.
        </p>
        {initMessage && <p className="profile-message">{initMessage}</p>}
        <label>
          Voix
          <select
            value={preferences.voiceUri ?? DEFAULT_PIPER_VOICE}
            onChange={(event) =>
              updatePreferences({ voiceUri: event.target.value, language: 'fr-FR' })
            }
          >
            {BUNDLED_FRENCH_VOICES.map((voice) => (
              <option key={voice.id} value={voice.id}>
                {voice.label}
              </option>
            ))}
          </select>
        </label>
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
            <p className="profile-email">Active : {getResolvedNativeVoiceName()}</p>
            {ttsStatus && <p className="profile-message">{ttsStatus}</p>}
            <button
              type="button"
              onClick={() => {
                void ttsEngine.speak(
                  'Bonjour. Je lis vos documents en français, avec une voix naturelle intégrée directement dans Lumen Reader.',
                  {
                    rate: preferences.speed,
                    language: 'fr-FR',
                    voiceUri: preferences.voiceUri ?? DEFAULT_PIPER_VOICE,
                  },
                  {
                    onStart: () => setTtsStatus('Test en cours…'),
                    onEnd: () => setTtsStatus('Test réussi — voix française intégrée.'),
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
