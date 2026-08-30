import './PdfViewerPlaceholder.css';

interface PdfViewerPlaceholderProps {
  title: string;
  pageCount: number;
  currentPage: number;
}

export function PdfViewerPlaceholder({
  title,
  pageCount,
  currentPage,
}: PdfViewerPlaceholderProps) {
  return (
    <section className="pdf-viewer" aria-label="Aperçu PDF">
      <div className="pdf-viewer__toolbar">
        <span>Page {currentPage} / {pageCount}</span>
        <span className="pdf-viewer__badge">Aperçu fictif</span>
      </div>

      <div className="pdf-viewer__page">
        <div className="pdf-viewer__page-inner">
          <p className="pdf-viewer__doc-title">{title}</p>
          <div className="pdf-viewer__lines">
            {Array.from({ length: 12 }, (_, index) => (
              <span
                key={index}
                className="pdf-viewer__line"
                style={{ width: `${70 + (index % 4) * 7}%` }}
              />
            ))}
          </div>
          <p className="pdf-viewer__footnote">
            Le rendu PDF réel sera intégré en Phase 3 avec PDF.js.
          </p>
        </div>
      </div>
    </section>
  );
}
