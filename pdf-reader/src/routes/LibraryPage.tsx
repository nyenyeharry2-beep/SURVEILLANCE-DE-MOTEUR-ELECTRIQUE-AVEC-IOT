import { DocumentCard } from '../components/library/DocumentCard';
import { ImportButton } from '../components/library/ImportButton';
import { useLibrary } from '../context/LibraryContext';
import './LibraryPage.css';

export function LibraryPage() {
  const { documents, loading, removeDocument } = useLibrary();

  return (
    <div className="library-page">
      <header className="library-page__header">
        <div>
          <h2>Bibliothèque</h2>
          <p>
            {loading
              ? 'Chargement…'
              : `${documents.length} document${documents.length > 1 ? 's' : ''} disponible${documents.length > 1 ? 's' : ''}`}
          </p>
        </div>
        <ImportButton variant="secondary" />
      </header>

      {documents.length === 0 ? (
        <div className="library-page__empty">
          <p>Votre bibliothèque est vide.</p>
          <ImportButton />
        </div>
      ) : (
        <div className="library-page__grid">
          {documents.map((document) => (
            <DocumentCard
              key={document.id}
              document={document}
              onDelete={document.hasPdfBlob ? () => removeDocument(document.id) : undefined}
            />
          ))}
        </div>
      )}
    </div>
  );
}
