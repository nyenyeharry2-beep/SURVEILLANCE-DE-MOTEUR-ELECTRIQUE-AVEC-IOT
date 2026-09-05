import { useMemo, useState } from 'react';
import { Toaster } from 'sonner';
import { ChevronLeft, Grid3X3 } from 'lucide-react';
import appViewsRaw from './data/app_views.txt?raw';
import { DarkWebScannerModule } from './modules/DarkWebScannerModule';

function parseAppViews(raw: string): string[] {
  const seen = new Set<string>();
  const modules: string[] = [];
  for (const line of raw.split('\n')) {
    const name = line.trim();
    if (name && !seen.has(name)) {
      seen.add(name);
      modules.push(name);
    }
  }
  return modules;
}

function PlaceholderModule({ name }: { name: string }) {
  return (
    <div className="p-6 h-full flex flex-col items-center justify-center text-center opacity-60">
      <Grid3X3 className="w-10 h-10 mb-4" />
      <h2 className="text-sm font-bold uppercase tracking-widest mb-2">{name}</h2>
      <p className="text-[10px] font-mono uppercase max-w-xs">
        Module registered in app_views.txt — interface mobile à venir.
      </p>
    </div>
  );
}

export default function App() {
  const modules = useMemo(() => parseAppViews(appViewsRaw), []);
  const [activeModule, setActiveModule] = useState<string | null>(null);

  if (activeModule === 'darkweb') {
    return (
      <div className="h-full flex flex-col">
        <header className="border-b border-brand-line px-4 py-3 flex items-center gap-3 bg-brand-bg">
          <button
            type="button"
            onClick={() => setActiveModule(null)}
            className="p-2 -ml-2"
            aria-label="Retour"
          >
            <ChevronLeft className="w-5 h-5" />
          </button>
          <h1 className="text-xs font-bold uppercase tracking-widest">Dark Web Scanner</h1>
        </header>
        <main className="flex-1 overflow-hidden">
          <DarkWebScannerModule />
        </main>
        <Toaster position="top-center" richColors />
      </div>
    );
  }

  if (activeModule) {
    return (
      <div className="h-full flex flex-col">
        <header className="border-b border-brand-line px-4 py-3 flex items-center gap-3 bg-brand-bg">
          <button
            type="button"
            onClick={() => setActiveModule(null)}
            className="p-2 -ml-2"
            aria-label="Retour"
          >
            <ChevronLeft className="w-5 h-5" />
          </button>
          <h1 className="text-xs font-bold uppercase tracking-widest">{activeModule}</h1>
        </header>
        <main className="flex-1 overflow-hidden">
          <PlaceholderModule name={activeModule} />
        </main>
      </div>
    );
  }

  return (
    <div className="h-full flex flex-col">
      <header className="border-b border-brand-line px-4 py-4 bg-brand-bg">
        <h1 className="text-sm font-bold uppercase tracking-widest">Intelligence Platform</h1>
        <p className="text-[10px] font-mono opacity-50 mt-1 uppercase">
          {modules.length} modules · app_views.txt
        </p>
      </header>
      <main className="flex-1 overflow-y-auto p-4">
        <div className="grid grid-cols-2 gap-3">
          {modules.map((mod) => (
            <button
              key={mod}
              type="button"
              onClick={() => setActiveModule(mod)}
              className={`border border-brand-line p-4 text-left shadow-[2px_2px_0px_0px_rgba(20,20,20,1)] active:translate-x-[1px] active:translate-y-[1px] active:shadow-none transition-all ${
                mod === 'darkweb' ? 'bg-brand-ink text-brand-bg' : 'bg-brand-bg'
              }`}
            >
              <span className="text-[10px] font-bold uppercase tracking-widest">{mod}</span>
              {mod === 'darkweb' && (
                <p className="text-[9px] font-mono mt-2 opacity-70">Scanner actif</p>
              )}
            </button>
          ))}
        </div>
      </main>
    </div>
  );
}
