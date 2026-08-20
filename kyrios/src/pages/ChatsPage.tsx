import { useState } from 'react'
import { Search, Camera, MoreVertical } from '../components/StoryRow'
import { StoryRow } from '../components/StoryRow'
import { FilterPills } from '../components/StoryRow'
import { ChatListItem } from '../components/ChatListItem'
import { stories, chats, chatFilters, type ChatFilter } from '../data/mockData'

interface ChatsPageProps {
  onOpenChat: (chatId: string) => void
}

export function ChatsPage({ onOpenChat }: ChatsPageProps) {
  const [filter, setFilter] = useState<ChatFilter>('All')

  const filteredChats = chats.filter((chat) => {
    if (filter === 'Groups') return chat.isGroup
    if (filter === 'Favorites') return chat.isOnline
    return true
  })

  return (
    <div className="flex flex-col h-full">
      <header className="flex items-center justify-between px-5 pt-2 pb-1">
        <h1 className="text-2xl font-bold">Chats</h1>
        <div className="flex items-center gap-1">
          <button className="p-2 text-white/60 hover:text-white rounded-full hover:bg-white/10">
            <Search size={22} />
          </button>
          <button className="p-2 text-white/60 hover:text-white rounded-full hover:bg-white/10">
            <Camera size={22} />
          </button>
          <button className="p-2 text-white/60 hover:text-white rounded-full hover:bg-white/10">
            <MoreVertical size={22} />
          </button>
        </div>
      </header>

      <StoryRow stories={stories} />
      <FilterPills filters={chatFilters} active={filter} onChange={(f) => setFilter(f as ChatFilter)} />

      <div className="flex-1 overflow-y-auto scrollbar-hide pb-28">
        {filteredChats.map((chat) => (
          <ChatListItem key={chat.id} chat={chat} onClick={() => onOpenChat(chat.id)} />
        ))}
      </div>
    </div>
  )
}
