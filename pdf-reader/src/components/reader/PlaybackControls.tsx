import type { PlaybackState } from '../../types/document';
import './PlaybackControls.css';

interface PlaybackControlsProps {
  playback: PlaybackState;
  speed: number;
  currentSegment: number;
  totalSegments: number;
  onPlay: () => void;
  onPause: () => void;
  onPrevious: () => void;
  onNext: () => void;
  onSpeedChange: (speed: number) => void;
}

export function PlaybackControls({
  playback,
  speed,
  currentSegment,
  totalSegments,
  onPlay,
  onPause,
  onPrevious,
  onNext,
  onSpeedChange,
}: PlaybackControlsProps) {
  const progress = totalSegments > 0 ? ((currentSegment + 1) / totalSegments) * 100 : 0;
  const isPlaying = playback === 'playing';

  return (
    <section className="playback-controls" aria-label="Contrôles de lecture">
      <div className="playback-controls__progress">
        <div className="playback-controls__progress-bar">
          <span style={{ width: `${progress}%` }} />
        </div>
        <p className="playback-controls__progress-text">
          Segment {Math.min(currentSegment + 1, totalSegments)} / {totalSegments}
        </p>
      </div>

      <div className="playback-controls__actions">
        <button type="button" className="playback-controls__btn" onClick={onPrevious}>
          Précédent
        </button>

        {isPlaying ? (
          <button
            type="button"
            className="playback-controls__btn playback-controls__btn--primary"
            onClick={onPause}
          >
            Pause
          </button>
        ) : (
          <button
            type="button"
            className="playback-controls__btn playback-controls__btn--primary"
            onClick={onPlay}
          >
            Lire
          </button>
        )}

        <button type="button" className="playback-controls__btn" onClick={onNext}>
          Suivant
        </button>
      </div>

      <label className="playback-controls__speed">
        <span>Vitesse : {speed.toFixed(1)}x</span>
        <input
          type="range"
          min="0.5"
          max="2"
          step="0.1"
          value={speed}
          onChange={(event) => onSpeedChange(Number(event.target.value))}
        />
      </label>

      <p className="playback-controls__hint">
        Phase 2 : simulation visuelle. La synthèse vocale arrive en Phase 6.
      </p>
    </section>
  );
}
