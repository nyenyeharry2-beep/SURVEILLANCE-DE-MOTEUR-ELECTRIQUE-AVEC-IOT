import type { TextSegment } from '../../types/document';
import './TextPanel.css';

interface TextPanelProps {
  segments: TextSegment[];
  currentSegmentIndex: number;
}

export function TextPanel({ segments, currentSegmentIndex }: TextPanelProps) {
  return (
    <section className="text-panel" aria-label="Texte extrait">
      <div className="text-panel__header">
        <h2>Texte</h2>
        <span>{segments.length} segments</span>
      </div>

      <div className="text-panel__content">
        {segments.map((segment, index) => {
          const isActive = index === currentSegmentIndex;
          return (
            <p
              key={segment.id}
              className={[
                'text-panel__segment',
                `text-panel__segment--${segment.type}`,
                isActive ? 'text-panel__segment--active' : '',
              ]
                .filter(Boolean)
                .join(' ')}
              aria-current={isActive ? 'true' : undefined}
            >
              <span className="text-panel__page">p.{segment.page}</span>
              {segment.content}
            </p>
          );
        })}
      </div>
    </section>
  );
}
