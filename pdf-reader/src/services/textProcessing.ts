import type { TextSegment } from '../types/document';
import type { PageText } from './pdfService';

const CHAPTER_PATTERN =
  /^(chapitre|chapter|partie|section|livre|part)\s+[\divxlc\d\s\-–—.:]+/i;
const TITLE_PATTERN = /^.{3,90}$/;

function isNoise(line: string): boolean {
  const trimmed = line.trim();
  if (!trimmed) {
    return true;
  }
  if (/^\d+$/.test(trimmed)) {
    return true;
  }
  if (/^page\s+\d+/i.test(trimmed)) {
    return true;
  }
  if (trimmed.length <= 2) {
    return true;
  }
  return false;
}

function detectSegmentType(content: string): TextSegment['type'] {
  const trimmed = content.trim();

  if (CHAPTER_PATTERN.test(trimmed)) {
    return 'chapter';
  }

  if (
    trimmed.length <= 80 &&
    (trimmed === trimmed.toUpperCase() ||
      /^(\d+[\.\)]\s+).{3,80}$/.test(trimmed) ||
      TITLE_PATTERN.test(trimmed))
  ) {
    return 'title';
  }

  return 'paragraph';
}

function cleanPageText(text: string): string {
  return text
    .replace(/\f/g, '\n')
    .replace(/-\s+/g, '')
    .replace(/\s+/g, ' ')
    .trim();
}

function splitIntoBlocks(text: string): string[] {
  const byParagraph = text
    .split(/\n{2,}|(?<=[.!?…])\s+(?=[A-ZÀÂÄÉÈÊËÎÏÔÖÙÛÜÇ"«])/)
    .map((block) => block.trim())
    .filter(Boolean);

  if (byParagraph.length > 1) {
    return byParagraph;
  }

  return text
    .split(/(?<=[.!?…])\s+/)
    .reduce<string[]>((chunks, sentence, index, array) => {
      if (index % 2 === 0) {
        const pair = [sentence, array[index + 1]].filter(Boolean).join(' ');
        if (pair.trim()) {
          chunks.push(pair.trim());
        }
      }
      return chunks;
    }, []);
}

export function processPagesToSegments(pages: PageText[]): TextSegment[] {
  const segments: TextSegment[] = [];
  let counter = 0;

  for (const page of pages) {
    const cleaned = cleanPageText(page.text);
    if (!cleaned) {
      continue;
    }

    const blocks = splitIntoBlocks(cleaned);

    for (const block of blocks) {
      if (isNoise(block)) {
        continue;
      }

      segments.push({
        id: `seg-${++counter}`,
        type: detectSegmentType(block),
        content: block,
        page: page.pageNumber,
      });
    }
  }

  if (segments.length === 0 && pages.length > 0) {
    segments.push({
      id: 'seg-1',
      type: 'paragraph',
      content: 'Aucun texte exploitable n\'a pu être extrait de ce document.',
      page: 1,
    });
  }

  return segments;
}

export function estimateReadingProgress(
  segmentIndex: number,
  totalSegments: number,
): number {
  if (totalSegments <= 0) {
    return 0;
  }
  return Math.min(100, Math.round(((segmentIndex + 1) / totalSegments) * 100));
}
