import { useEffect, useRef, useState } from 'react';
import { loadPdfBlob } from '../../services/documentImportService';
import { loadPdfFromArrayBuffer, renderPageToCanvas } from '../../services/pdfService';
import type { PDFDocumentProxy } from 'pdfjs-dist';
import './PdfViewer.css';

interface PdfViewerProps {
  documentId: string;
  hasPdfBlob: boolean;
  title: string;
  pageCount: number;
  currentPage: number;
  isScanned: boolean;
  pdfSource: string;
}

export function PdfViewer({
  documentId,
  hasPdfBlob,
  title,
  pageCount,
  currentPage,
  isScanned,
  pdfSource,
}: PdfViewerProps) {
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const [pdf, setPdf] = useState<PDFDocumentProxy | null>(null);
  const [loading, setLoading] = useState(hasPdfBlob);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!hasPdfBlob) {
      setPdf(null);
      setLoading(false);
      return;
    }

    let cancelled = false;

    loadPdfBlob(documentId)
      .then(async (blob) => {
        if (!blob || cancelled) {
          return;
        }
        const buffer = await blob.arrayBuffer();
        const loaded = await loadPdfFromArrayBuffer(buffer);
        if (!cancelled) {
          setPdf(loaded);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setError('Impossible de charger le PDF.');
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [documentId, hasPdfBlob]);

  useEffect(() => {
    if (!pdf || !canvasRef.current) {
      return;
    }

    renderPageToCanvas(pdf, currentPage, canvasRef.current, 1.2).catch(() => {
      setError('Erreur d\'affichage de la page.');
    });
  }, [pdf, currentPage]);

  if (!hasPdfBlob) {
    return (
      <section className="pdf-viewer" aria-label="Aperçu PDF">
        <div className="pdf-viewer__toolbar">
          <span>Page {currentPage} / {pageCount}</span>
          <span className="pdf-viewer__badge pdf-viewer__badge--mock">Document démo</span>
        </div>
        <div className="pdf-viewer__page pdf-viewer__page--mock">
          <div className="pdf-viewer__page-inner">
            <p className="pdf-viewer__doc-title">{title}</p>
            <div className="pdf-viewer__lines">
              {Array.from({ length: 10 }, (_, index) => (
                <span
                  key={index}
                  className="pdf-viewer__line"
                  style={{ width: `${68 + (index % 4) * 8}%` }}
                />
              ))}
            </div>
            <p className="pdf-viewer__footnote">Importez un vrai PDF pour l&apos;aperçu complet.</p>
          </div>
        </div>
      </section>
    );
  }

  return (
    <section className="pdf-viewer" aria-label="Aperçu PDF">
      <div className="pdf-viewer__toolbar">
        <span>Page {currentPage} / {pageCount}</span>
        <span className={`pdf-viewer__badge ${isScanned ? 'pdf-viewer__badge--scan' : ''}`}>
          {pdfSource === 'ocr' ? 'OCR' : isScanned ? 'Scanné' : 'Texte natif'}
        </span>
      </div>

      <div className="pdf-viewer__page">
        {loading && <p className="pdf-viewer__status">Chargement du PDF…</p>}
        {error && <p className="pdf-viewer__status pdf-viewer__status--error">{error}</p>}
        {!loading && !error && <canvas ref={canvasRef} className="pdf-viewer__canvas" />}
      </div>
    </section>
  );
}
