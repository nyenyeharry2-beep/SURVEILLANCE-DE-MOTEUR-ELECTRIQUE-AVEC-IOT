import { MessageCircle, Phone, Compass, User, Plus } from 'lucide-react'

export type TabId = 'chats' | 'calls' | 'discover' | 'profile'

interface BottomNavProps {
  active: TabId
  onChange: (tab: TabId) => void
}

const tabs: { id: TabId; icon: typeof MessageCircle; label: string }[] = [
  { id: 'chats', icon: MessageCircle, label: 'Chats' },
  { id: 'calls', icon: Phone, label: 'Calls' },
  { id: 'discover', icon: Compass, label: 'Discover' },
  { id: 'profile', icon: User, label: 'Profile' },
]

export function BottomNav({ active, onChange }: BottomNavProps) {
  return (
    <nav className="absolute bottom-6 left-1/2 -translate-x-1/2 z-20">
      <div className="glass-strong rounded-full flex items-center gap-1 px-3 py-2 shadow-2xl">
        {tabs.map(({ id, icon: Icon, label }) => (
          <button
            key={id}
            onClick={() => onChange(id)}
            className={`relative p-3 rounded-full transition-all ${
              active === id
                ? 'bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/30'
                : 'text-white/50 hover:text-white/80'
            }`}
            aria-label={label}
          >
            {id === 'chats' && active === 'chats' ? (
              <div className="w-6 h-6 rounded-full bg-gradient-to-br from-cyan-400 via-indigo-400 to-purple-500 flex items-center justify-center">
                <span className="text-[10px] font-bold text-white">K</span>
              </div>
            ) : (
              <Icon size={22} strokeWidth={active === id ? 2.5 : 2} />
            )}
          </button>
        ))}
        <button
          className="ml-1 w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-white/70 hover:bg-white/20 transition-colors"
          aria-label="New message"
        >
          <Plus size={20} />
        </button>
      </div>
    </nav>
  )
}
