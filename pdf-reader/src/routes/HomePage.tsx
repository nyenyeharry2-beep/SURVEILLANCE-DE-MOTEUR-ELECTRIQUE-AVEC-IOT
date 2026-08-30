import { Link } from 'react-router-dom';
import { ImportButton } from '../components/library/ImportButton';
import { useLibrary } from '../context/LibraryContext';
import './HomePage.css';

export function HomePage() {
  const { documents } = useLibrary();
  const recentDocuments = documents.slice(0, 2);
  const inProgress = documents.filter((doc) => doc.progress > 0 && doc.progress < 100);

  return (
    <div className="home-page">
      <section className="hero-card">
        <div className="hero-card__content">
          <p className="hero-card__badge">Phase 2 — Interface</p>
          <h2>Lisez vos PDF avec une expérience moderne</h2>
          <p>
            Importez vos documents, parcourez votre bibliothèque et ouvrez le lecteur avec
            contrôles de lecture. Les données sont fictives pour l&apos;instant.
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
            <strong>{documents.length}</strong>
            <span>Documents</span>
          </article>
          <article>
            <strong>{inProgress.length}</strong>
            <span>En cours</span>
          </article>
          <article>
            <strong>0</strong>
            <span>PDF réels</span>
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
        <h3>Fonctionnalités à venir</h3>
        <div className="feature-grid">
          <article>
            <h4>Phase 3</h4>
            <p>Import PDF réel, affichage et extraction de texte.</p>
          </article>
          <article>
            <h4>Phase 6</h4>
            <p>Synthèse vocale avec pause, reprise et vitesse.</p>
          </article>
          <article>
            <h4>Phase 8</h4>
            <p>Questions, résumés et recherche sémantique.</p>
          </article>
        </div>
      </section>
    </div>
  );
}
