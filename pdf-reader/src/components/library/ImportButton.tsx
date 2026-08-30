import { useRef, useState, type ChangeEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { useLibrary } from '../../context/LibraryContext';
import type { ImportProgress } from '../../types/document';
import { ImportProgressModal } from './ImportProgressModal';
import './ImportButton.css';

interface ImportButtonProps {
  variant?: 'primary' | 'secondary';
}

export function ImportButton({ variant = 'primary' }: ImportButtonProps) {
  const fileInputRef = useRef<HTMLInputElement>(null);
  const { importPdf } = useLibrary();
  const navigate = useNavigate();
  const [progress, setProgress] = useState<ImportProgress | null>(null);
  const [error, setError] = useState<string | null>(null);

  const handleClick = () => {
    fileInputRef.current?.click();
  };

  const handleFileChange = async (event: ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) {
      return;
    }

    setError(null);
    setProgress({
      stage: 'loading',
      message: 'Préparation…',
      progress: 0,
    });

    try {
      const imported = await importPdf(file, setProgress);
      navigate(`/reader/${imported.id}`);
    } catch (importError) {
      setError(
        importError instanceof Error
          ? importError.message
          : 'Erreur lors de l\'import du PDF.',
      );
    } finally {
      setProgress(null);
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    }
  };

  return (
    <>
      <button
        type="button"
        className={`import-button import-button--${variant}`}
        onClick={handleClick}
        disabled={Boolean(progress && progress.stage !== 'done')}
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
      {error && <p className="import-button__error">{error}</p>}
      <ImportProgressModal progress={progress} />
    </>
  );
}
