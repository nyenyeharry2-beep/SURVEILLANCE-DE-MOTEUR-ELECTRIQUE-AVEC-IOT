import type { ImportProgress } from '../../types/document';
import './ImportProgressModal.css';

interface ImportProgressModalProps {
  progress: ImportProgress | null;
}

export function ImportProgressModal({ progress }: ImportProgressModalProps) {
  if (!progress || progress.stage === 'done') {
    return null;
  }

  return (
    <div className="import-modal" role="dialog" aria-modal="true" aria-label="Import en cours">
      <div className="import-modal__card">
        <h3>Import du PDF</h3>
        <p>{progress.message}</p>
        <div className="import-modal__bar">
          <span style={{ width: `${progress.progress}%` }} />
        </div>
        <span className="import-modal__percent">{progress.progress}%</span>
      </div>
    </div>
  );
}
