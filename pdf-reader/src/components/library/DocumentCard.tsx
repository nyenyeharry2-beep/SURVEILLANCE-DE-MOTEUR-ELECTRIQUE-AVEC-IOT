import { Link } from 'react-router-dom';
import type { DocumentItem } from '../../types/document';
import './DocumentCard.css';

interface DocumentCardProps {
  document: DocumentItem;
  onDelete?: () => void;
}

function formatDate(iso: string): string {
  return new Intl.DateTimeFormat('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }).format(new Date(iso));
}

export function DocumentCard({ document, onDelete }: DocumentCardProps) {
  return (
    <article className="document-card">
      <div
        className="document-card__cover"
        style={{ backgroundColor: document.coverColor }}
        aria-hidden="true"
      >
        <span>PDF</span>
      </div>

      <div className="document-card__body">
        <h3 className="document-card__title">{document.title}</h3>
        <p className="document-card__meta">
          {document.author} · {document.pageCount} pages
        </p>
        <p className="document-card__tags">
          {document.hasPdfBlob ? (
            document.isScanned ? 'OCR' : 'Texte natif'
          ) : (
            'Démo'
          )}
        </p>
        <p className="document-card__date">Ajouté le {formatDate(document.addedAt)}</p>

        <div className="document-card__progress">
          <div className="document-card__progress-bar">
            <span style={{ width: `${document.progress}%` }} />
          </div>
          <span className="document-card__progress-label">{document.progress}% lu</span>
        </div>

        <div className="document-card__actions">
          <Link to={`/reader/${document.id}`} className="document-card__button">
            Ouvrir le lecteur
          </Link>
          {onDelete && (
            <button type="button" className="document-card__delete" onClick={onDelete}>
              Supprimer
            </button>
          )}
        </div>
      </div>
    </article>
  );
}
