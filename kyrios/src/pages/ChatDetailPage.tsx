import { ChatHeader } from '../components/ChatHeader'
import { MessageBubble, TimeDivider, MessageInput } from '../components/MessageBubble'
import { visitDenpasarMessages, chats } from '../data/mockData'

interface ChatDetailPageProps {
  chatId: string
  onBack: () => void
}

export function ChatDetailPage({ chatId, onBack }: ChatDetailPageProps) {
  const chat = chats.find((c) => c.id === chatId) ?? chats[0]
  const messages = chatId === 'visit-denpasar' ? visitDenpasarMessages : visitDenpasarMessages.slice(0, 2)

  return (
    <div className="flex flex-col h-full">
      <ChatHeader
        name={chat.name}
        avatar={chat.avatar}
        isOnline={chat.isOnline}
        isGroup={chat.isGroup}
        onBack={onBack}
      />

      <div className="flex-1 overflow-y-auto scrollbar-hide py-4">
        {messages.map((msg, i) => (
          <div key={msg.id}>
            {i === 0 && <TimeDivider time={msg.time} />}
            <MessageBubble message={msg} />
            {i < messages.length - 1 && messages[i + 1]?.time !== msg.time && (
              <TimeDivider time={messages[i + 1]?.time ?? ''} />
            )}
          </div>
        ))}
      </div>

      <MessageInput />
    </div>
  )
}
