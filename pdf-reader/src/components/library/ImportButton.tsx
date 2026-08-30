import { useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { useLibrary } from '../../context/LibraryContext';
import './ImportButton.css';

interface ImportButtonProps {
  variant?: 'primary' | 'secondary';
}

export function ImportButton({ variant = 'primary' }: ImportButtonProps) {
  const fileInputRef = useRef<HTMLInputElement>(null);
  const { importMockDocument } = useLibrary();
  const navigate = useNavigate();

  const handleClick = () => {
    fileInputRef.current?.click();
  };

  const handleFileChange = () => {
    const newDoc = importMockDocument();
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
    navigate(`/reader/${newDoc.id}`);
  };

  return (
    <>
      <button
        type="button"
        className={`import-button import-button--${variant}`}
        onClick={handleClick}
      >
        <span className="import-button__icon" aria-hidden="true">
          +
        </span>
        Importer un PDF
      </button>
      <input
        ref={fileInputRef}
        type="file"
        accept=".pdf,application/pdf"
        className="import-button__input"
        onChange={handleFileChange}
        aria-label="Sélectionner un fichier PDF"
      />
      <p className="import-button__hint">
        Phase 2 : import simulé avec données fictives. L&apos;extraction PDF arrive en Phase 3.
      </p>
    </>
  );
}
