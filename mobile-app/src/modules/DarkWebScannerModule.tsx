import React, { useState, useEffect } from 'react';
import { Terminal, ShieldAlert, FileSearch, Lock, Download, AlertTriangle, Eye, ServerCrash } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { ScrollArea } from '@/components/ui/scroll-area';
import { toast } from 'sonner';

interface LeakData {
  id: string;
  timestamp: string;
  source: string;
  matchedEntity: string;
  threatLevel: 'critical' | 'high' | 'medium';
  snippet: string;
}

const MOCK_LEAKS: LeakData[] = [
  { id: 'LK-992A', timestamp: '2 mins ago', source: 'OnionSite X-9V', matchedEntity: 'Lithium Trace #44', threatLevel: 'critical', snippet: '...shipping container 0x4A, chemical composition closely matching the regulated Li/Co blend. Offloading in 2 days...' },
  { id: 'LK-110B', timestamp: '14 mins ago', source: 'Telegram Channel // O-Chain', matchedEntity: 'Manufacturer Beta', threatLevel: 'high', snippet: '...bypass the spectrometer. Use the masking agent as discussed. Documents attached...' },
  { id: 'LK-087C', timestamp: '1 hour ago', source: 'DeepWeb Forum Null', matchedEntity: 'Estuary Main Stream', threatLevel: 'medium', snippet: '...dump schedule updated. Avoid the new downstream sensors. They deployed 4 new ones...' }
];

export const DarkWebScannerModule: React.FC = () => {
  const [isScanning, setIsScanning] = useState(false);
  const [logs, setLogs] = useState<string[]>([]);
  const [leaks, setLeaks] = useState<LeakData[]>([]);
  const [searchTarget, setSearchTarget] = useState('');

  const runScan = () => {
    if (!searchTarget.trim()) {
      toast.error('Search Empty', { description: 'Enter an entity, chemical, or manufacturer.' });
      return;
    }
    
    setIsScanning(true);
    setLogs([]);
    setLeaks([]);
    
    const scanSteps = [
      `[INIT] Establishing Tor circuit...`,
      `[OK] Connected to proxy nodes`,
      `[SCAN] Querying underground marketplaces for target: "${searchTarget}"`,
      `[WARN] Rate limit encountered on Forum Null. Bypass initiated...`,
      `[SCAN] Analyzing intercepted Telegram packets...`,
      `[MATCH] Cross-referencing forensic fingerprints...`,
      `[DONE] Scan complete. Correlating matches...`
    ];
    
    let step = 0;
    const interval = setInterval(() => {
      if (step < scanSteps.length) {
        setLogs(prev => [...prev, scanSteps[step]]);
        step++;
      } else {
        clearInterval(interval);
        setIsScanning(false);
        setLeaks(MOCK_LEAKS);
        toast.info('Scan Complete', { description: 'Found 3 potential matches in underground chatter.' });
      }
    }, 800);
  };

  return (
    <div className="p-6 h-full flex flex-col overflow-hidden">
      <div className="flex gap-4 items-center mb-6">
        <div className="flex-1 max-w-md relative">
          <FileSearch className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 opacity-50" />
          <Input 
            value={searchTarget}
            onChange={(e) => setSearchTarget(e.target.value)}
            placeholder="Enter target entity, signature ID, or keyword..."
            className="pl-10 font-mono text-xs rounded-none border-brand-line shadow-none focus-visible:ring-1 focus-visible:ring-brand-ink"
            onKeyDown={(e) => { if(e.key === 'Enter') runScan(); }}
          />
        </div>
        <Button 
          onClick={runScan}
          disabled={isScanning}
          className="rounded-none bg-brand-ink text-brand-bg uppercase text-xs font-bold tracking-wider px-8 shadow-[4px_4px_0px_0px_rgba(20,20,20,1)] hover:bg-brand-ink/90"
        >
          {isScanning ? <ServerCrash className="w-4 h-4 mr-2 animate-pulse" /> : <Eye className="w-4 h-4 mr-2" />}
          {isScanning ? 'Scraping Deep Web...' : 'Initiate Intel Sweep'}
        </Button>
      </div>

      <div className="flex-1 flex gap-6 overflow-hidden">
        {/* Terminal output */}
        <div className="w-1/3 bg-black border border-brand-line p-4 flex flex-col font-mono text-xs text-brand-ink shadow-[4px_4px_0px_0px_rgba(20,20,20,1)]">
          <div className="flex items-center gap-2 border-b border-brand-line/30 pb-2 mb-4">
            <Terminal className="w-4 h-4 opacity-50" />
            <h3 className="uppercase opacity-50 tracking-widest">Protocol CLI</h3>
          </div>
          <ScrollArea className="flex-1">
            <div className="space-y-2 opacity-80">
              <p className="text-zinc-500">&gt; Awaiting command...</p>
              {logs.map((log, i) => (
                <p key={`log-${i}`} className={log.includes('[WARN]') ? 'text-yellow-500' : log.includes('[MATCH]') ? 'text-green-500' : ''}>
                  &gt; {log}
                </p>
              ))}
              {isScanning && <p className="animate-pulse">&gt; _</p>}
            </div>
          </ScrollArea>
        </div>
        
        {/* Results */}
        <div className="flex-1 bg-brand-line/5 border border-brand-line p-6 overflow-y-auto">
          <div className="flex items-center gap-2 mb-6 border-b border-brand-line/20 pb-4">
            <ShieldAlert className="w-5 h-5" />
            <h2 className="text-sm font-bold uppercase tracking-widest">Intercepted Intelligence</h2>
          </div>
          
          {leaks.length === 0 ? (
            <div className="h-40 flex items-center justify-center opacity-30 text-[10px] font-mono uppercase">
              No active leaks detected. Standing by.
            </div>
          ) : (
             <div className="space-y-4">
               {leaks.map((leak) => (
                 <div key={leak.id} className="border border-brand-line/30 bg-brand-bg p-4 shadow-[2px_2px_0px_0px_rgba(20,20,20,1)]">
                   <div className="flex justify-between items-start mb-3">
                     <div>
                       <div className="flex gap-2 items-center mb-1">
                         <h4 className="font-bold text-sm tracking-widest uppercase">{leak.id}</h4>
                         <Badge variant="outline" className={
                           leak.threatLevel === 'critical' ? 'border-red-500 text-red-500' :
                           leak.threatLevel === 'high' ? 'border-yellow-500 text-yellow-500' : 'border-blue-500 text-blue-500'
                         }>
                           {leak.threatLevel}
                         </Badge>
                       </div>
                       <p className="text-[10px] font-mono opacity-50 uppercase flex gap-2 items-center">
                         <Lock className="w-3 h-3" /> {leak.source} | {leak.timestamp}
                       </p>
                     </div>
                     <Button variant="ghost" size="icon" className="h-8 w-8 text-brand-ink opacity-50 hover:opacity-100">
                       <Download className="w-4 h-4" />
                     </Button>
                   </div>
                   
                   <div className="border-l-2 border-brand-ink pl-3 py-1 my-3 bg-brand-line/5">
                     <p className="font-mono text-xs opacity-80 leading-relaxed italic">
                       "{leak.snippet}"
                     </p>
                   </div>
                   
                   <div className="flex gap-2 mt-2">
                     <span className="text-[9px] font-bold font-mono tracking-widest uppercase bg-brand-ink/10 text-brand-ink px-2 py-1">
                       Target Match: {leak.matchedEntity}
                     </span>
                   </div>
                 </div>
               ))}
             </div>
          )}
        </div>
      </div>
    </div>
  );
};
