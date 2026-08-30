import { useCallback, useEffect, useRef, useState } from 'react';
import { ttsEngine } from '../services/ttsService';
import type { PlaybackState, TextSegment } from '../types/document';
import { estimateReadingProgress } from '../services/textProcessing';

interface UseReaderPlaybackOptions {
  segments: TextSegment[];
  speed: number;
  language: string;
  voiceUri: string | null;
  initialSegmentIndex?: number;
  autoStart?: boolean;
  onProgress?: (progress: number, segmentIndex: number, page: number) => void;
}

export function useReaderPlayback({
  segments,
  speed,
  language,
  voiceUri,
  initialSegmentIndex = 0,
  autoStart = false,
  onProgress,
}: UseReaderPlaybackOptions) {
  const [playback, setPlayback] = useState<PlaybackState>('idle');
  const [currentSegmentIndex, setCurrentSegmentIndex] = useState(initialSegmentIndex);
  const [ttsError, setTtsError] = useState<string | null>(null);
  const segmentsRef = useRef(segments);
  const autoStartedRef = useRef(false);

  useEffect(() => {
    segmentsRef.current = segments;
  }, [segments]);

  useEffect(() => {
    setCurrentSegmentIndex(initialSegmentIndex);
  }, [initialSegmentIndex]);

  const speakSegment = useCallback(
    async (index: number) => {
      const segment = segmentsRef.current[index];
      if (!segment) {
        setPlayback('idle');
        return;
      }

      setTtsError(null);

      await ttsEngine.speak(
        segment.content,
        { rate: speed, language, voiceUri },
        {
          onEnd: () => {
            const nextIndex = index + 1;
            if (nextIndex >= segmentsRef.current.length) {
              setPlayback('idle');
              onProgress?.(100, index, segment.page);
              return;
            }

            setCurrentSegmentIndex(nextIndex);
            onProgress?.(
              estimateReadingProgress(nextIndex, segmentsRef.current.length),
              nextIndex,
              segmentsRef.current[nextIndex]?.page ?? segment.page,
            );
            void speakSegment(nextIndex);
          },
          onError: (message) => {
            setTtsError(message);
            setPlayback('idle');
          },
        },
      );
    },
    [speed, language, voiceUri, onProgress],
  );

  const play = useCallback(async () => {
    if (segmentsRef.current.length === 0) {
      setTtsError('Aucun texte à lire dans ce document.');
      return;
    }

    if (ttsEngine.isPaused()) {
      await ttsEngine.resume();
      if (!ttsEngine.isSpeaking()) {
        setPlayback('playing');
        await speakSegment(currentSegmentIndex);
        return;
      }
      setPlayback('playing');
      return;
    }

    setPlayback('playing');
    await speakSegment(currentSegmentIndex);
  }, [currentSegmentIndex, speakSegment]);

  const pause = useCallback(async () => {
    await ttsEngine.pause();
    setPlayback('paused');
  }, []);

  const stop = useCallback(async () => {
    await ttsEngine.stop();
    setPlayback('idle');
  }, []);

  const goPrevious = useCallback(async () => {
    await stop();
    setCurrentSegmentIndex((index) => Math.max(0, index - 1));
  }, [stop]);

  const goNext = useCallback(async () => {
    await stop();
    setCurrentSegmentIndex((index) =>
      Math.min(segmentsRef.current.length - 1, index + 1),
    );
  }, [stop]);

  const jumpToSegment = useCallback(
    async (segmentId: string) => {
      const index = segmentsRef.current.findIndex((segment) => segment.id === segmentId);
      if (index >= 0) {
        await stop();
        setCurrentSegmentIndex(index);
      }
    },
    [stop],
  );

  useEffect(() => {
    return () => {
      void ttsEngine.stop();
    };
  }, []);

  useEffect(() => {
    if (!autoStart || autoStartedRef.current || segments.length === 0) {
      return;
    }
    autoStartedRef.current = true;
    void play();
  }, [autoStart, segments.length, play]);

  useEffect(() => {
    if (playback === 'playing' && ttsEngine.isSpeaking()) {
      void ttsEngine.stop().then(() => speakSegment(currentSegmentIndex));
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [speed, language, voiceUri]);

  const currentSegment = segments[currentSegmentIndex];
  const currentPage = currentSegment?.page ?? 1;

  return {
    playback,
    currentSegmentIndex,
    currentSegment,
    currentPage,
    ttsError,
    play,
    pause,
    stop,
    goPrevious,
    goNext,
    jumpToSegment,
    setCurrentSegmentIndex,
  };
}
