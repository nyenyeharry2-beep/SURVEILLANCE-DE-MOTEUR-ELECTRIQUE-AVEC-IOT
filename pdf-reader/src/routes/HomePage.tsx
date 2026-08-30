import { Link } from 'react-router-dom';
import { ImportButton } from '../components/library/ImportButton';
import { useAuth } from '../context/AuthContext';
import { useLibrary } from '../context/LibraryContext';
import { getHistory } from '../services/db';
import { useEffect, useState } from 'react';
import type { HistoryEntry } from '../types/document';
import './HomePage.css';

export function HomePage() {
  const { documents, loading } = useLibrary();
  const { user } = useAuth();
  const [history, setHistory] = useState<HistoryEntry[]>([]);

  const recentDocuments = documents.slice(0, 2);
  const inProgress = documents.filter((doc) => doc.progress > 0 && doc.progress < 100);
  const realPdfCount = documents.filter((doc) => doc.hasPdfBlob).length;

  useEffect(() => {
    getHistory(5).then(setHistory);
  }, [documents]);

  return (
    <div className="home-page">
      <section className="hero-card">
        <div className="hero-card__content">
          <p className="hero-card__badge">Lumen Reader — Complet</p>
          <h2>Lisez, écoutez et comprenez vos PDF</h2>
          <p>
            Importez un PDF, extrayez le texte (OCR si scanné), écoutez avec synthèse vocale,
            reprenez votre progression et utilisez l&apos;assistant IA.
          </p>
          <div className="hero-card__actions">
            <ImportButton />
            <Link to="/library" className="hero-card__secondary">
              Voir la bibliothèque
            </Link>
          </div>
        </div>

        <div className="hero-card__stats">
          <article>
            <strong>{loading ? '…' : documents.length}</strong>
            <span>Documents</span>
          </article>
          <article>
            <strong>{inProgress.length}</strong>
            <span>En cours</span>
          </article>
          <article>
            <strong>{realPdfCount}</strong>
            <span>PDF importés</span>
          </article>
        </div>
      </section>

      <section className="home-section">
        <div className="home-section__header">
          <h3>Reprendre la lecture</h3>
          <Link to="/library">Tout voir</Link>
        </div>

        {recentDocuments.length === 0 ? (
          <p className="home-empty">Aucun document pour le moment.</p>
        ) : (
          <div className="home-recent">
            {recentDocuments.map((doc) => (
              <Link key={doc.id} to={`/reader/${doc.id}`} className="home-recent__item">
                <span
                  className="home-recent__cover"
                  style={{ backgroundColor: doc.coverColor }}
                />
                <div>
                  <strong>{doc.title}</strong>
                  <p>{doc.progress}% lu · {doc.pageCount} pages</p>
                </div>
              </Link>
            ))}
          </div>
        )}
      </section>

      <section className="home-section">
        <div className="home-section__header">
          <h3>Historique récent</h3>
          {user && <Link to="/profile">Profil</Link>}
        </div>
        {history.length === 0 ? (
          <p className="home-empty">Aucune activité enregistrée.</p>
        ) : (
          <ul className="home-history">
            {history.map((entry) => (
              <li key={entry.id}>
                <span>{entry.action}</span>
                <strong>{entry.documentTitle}</strong>
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  );
}
