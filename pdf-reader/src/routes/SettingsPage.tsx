import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { usePreferences } from '../context/PreferencesContext';
import { getHistory } from '../services/db';
import {
  getResolvedNativeVoiceName,
  isNativeTtsAvailable,
  ttsEngine,
} from '../services/ttsService';
import type { HistoryEntry } from '../types/document';
import './AuthPages.css';

export function SettingsPage() {
  const { user, logout, updateName } = useAuth();
  const { preferences, updatePreferences } = usePreferences();
  const [name, setName] = useState(user?.name ?? '');
  const [history, setHistory] = useState<HistoryEntry[]>([]);
  const [message, setMessage] = useState<string | null>(null);
  const [ttsStatus, setTtsStatus] = useState<string | null>(null);

  useEffect(() => {
    if (user) setName(user.name);
  }, [user]);

  useEffect(() => {
    getHistory(10).then(setHistory);
  }, []);

  return (
    <div className="profile-page">
      <section className="auth-card">
        <h2>Lecture audio</h2>
        <p className="profile-email">
          Voix française intégrée dans l&apos;application — rien à installer sur le téléphone.
        </p>
        <p className="profile-email">Voix : {getResolvedNativeVoiceName()}</p>
        <label>
          Vitesse : {preferences.speed.toFixed(1)}x
          <input
            type="range"
            min="0.5"
            max="1.4"
            step="0.1"
            value={preferences.speed}
            onChange={(event) => updatePreferences({ speed: Number(event.target.value) })}
          />
        </label>
        <label className="profile-checkbox">
          <input
            type="checkbox"
            checked={preferences.autoPlay}
            onChange={(event) => updatePreferences({ autoPlay: event.target.checked })}
          />
          Démarrer l&apos;audio à l&apos;ouverture d&apos;un document
        </label>
        {isNativeTtsAvailable() && (
          <>
            {ttsStatus && <p className="profile-message">{ttsStatus}</p>}
            <button
              type="button"
              onClick={() => {
                void ttsEngine.speak(
                  'Bonjour. Je lis vos documents en français, directement depuis Lumen Reader.',
                  { rate: preferences.speed, language: 'fr-FR', voiceUri: null },
                  {
                    onStart: () => setTtsStatus('Préparation de la voix intégrée…'),
                    onEnd: () => setTtsStatus('Test réussi — voix française intégrée.'),
                    onError: (msg) => setTtsStatus(msg),
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
          <button type="button" onClick={async () => { await updateName(name); setMessage('Profil mis à jour.'); }}>
            Enregistrer
          </button>
          <button type="button" className="profile-logout" onClick={logout}>Se déconnecter</button>
          {message && <p className="profile-message">{message}</p>}
        </section>
      ) : (
        <section className="auth-card">
          <h2>Compte (optionnel)</h2>
          <p className="profile-email">Connexion non obligatoire.</p>
          <Link to="/login" className="settings-link">Se connecter</Link>
          <Link to="/register" className="settings-link settings-link--secondary">Créer un compte</Link>
        </section>
      )}

      <section className="auth-card">
        <h2>Historique</h2>
        {history.length === 0 ? (
          <p className="profile-email">Aucune activité.</p>
        ) : (
          <ul className="profile-history">
            {history.map((entry) => (
              <li key={entry.id}><span>{entry.action}</span> {entry.documentTitle}</li>
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
