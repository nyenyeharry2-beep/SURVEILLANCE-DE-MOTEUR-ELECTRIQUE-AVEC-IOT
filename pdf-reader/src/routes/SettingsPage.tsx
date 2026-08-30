import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { usePreferences } from '../context/PreferencesContext';
import { getHistory } from '../services/db';
import { getNativeLanguages, isNativeTtsAvailable } from '../services/ttsService';
import type { HistoryEntry } from '../types/document';
import './AuthPages.css';

export function SettingsPage() {
  const { user, logout, updateName } = useAuth();
  const { preferences, updatePreferences } = usePreferences();
  const [name, setName] = useState(user?.name ?? '');
  const [history, setHistory] = useState<HistoryEntry[]>([]);
  const [message, setMessage] = useState<string | null>(null);
  const [languages, setLanguages] = useState<string[]>(['fr-FR', 'en-US', 'es-ES']);

  useEffect(() => {
    if (user) {
      setName(user.name);
    }
  }, [user]);

  useEffect(() => {
    getHistory(10).then(setHistory);
    if (isNativeTtsAvailable()) {
      getNativeLanguages().then(setLanguages);
    }
  }, []);

  return (
    <div className="profile-page">
      <section className="auth-card">
        <h2>Lecture audio</h2>
        <p className="profile-email">
          {isNativeTtsAvailable()
            ? 'Voix Android intégrée — fonctionne hors ligne.'
            : 'Synthèse vocale du navigateur.'}
        </p>
        <label>
          Langue de lecture
          <select
            value={preferences.language}
            onChange={(event) => updatePreferences({ language: event.target.value })}
          >
            {languages.slice(0, 20).map((lang) => (
              <option key={lang} value={lang}>
                {lang}
              </option>
            ))}
          </select>
        </label>
        <label>
          Vitesse : {preferences.speed.toFixed(1)}x
          <input
            type="range"
            min="0.5"
            max="2"
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
          Démarrer l&apos;audio automatiquement à l&apos;ouverture d&apos;un document
        </label>
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
