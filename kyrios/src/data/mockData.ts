export interface Story {
  id: string
  name: string
  avatar: string
  isLive?: boolean
  isAdd?: boolean
}

export interface Chat {
  id: string
  name: string
  avatar: string
  lastMessage: string
  sender?: string
  time: string
  unread?: number
  isRead?: boolean
  isGroup?: boolean
  isOnline?: boolean
}

export interface Message {
  id: string
  senderId: string
  senderName: string
  senderAvatar: string
  text?: string
  images?: string[]
  reactions?: { emoji: string; count: number }[]
  time: string
  isOwn?: boolean
}

export interface Community {
  id: string
  name: string
  icon: string
  members: number
  color: string
}

export interface Post {
  id: string
  author: string
  handle: string
  avatar: string
  caption: string
  image: string
  likes: number
  comments: number
  time: string
}

export const stories: Story[] = [
  { id: 'me', name: 'Me', avatar: 'https://i.pravatar.cc/150?u=me', isAdd: true },
  { id: '1', name: 'Haris', avatar: 'https://i.pravatar.cc/150?u=haris', isLive: true },
  { id: '2', name: 'Abdullah', avatar: 'https://i.pravatar.cc/150?u=abdullah' },
  { id: '3', name: 'Sienna', avatar: 'https://i.pravatar.cc/150?u=sienna' },
  { id: '4', name: 'Alex', avatar: 'https://i.pravatar.cc/150?u=alex' },
  { id: '5', name: 'Jordan', avatar: 'https://i.pravatar.cc/150?u=jordan' },
]

export const chats: Chat[] = [
  {
    id: 'visit-denpasar',
    name: 'Visit Denpasar',
    avatar: 'https://i.pravatar.cc/150?u=denpasar',
    lastMessage: 'Khai: Are they still open at sunday?',
    time: '24 mins',
    unread: 4,
    isGroup: true,
    isOnline: true,
  },
  {
    id: 'kira',
    name: 'Kira Lindegaard',
    avatar: 'https://i.pravatar.cc/150?u=kira',
    lastMessage: 'See you tomorrow! ✨',
    time: '2 mins',
    isRead: true,
    isOnline: true,
  },
  {
    id: 'best-girls',
    name: 'Best girls',
    avatar: 'https://i.pravatar.cc/150?u=bestgirls',
    lastMessage: 'Guys I just made $10,000 from Youtube...',
    time: '20 secs',
    unread: 5,
    isGroup: true,
  },
  {
    id: 'justin',
    name: 'Justin Bryant',
    avatar: 'https://i.pravatar.cc/150?u=justin',
    lastMessage: 'Thanks for the update',
    time: '1h',
    isRead: true,
  },
  {
    id: 'devon',
    name: 'Devon McCoy',
    avatar: 'https://i.pravatar.cc/150?u=devon',
    lastMessage: 'Can we reschedule?',
    time: '3h',
    unread: 2,
  },
  {
    id: 'sara',
    name: 'Sara Watanabe',
    avatar: 'https://i.pravatar.cc/150?u=sara',
    lastMessage: 'Happy birthday! 🎂',
    time: '5h',
    isRead: true,
  },
]

export const visitDenpasarMessages: Message[] = [
  {
    id: '1',
    senderId: 'kira',
    senderName: 'Kira Lindegaard',
    senderAvatar: 'https://i.pravatar.cc/150?u=kira',
    text: 'Hey everyone! I found this amazing spot in Denpasar 🌴',
    time: '8:16 PM',
  },
  {
    id: '2',
    senderId: 'khai',
    senderName: 'Khai Azzahra',
    senderAvatar: 'https://i.pravatar.cc/150?u=khai',
    text: 'Are they still open at sunday?',
    time: '8:19 PM',
  },
  {
    id: '3',
    senderId: 'akbar',
    senderName: 'Akbar Lazuardi',
    senderAvatar: 'https://i.pravatar.cc/150?u=akbar',
    images: [
      'https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?w=200&h=200&fit=crop',
      'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=200&h=200&fit=crop',
      'https://images.unsplash.com/photo-1555404738-9f2795b1dbe8?w=200&h=200&fit=crop',
    ],
    reactions: [
      { emoji: '🔥', count: 6 },
      { emoji: '😍', count: 9 },
    ],
    time: '8:21 PM',
  },
]

export const communities: Community[] = [
  { id: '1', name: 'Foodies', icon: '🍕', members: 12400, color: '#f97316' },
  { id: '2', name: 'Daily Inspiration', icon: '✨', members: 8900, color: '#a855f7' },
  { id: '3', name: 'Football', icon: '⚽', members: 23100, color: '#22c55e' },
  { id: '4', name: 'Sneakerheads', icon: '👟', members: 5600, color: '#3b82f6' },
  { id: '5', name: 'Photography', icon: '📷', members: 15800, color: '#ec4899' },
  { id: '6', name: 'Travel', icon: '✈️', members: 19200, color: '#06b6d4' },
]

export const discoverPosts: Post[] = [
  {
    id: '1',
    author: 'Nilesh',
    handle: '@nilesh',
    avatar: 'https://i.pravatar.cc/150?u=nilesh',
    caption: 'Discover adventure in patagonia\'s peaks or serenity provence\'s @hamlets — arrival',
    image: 'https://images.unsplash.com/photo-1682687220063-4742bd7fd538?w=400&h=300&fit=crop',
    likes: 2400,
    comments: 156,
    time: '1h ago',
  },
  {
    id: '2',
    author: 'Bran S.',
    handle: '@brans',
    avatar: 'https://i.pravatar.cc/150?u=brans',
    caption: 'Golden hour in the city never gets old',
    image: 'https://images.unsplash.com/photo-1480714378408-67cf0d13bc1b?w=400&h=300&fit=crop',
    likes: 7400,
    comments: 320,
    time: '3h ago',
  },
]

export const chatFilters = ['All', 'Favorites', 'Work', 'Groups'] as const
export type ChatFilter = (typeof chatFilters)[number]
