import type { TextSegment } from '../types/document';

const OPENAI_API_KEY = import.meta.env.VITE_OPENAI_API_KEY as string | undefined;

function buildDocumentContext(segments: TextSegment[], maxChars = 12000): string {
  return segments
    .map((segment) => segment.content)
    .join('\n\n')
    .slice(0, maxChars);
}

async function callOpenAi(
  systemPrompt: string,
  userPrompt: string,
): Promise<string | null> {
  if (!OPENAI_API_KEY) {
    return null;
  }

  const response = await fetch('https://api.openai.com/v1/chat/completions', {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${OPENAI_API_KEY}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      model: 'gpt-4o-mini',
      messages: [
        { role: 'system', content: systemPrompt },
        { role: 'user', content: userPrompt },
      ],
      temperature: 0.4,
    }),
  });

  if (!response.ok) {
    return null;
  }

  const data = (await response.json()) as {
    choices?: Array<{ message?: { content?: string } }>;
  };

  return data.choices?.[0]?.message?.content?.trim() ?? null;
}

function fallbackSummary(segments: TextSegment[]): string {
  const paragraphs = segments
    .filter((segment) => segment.type !== 'chapter')
    .slice(0, 8)
    .map((segment) => segment.content);

  if (paragraphs.length === 0) {
    return 'Aucun contenu disponible pour générer un résumé.';
  }

  return `Résumé local :\n\n${paragraphs.join(' ').slice(0, 900)}…`;
}

function fallbackAnswer(question: string, segments: TextSegment[]): string {
  const terms = question
    .toLowerCase()
    .split(/\W+/)
    .filter((term) => term.length > 3);

  const matches = segments.filter((segment) => {
    const content = segment.content.toLowerCase();
    return terms.some((term) => content.includes(term));
  });

  if (matches.length === 0) {
    return 'Je n\'ai pas trouvé de passage pertinent dans le document. Ajoutez VITE_OPENAI_API_KEY pour des réponses plus intelligentes.';
  }

  return matches
    .slice(0, 3)
    .map((segment, index) => `${index + 1}. (p.${segment.page}) ${segment.content}`)
    .join('\n\n');
}

function fallbackExplain(segment: TextSegment): string {
  return `Explication locale :\n\nCe passage (${segment.type}, page ${segment.page}) développe l'idée suivante : ${segment.content}`;
}

function fallbackSemanticSearch(query: string, segments: TextSegment[]): TextSegment[] {
  const terms = query.toLowerCase().split(/\W+/).filter(Boolean);

  return segments
    .map((segment) => {
      const content = segment.content.toLowerCase();
      const score = terms.reduce(
        (sum, term) => sum + (content.includes(term) ? 1 : 0),
        0,
      );
      return { segment, score };
    })
    .filter((item) => item.score > 0)
    .sort((a, b) => b.score - a.score)
    .slice(0, 8)
    .map((item) => item.segment);
}

export async function summarizeDocument(segments: TextSegment[]): Promise<string> {
  const context = buildDocumentContext(segments);
  const ai = await callOpenAi(
    'Tu es un assistant de lecture. Résume le document en français, en 5-8 phrases claires.',
    context,
  );
  return ai ?? fallbackSummary(segments);
}

export async function askDocument(
  question: string,
  segments: TextSegment[],
): Promise<string> {
  const context = buildDocumentContext(segments);
  const ai = await callOpenAi(
    'Tu réponds uniquement à partir du document fourni. Si l\'information manque, dis-le clairement.',
    `Document:\n${context}\n\nQuestion: ${question}`,
  );
  return ai ?? fallbackAnswer(question, segments);
}

export async function explainSegment(segment: TextSegment): Promise<string> {
  const ai = await callOpenAi(
    'Explique le passage en français simplement, en 3-5 phrases.',
    segment.content,
  );
  return ai ?? fallbackExplain(segment);
}

export async function semanticSearch(
  query: string,
  segments: TextSegment[],
): Promise<TextSegment[]> {
  if (!OPENAI_API_KEY) {
    return fallbackSemanticSearch(query, segments);
  }

  const ai = await callOpenAi(
    'Retourne une liste de phrases exactes du document qui répondent à la recherche. Une phrase par ligne.',
    `Document:\n${buildDocumentContext(segments, 8000)}\n\nRecherche: ${query}`,
  );

  if (!ai) {
    return fallbackSemanticSearch(query, segments);
  }

  const lines = ai.split('\n').map((line) => line.trim()).filter(Boolean);
  const results: TextSegment[] = [];

  for (const line of lines) {
    const match = segments.find((segment) => segment.content.includes(line.slice(0, 40)));
    if (match && !results.some((item) => item.id === match.id)) {
      results.push(match);
    }
  }

  return results.length > 0 ? results : fallbackSemanticSearch(query, segments);
}

export function hasAiApiKey(): boolean {
  return Boolean(OPENAI_API_KEY);
}
