import { useState } from 'react';
import {
  askDocument,
  explainSegment,
  hasAiApiKey,
  semanticSearch,
  summarizeDocument,
} from '../../services/aiService';
import type { TextSegment } from '../../types/document';
import './AiPanel.css';

interface AiPanelProps {
  segments: TextSegment[];
  currentSegment: TextSegment | undefined;
  onJumpToSegment: (segmentId: string) => void;
}

export function AiPanel({ segments, currentSegment, onJumpToSegment }: AiPanelProps) {
  const [question, setQuestion] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [loading, setLoading] = useState(false);
  const [response, setResponse] = useState('');
  const [searchResults, setSearchResults] = useState<TextSegment[]>([]);

  const runAction = async (action: () => Promise<string | TextSegment[]>) => {
    setLoading(true);
    try {
      const result = await action();
      if (Array.isArray(result)) {
        setSearchResults(result);
        setResponse(`${result.length} passage(s) trouvé(s).`);
      } else {
        setResponse(result);
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <section className="ai-panel" aria-label="Assistant IA">
      <div className="ai-panel__header">
        <h3>Assistant IA</h3>
        <span>{hasAiApiKey() ? 'API connectée' : 'Mode local'}</span>
      </div>

      <div className="ai-panel__actions">
        <button
          type="button"
          disabled={loading}
          onClick={() => runAction(() => summarizeDocument(segments))}
        >
          Résumé
        </button>
        <button
          type="button"
          disabled={loading || !currentSegment}
          onClick={() =>
            currentSegment
              ? runAction(() => explainSegment(currentSegment))
              : Promise.resolve('')
          }
        >
          Expliquer le passage
        </button>
      </div>

      <form
        className="ai-panel__form"
        onSubmit={(event) => {
          event.preventDefault();
          if (!question.trim()) {
            return;
          }
          runAction(() => askDocument(question, segments));
        }}
      >
        <input
          value={question}
          onChange={(event) => setQuestion(event.target.value)}
          placeholder="Posez une question sur le document…"
        />
        <button type="submit" disabled={loading}>
          Question
        </button>
      </form>

      <form
        className="ai-panel__form"
        onSubmit={(event) => {
          event.preventDefault();
          if (!searchQuery.trim()) {
            return;
          }
          runAction(() => semanticSearch(searchQuery, segments));
        }}
      >
        <input
          value={searchQuery}
          onChange={(event) => setSearchQuery(event.target.value)}
          placeholder="Recherche sémantique…"
        />
        <button type="submit" disabled={loading}>
          Rechercher
        </button>
      </form>

      {loading && <p className="ai-panel__loading">Analyse en cours…</p>}

      {response && <div className="ai-panel__response">{response}</div>}

      {searchResults.length > 0 && (
        <ul className="ai-panel__results">
          {searchResults.map((segment) => (
            <li key={segment.id}>
              <button type="button" onClick={() => onJumpToSegment(segment.id)}>
                p.{segment.page} — {segment.content.slice(0, 120)}
                {segment.content.length > 120 ? '…' : ''}
              </button>
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}
