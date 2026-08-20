import { useState } from 'react'
import { PhoneFrame } from './components/PhoneFrame'
import { BottomNav, type TabId } from './components/BottomNav'
import { ChatsPage } from './pages/ChatsPage'
import { ChatDetailPage } from './pages/ChatDetailPage'
import { DiscoverPage } from './pages/DiscoverPage'
import { ProfilePage, CallsPage } from './pages/ProfilePage'

export default function App() {
  const [activeTab, setActiveTab] = useState<TabId>('chats')
  const [activeChat, setActiveChat] = useState<string | null>(null)

  const handleOpenChat = (chatId: string) => setActiveChat(chatId)
  const handleBack = () => setActiveChat(null)

  const renderContent = () => {
    if (activeChat) {
      return <ChatDetailPage chatId={activeChat} onBack={handleBack} />
    }

    switch (activeTab) {
      case 'chats':
        return <ChatsPage onOpenChat={handleOpenChat} />
      case 'calls':
        return <CallsPage />
      case 'discover':
        return <DiscoverPage />
      case 'profile':
        return <ProfilePage />
      default:
        return <ChatsPage onOpenChat={handleOpenChat} />
    }
  }

  return (
    <PhoneFrame>
      {renderContent()}
      {!activeChat && <BottomNav active={activeTab} onChange={setActiveTab} />}
    </PhoneFrame>
  )
}
