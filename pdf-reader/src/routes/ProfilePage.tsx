import { useEffect, useState } from 'react';
import { Link, Navigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { usePreferences } from '../context/PreferencesContext';
import { getAvailableVoices } from '../services/ttsService';
import { getHistory } from '../services/db';
import type { HistoryEntry } from '../types/document';
import './AuthPages.css';

export function ProfilePage() {
  const { user, loading, logout, updateName } = useAuth();
  const { preferences, updatePreferences } = usePreferences();
  const [name, setName] = useState(user?.name ?? '');
  const [history, setHistory] = useState<HistoryEntry[]>([]);
  const [message, setMessage] = useState<string | null>(null);
  const voices = getAvailableVoices();

  useEffect(() => {
    if (user) {
      setName(user.name);
    }
  }, [user]);

  useEffect(() => {
    getHistory(20).then(setHistory);
  }, []);

  if (loading) {
    return <p className="auth-page">Chargement…</p>;
  }

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  return (
    <div className="profile-page">
      <section className="auth-card">
        <h2>Profil</h2>
        <p className="profile-email">{user.email}</p>
        <label>
          Nom affiché
          <input value={name} onChange={(event) => setName(event.target.value)} />
        </label>
        <button
          type="button"
          onClick={async () => {
            await updateName(name);
            setMessage('Profil mis à jour.');
          }}
        >
          Enregistrer le profil
        </button>
        <button type="button" className="profile-logout" onClick={logout}>
          Se déconnecter
        </button>
        {message && <p className="profile-message">{message}</p>}
      </section>

      <section className="auth-card">
        <h2>Préférences de lecture</h2>
        <label>
          Langue TTS
          <select
            value={preferences.language}
            onChange={(event) => updatePreferences({ language: event.target.value })}
          >
            <option value="fr-FR">Français</option>
            <option value="en-US">English</option>
            <option value="es-ES">Español</option>
          </select>
        </label>
        <label>
          Voix
          <select
            value={preferences.voiceUri ?? ''}
            onChange={(event) =>
              updatePreferences({ voiceUri: event.target.value || null })
            }
          >
            <option value="">Voix par défaut</option>
            {voices.map((voice) => (
              <option key={voice.voiceURI} value={voice.voiceURI}>
                {voice.name} ({voice.lang})
              </option>
            ))}
          </select>
        </label>
        <label className="profile-checkbox">
          <input
            type="checkbox"
            checked={preferences.autoPlay}
            onChange={(event) => updatePreferences({ autoPlay: event.target.checked })}
          />
          Lecture automatique à l&apos;ouverture
        </label>
      </section>

      <section className="auth-card">
        <h2>Historique</h2>
        {history.length === 0 ? (
          <p>Aucun historique.</p>
        ) : (
          <ul className="profile-history">
            {history.map((entry) => (
              <li key={entry.id}>
                <span>{entry.action}</span> {entry.documentTitle}
              </li>
            ))}
          </ul>
        )}
        <Link to="/library">Retour à la bibliothèque</Link>
      </section>
    </div>
  );
}
