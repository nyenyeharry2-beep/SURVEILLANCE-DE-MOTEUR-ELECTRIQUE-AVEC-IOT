import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { AiPanel } from '../components/reader/AiPanel';
import { PdfViewer } from '../components/reader/PdfViewer';
import { PlaybackControls } from '../components/reader/PlaybackControls';
import { TextPanel } from '../components/reader/TextPanel';
import { useLibrary } from '../context/LibraryContext';
import { usePreferences } from '../context/PreferencesContext';
import { useReaderPlayback } from '../hooks/useReaderPlayback';
import { estimateReadingProgress } from '../services/textProcessing';
import './ReaderPage.css';

export function ReaderPage() {
  const { id } = useParams<{ id: string }>();
  const { getDocument, updateProgress, recordOpen, getSavedReadingState } = useLibrary();
  const { preferences, updatePreferences } = usePreferences();
  const document = id ? getDocument(id) : undefined;

  const [ready, setReady] = useState(false);
  const [initialIndex, setInitialIndex] = useState(0);
  const [searchHighlightIds, setSearchHighlightIds] = useState<string[]>([]);

  const segments = document?.segments ?? [];

  useEffect(() => {
    if (!document) {
      return;
    }

    recordOpen(document);
    getSavedReadingState(document.id).then((state) => {
      if (state) {
        setInitialIndex(state.segmentIndex);
      }
      setReady(true);
    });
  }, [document, getSavedReadingState, recordOpen]);

  const handleProgress = useCallback(
    (progress: number, segmentIndex: number, page: number) => {
      if (!document) {
        return;
      }
      updateProgress(document.id, progress, segmentIndex, page);
    },
    [document, updateProgress],
  );

  const {
    playback,
    currentSegmentIndex,
    currentSegment,
    currentPage,
    play,
    pause,
    goPrevious,
    goNext,
    jumpToSegment,
  } = useReaderPlayback({
    segments,
    speed: preferences.speed,
    language: preferences.language,
    voiceUri: preferences.voiceUri,
    initialSegmentIndex: initialIndex,
    onProgress: handleProgress,
  });

  useEffect(() => {
    if (!document || segments.length === 0) {
      return;
    }
    handleProgress(
      estimateReadingProgress(currentSegmentIndex, segments.length),
      currentSegmentIndex,
      currentPage,
    );
  }, [currentSegmentIndex, currentPage, document, handleProgress, segments.length]);

  const statusLabel = useMemo(() => {
    if (playback === 'playing') {
      return 'Lecture en cours';
    }
    if (playback === 'paused') {
      return 'En pause';
    }
    return 'Prêt';
  }, [playback]);

  useEffect(() => {
    if (!ready || !preferences.autoPlay || playback !== 'idle' || segments.length === 0) {
      return;
    }
    play();
  }, [ready, preferences.autoPlay, playback, segments.length, play]);

  if (!document) {
    return (
      <div className="reader-page reader-page--empty">
        <h2>Document introuvable</h2>
        <p>Ce document n&apos;existe pas dans votre bibliothèque.</p>
        <Link to="/library">Retour à la bibliothèque</Link>
      </div>
    );
  }

  if (!ready) {
    return <p className="reader-page__loading">Chargement du lecteur…</p>;
  }

  return (
    <div className="reader-page">
      <header className="reader-page__header">
        <div>
          <Link to="/library" className="reader-page__back">
            ← Bibliothèque
          </Link>
          <h2>{document.title}</h2>
          <p>
            {document.author} · {document.pageCount} pages
            {document.isScanned ? ' · PDF scanné (OCR)' : ' · Texte natif'}
          </p>
        </div>
        <span className="reader-page__status">{statusLabel}</span>
      </header>

      <div className="reader-page__layout">
        <PdfViewer
          documentId={document.id}
          hasPdfBlob={document.hasPdfBlob}
          title={document.title}
          pageCount={document.pageCount}
          currentPage={currentPage}
          isScanned={document.isScanned}
          pdfSource={document.pdfSource}
        />
        <TextPanel
          segments={segments}
          currentSegmentIndex={currentSegmentIndex}
          searchHighlightIds={searchHighlightIds}
        />
      </div>

      <PlaybackControls
        playback={playback}
        speed={preferences.speed}
        currentSegment={currentSegmentIndex}
        totalSegments={segments.length}
        onPlay={play}
        onPause={pause}
        onPrevious={goPrevious}
        onNext={goNext}
        onSpeedChange={(speed) => updatePreferences({ speed })}
      />

      <AiPanel
        segments={segments}
        currentSegment={currentSegment}
        onJumpToSegment={(segmentId) => {
          jumpToSegment(segmentId);
          setSearchHighlightIds([segmentId]);
        }}
      />
    </div>
  );
}
