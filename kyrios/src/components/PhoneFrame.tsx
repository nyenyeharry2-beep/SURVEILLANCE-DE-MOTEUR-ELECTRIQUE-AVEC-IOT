import type { ReactNode } from 'react'

interface PhoneFrameProps {
  children: ReactNode
}

export function PhoneFrame({ children }: PhoneFrameProps) {
  return (
    <div className="h-full flex items-center justify-center bg-[#050508] p-4">
      <div className="relative w-full max-w-[390px] h-full max-h-[844px] bg-[#0a0a0f] rounded-[3rem] overflow-hidden shadow-2xl shadow-black/50 border border-white/10">
        {/* Dynamic Island */}
        <div className="absolute top-3 left-1/2 -translate-x-1/2 w-28 h-7 bg-black rounded-full z-30" />
        {/* Status bar */}
        <div className="absolute top-0 left-0 right-0 h-12 flex items-end justify-between px-8 pb-1 z-20 text-xs font-medium">
          <span>9:41</span>
          <div className="flex items-center gap-1">
            <svg width="16" height="12" viewBox="0 0 16 12" fill="white">
              <rect x="0" y="8" width="3" height="4" rx="0.5" opacity="0.4" />
              <rect x="4.5" y="5" width="3" height="7" rx="0.5" opacity="0.6" />
              <rect x="9" y="2" width="3" height="10" rx="0.5" opacity="0.8" />
              <rect x="13.5" y="0" width="3" height="12" rx="0.5" />
            </svg>
            <svg width="16" height="12" viewBox="0 0 16 12" fill="white">
              <path d="M8 3C10.5 3 12.7 4.1 14.2 5.9L15.5 4.5C13.6 2.3 10.9 1 8 1C5.1 1 2.4 2.3 0.5 4.5L1.8 5.9C3.3 4.1 5.5 3 8 3Z" opacity="0.5" />
              <path d="M8 6.5C9.7 6.5 11.2 7.2 12.2 8.4L13.5 7C12.1 5.4 10.1 4.5 8 4.5C5.9 4.5 3.9 5.4 2.5 7L3.8 8.4C4.8 7.2 6.3 6.5 8 6.5Z" opacity="0.75" />
              <circle cx="8" cy="11" r="1.5" />
            </svg>
            <svg width="24" height="12" viewBox="0 0 24 12" fill="none">
              <rect x="0.5" y="0.5" width="20" height="11" rx="2" stroke="white" strokeOpacity="0.4" />
              <rect x="2" y="2" width="16" height="8" rx="1" fill="white" />
              <rect x="21.5" y="4" width="2" height="4" rx="0.5" fill="white" fillOpacity="0.4" />
            </svg>
          </div>
        </div>
        <div className="relative h-full pt-12 flex flex-col">
          {children}
        </div>
        {/* Home indicator */}
        <div className="absolute bottom-2 left-1/2 -translate-x-1/2 w-32 h-1 bg-white/30 rounded-full z-30" />
      </div>
    </div>
  )
}
