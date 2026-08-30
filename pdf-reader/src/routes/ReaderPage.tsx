import { useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PlaybackControls } from '../components/reader/PlaybackControls';
import { PdfViewerPlaceholder } from '../components/reader/PdfViewerPlaceholder';
import { TextPanel } from '../components/reader/TextPanel';
import { useLibrary } from '../context/LibraryContext';
import type { PlaybackState } from '../types/document';
import './ReaderPage.css';

export function ReaderPage() {
  const { id } = useParams<{ id: string }>();
  const { getDocument, updateProgress } = useLibrary();
  const document = id ? getDocument(id) : undefined;

  const [playback, setPlayback] = useState<PlaybackState>('idle');
  const [currentSegmentIndex, setCurrentSegmentIndex] = useState(0);
  const [speed, setSpeed] = useState(1);

  const segments = document?.segments ?? [];
  const totalSegments = segments.length;

  const currentPage = useMemo(() => {
    return segments[currentSegmentIndex]?.page ?? 1;
  }, [segments, currentSegmentIndex]);

  useEffect(() => {
    if (!document || totalSegments === 0) {
      return;
    }

    const progress = Math.round(((currentSegmentIndex + 1) / totalSegments) * 100);
    updateProgress(document.id, progress);
  }, [currentSegmentIndex, document, totalSegments, updateProgress]);

  useEffect(() => {
    if (playback !== 'playing' || totalSegments === 0) {
      return;
    }

    const intervalMs = Math.max(800, 2800 / speed);
    const timer = window.setInterval(() => {
      setCurrentSegmentIndex((index) => {
        if (index >= totalSegments - 1) {
          setPlayback('idle');
          return index;
        }
        return index + 1;
      });
    }, intervalMs);

    return () => window.clearInterval(timer);
  }, [playback, speed, totalSegments]);

  if (!document) {
    return (
      <div className="reader-page reader-page--empty">
        <h2>Document introuvable</h2>
        <p>Ce document n&apos;existe pas dans la bibliothèque fictive.</p>
        <Link to="/library">Retour à la bibliothèque</Link>
      </div>
    );
  }

  const handlePlay = () => setPlayback('playing');
  const handlePause = () => setPlayback('paused');

  const handlePrevious = () => {
    setPlayback('paused');
    setCurrentSegmentIndex((index) => Math.max(0, index - 1));
  };

  const handleNext = () => {
    setPlayback('paused');
    setCurrentSegmentIndex((index) => Math.min(totalSegments - 1, index + 1));
  };

  return (
    <div className="reader-page">
      <header className="reader-page__header">
        <div>
          <Link to="/library" className="reader-page__back">
            ← Bibliothèque
          </Link>
          <h2>{document.title}</h2>
          <p>{document.author} · {document.pageCount} pages</p>
        </div>
        <span className="reader-page__status">
          {playback === 'playing' ? 'Lecture simulée' : playback === 'paused' ? 'En pause' : 'Prêt'}
        </span>
      </header>

      <div className="reader-page__layout">
        <PdfViewerPlaceholder
          title={document.title}
          pageCount={document.pageCount}
          currentPage={currentPage}
        />
        <TextPanel segments={segments} currentSegmentIndex={currentSegmentIndex} />
      </div>

      <PlaybackControls
        playback={playback}
        speed={speed}
        currentSegment={currentSegmentIndex}
        totalSegments={totalSegments}
        onPlay={handlePlay}
        onPause={handlePause}
        onPrevious={handlePrevious}
        onNext={handleNext}
        onSpeedChange={setSpeed}
      />
    </div>
  );
}
