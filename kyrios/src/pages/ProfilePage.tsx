import { Settings, Grid3X3, AtSign } from 'lucide-react'

export function ProfilePage() {
  return (
    <div className="flex flex-col h-full overflow-y-auto scrollbar-hide pb-28">
      <header className="flex items-center justify-between px-5 pt-2 pb-4">
        <div className="flex items-center gap-2">
          <span className="font-semibold">Darlene</span>
          <svg width="12" height="12" viewBox="0 0 12 12" fill="white" opacity="0.5">
            <path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" strokeWidth="1.5" fill="none" />
          </svg>
        </div>
        <div className="flex items-center gap-2">
          <button className="text-sm text-indigo-400 font-medium">Edit</button>
          <button className="p-1 text-white/60">
            <Settings size={20} />
          </button>
        </div>
      </header>

      <div className="flex flex-col items-center px-5 pb-6">
        <img
          src="https://i.pravatar.cc/200?u=darlene"
          alt="Darlene Beats"
          className="w-24 h-24 rounded-full object-cover border-2 border-indigo-500/50 mb-3"
        />
        <h1 className="text-xl font-bold">Darlene Beats</h1>
        <p className="text-sm text-white/40 mb-4">@dw_beats</p>
        <div className="flex gap-8">
          {[
            { label: 'Post', value: '360' },
            { label: 'Follower', value: '160k' },
            { label: 'Following', value: '140k' },
          ].map((stat) => (
            <div key={stat.label} className="text-center">
              <p className="font-bold text-lg">{stat.value}</p>
              <p className="text-xs text-white/40">{stat.label}</p>
            </div>
          ))}
        </div>
      </div>

      {/* Highlights */}
      <div className="flex gap-3 overflow-x-auto scrollbar-hide px-5 pb-4">
        {['Add Story', 'Travel', 'Music', 'Food'].map((label, i) => (
          <div key={label} className="shrink-0">
            <div className="w-16 h-16 rounded-2xl overflow-hidden border border-white/10 bg-white/5 flex items-center justify-center">
              {i === 0 ? (
                <span className="text-2xl text-indigo-400">+</span>
              ) : (
                <img
                  src={`https://images.unsplash.com/photo-${1480714378408 + i}?w=100&h=100&fit=crop`}
                  alt={label}
                  className="w-full h-full object-cover"
                  onError={(e) => {
                    e.currentTarget.src = `https://i.pravatar.cc/100?u=${label}`
                  }}
                />
              )}
            </div>
            <p className="text-[10px] text-white/50 mt-1 text-center">{label}</p>
          </div>
        ))}
      </div>

      {/* Tabs */}
      <div className="flex border-b border-white/10 mx-5 mb-4">
        <button className="flex-1 flex items-center justify-center gap-1.5 pb-3 border-b-2 border-indigo-400 text-sm font-medium">
          <Grid3X3 size={16} /> Post
        </button>
        <button className="flex-1 flex items-center justify-center gap-1.5 pb-3 text-sm text-white/40">
          <AtSign size={16} /> Mention
        </button>
      </div>

      {/* Photo grid */}
      <div className="px-5 grid grid-cols-3 gap-1">
        {Array.from({ length: 9 }).map((_, i) => (
          <div key={i} className="aspect-square rounded-lg overflow-hidden bg-white/5">
            <img
              src={`https://images.unsplash.com/photo-${1500000000000 + i * 100000}?w=200&h=200&fit=crop`}
              alt=""
              className="w-full h-full object-cover"
              onError={(e) => {
                e.currentTarget.src = `https://picsum.photos/200/200?random=${i}`
              }}
            />
          </div>
        ))}
      </div>
    </div>
  )
}

export function CallsPage() {
  const calls = [
    { name: 'Kira Lindegaard', avatar: 'https://i.pravatar.cc/150?u=kira', type: 'incoming', time: '10:32 AM', duration: '5:24' },
    { name: 'Justin Bryant', avatar: 'https://i.pravatar.cc/150?u=justin', type: 'outgoing', time: 'Yesterday', duration: '12:01' },
    { name: 'Best girls', avatar: 'https://i.pravatar.cc/150?u=bestgirls', type: 'missed', time: 'Monday', duration: '' },
  ]

  return (
    <div className="flex flex-col h-full overflow-y-auto scrollbar-hide pb-28">
      <header className="px-5 pt-2 pb-4">
        <h1 className="text-2xl font-bold">Calls</h1>
      </header>

      <div className="px-5 space-y-1">
        {calls.map((call) => (
          <button
            key={call.name}
            className="w-full flex items-center gap-3 py-3 hover:bg-white/5 rounded-xl transition-colors text-left"
          >
            <img src={call.avatar} alt={call.name} className="w-12 h-12 rounded-full object-cover" />
            <div className="flex-1">
              <p className="font-semibold">{call.name}</p>
              <p className={`text-sm ${call.type === 'missed' ? 'text-red-400' : 'text-white/40'}`}>
                {call.type === 'incoming' ? '↙' : call.type === 'outgoing' ? '↗' : '↙'} {call.time}
                {call.duration && ` · ${call.duration}`}
              </p>
            </div>
          </button>
        ))}
      </div>
    </div>
  )
}
