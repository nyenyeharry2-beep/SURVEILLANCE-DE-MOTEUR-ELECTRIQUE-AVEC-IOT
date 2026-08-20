import { CheckCheck } from 'lucide-react'
import type { Chat } from '../data/mockData'

interface ChatListItemProps {
  chat: Chat
  onClick: () => void
}

export function ChatListItem({ chat, onClick }: ChatListItemProps) {
  return (
    <button
      onClick={onClick}
      className="w-full flex items-center gap-3 px-5 py-3 hover:bg-white/5 transition-colors text-left"
    >
      <div className="relative shrink-0">
        <img
          src={chat.avatar}
          alt={chat.name}
          className="w-12 h-12 rounded-full object-cover"
        />
        {chat.isOnline && (
          <span className="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-[#0a0a0f]" />
        )}
      </div>
      <div className="flex-1 min-w-0">
        <div className="flex items-center justify-between gap-2">
          <span className="font-semibold text-[15px] truncate">{chat.name}</span>
          <span className="text-xs text-white/40 shrink-0">{chat.time}</span>
        </div>
        <div className="flex items-center justify-between gap-2 mt-0.5">
          <p className="text-sm text-white/50 truncate">{chat.lastMessage}</p>
          {chat.unread ? (
            <span className="shrink-0 min-w-[20px] h-5 px-1.5 rounded-full bg-red-500 text-white text-xs font-bold flex items-center justify-center">
              {chat.unread}
            </span>
          ) : chat.isRead ? (
            <CheckCheck size={16} className="text-indigo-400 shrink-0" />
          ) : null}
        </div>
      </div>
    </button>
  )
}
