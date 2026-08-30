export interface TextSegment {
  id: string;
  type: 'title' | 'paragraph' | 'chapter';
  content: string;
  page: number;
}

export interface DocumentItem {
  id: string;
  title: string;
  author: string;
  pageCount: number;
  coverColor: string;
  progress: number;
  addedAt: string;
  segments: TextSegment[];
}

export type PlaybackState = 'idle' | 'playing' | 'paused';

export interface ReaderState {
  playback: PlaybackState;
  currentSegmentIndex: number;
  speed: number;
}
