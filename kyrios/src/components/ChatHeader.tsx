import { ArrowLeft, Phone, Video, MoreVertical } from 'lucide-react'

interface ChatHeaderProps {
  name: string
  avatar: string
  isOnline?: boolean
  isGroup?: boolean
  onBack: () => void
}

export function ChatHeader({ name, avatar, isOnline, onBack }: ChatHeaderProps) {
  return (
    <header className="flex items-center gap-3 px-4 py-3 glass border-b-0">
      <button onClick={onBack} className="p-1 -ml-1 text-white/80 hover:text-white">
        <ArrowLeft size={22} />
      </button>
      <img src={avatar} alt={name} className="w-10 h-10 rounded-full object-cover" />
      <div className="flex-1 min-w-0">
        <h2 className="font-semibold text-[15px] truncate">{name}</h2>
        {isOnline && (
          <span className="text-xs text-green-400 flex items-center gap-1">
            <span className="w-1.5 h-1.5 rounded-full bg-green-400" />
            Online
          </span>
        )}
      </div>
      <div className="flex items-center gap-1">
        <button className="p-2 text-white/60 hover:text-white rounded-full hover:bg-white/10">
          <Phone size={20} />
        </button>
        <button className="p-2 text-white/60 hover:text-white rounded-full hover:bg-white/10">
          <Video size={20} />
        </button>
        <button className="p-2 text-white/60 hover:text-white rounded-full hover:bg-white/10">
          <MoreVertical size={20} />
        </button>
      </div>
    </header>
  )
}
