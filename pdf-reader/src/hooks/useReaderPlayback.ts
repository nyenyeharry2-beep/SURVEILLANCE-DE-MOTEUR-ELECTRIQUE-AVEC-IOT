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
  onProgress?: (progress: number, segmentIndex: number, page: number) => void;
}

export function useReaderPlayback({
  segments,
  speed,
  language,
  voiceUri,
  initialSegmentIndex = 0,
  onProgress,
}: UseReaderPlaybackOptions) {
  const [playback, setPlayback] = useState<PlaybackState>('idle');
  const [currentSegmentIndex, setCurrentSegmentIndex] = useState(initialSegmentIndex);
  const segmentsRef = useRef(segments);

  useEffect(() => {
    segmentsRef.current = segments;
  }, [segments]);

  useEffect(() => {
    setCurrentSegmentIndex(initialSegmentIndex);
  }, [initialSegmentIndex]);

  const speakSegment = useCallback(
    (index: number) => {
      const segment = segmentsRef.current[index];
      if (!segment) {
        setPlayback('idle');
        return;
      }

      ttsEngine.speak(
        segment.content,
        { rate: speed, language, voiceUri },
        {
          onEnd: () => {
            const nextIndex = index + 1;
            if (nextIndex >= segmentsRef.current.length) {
              setPlayback('idle');
              onProgress?.(
                100,
                index,
                segment.page,
              );
              return;
            }

            setCurrentSegmentIndex(nextIndex);
            onProgress?.(
              estimateReadingProgress(nextIndex, segmentsRef.current.length),
              nextIndex,
              segmentsRef.current[nextIndex]?.page ?? segment.page,
            );
            speakSegment(nextIndex);
          },
          onError: () => setPlayback('idle'),
        },
      );
    },
    [speed, language, voiceUri, onProgress],
  );

  const play = useCallback(() => {
    if (segmentsRef.current.length === 0) {
      return;
    }

    if (ttsEngine.isPaused()) {
      ttsEngine.resume();
      setPlayback('playing');
      return;
    }

    setPlayback('playing');
    speakSegment(currentSegmentIndex);
  }, [currentSegmentIndex, speakSegment]);

  const pause = useCallback(() => {
    ttsEngine.pause();
    setPlayback('paused');
  }, []);

  const stop = useCallback(() => {
    ttsEngine.stop();
    setPlayback('idle');
  }, []);

  const goPrevious = useCallback(() => {
    stop();
    setCurrentSegmentIndex((index) => Math.max(0, index - 1));
  }, [stop]);

  const goNext = useCallback(() => {
    stop();
    setCurrentSegmentIndex((index) =>
      Math.min(segmentsRef.current.length - 1, index + 1),
    );
  }, [stop]);

  const jumpToSegment = useCallback(
    (segmentId: string) => {
      const index = segmentsRef.current.findIndex((segment) => segment.id === segmentId);
      if (index >= 0) {
        stop();
        setCurrentSegmentIndex(index);
      }
    },
    [stop],
  );

  useEffect(() => {
    return () => ttsEngine.stop();
  }, []);

  useEffect(() => {
    if (playback === 'playing' && ttsEngine.isSpeaking()) {
      ttsEngine.stop();
      speakSegment(currentSegmentIndex);
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
    play,
    pause,
    stop,
    goPrevious,
    goNext,
    jumpToSegment,
    setCurrentSegmentIndex,
  };
}
