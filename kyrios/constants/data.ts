import { AgeDemographic, Chat, Community, InsightLocation, Message, Story, User } from '../types';

export const currentUser: User = {
  id: 'me',
  name: 'Me',
  avatar: 'https://i.pravatar.cc/150?u=me',
};

export const stories: Story[] = [
  { id: 'me', name: 'Me', avatar: 'https://i.pravatar.cc/150?u=me', isOwn: true },
  { id: 's1', name: 'Haris', avatar: 'https://i.pravatar.cc/150?u=haris', live: true },
  { id: 's2', name: 'Kira', avatar: 'https://i.pravatar.cc/150?u=kira2' },
  { id: 's3', name: 'Akbar', avatar: 'https://i.pravatar.cc/150?u=akbar' },
  { id: 's4', name: 'Lily', avatar: 'https://i.pravatar.cc/150?u=lily3' },
  { id: 's5', name: 'Jason', avatar: 'https://i.pravatar.cc/150?u=jason2' },
  { id: 's6', name: 'Nia', avatar: 'https://i.pravatar.cc/150?u=nia' },
];

export const chats: Chat[] = [
  {
    id: 'visit-denpasar',
    name: 'Visit Denpasar',
    avatar: 'https://i.pravatar.cc/150?u=denpasar',
    lastMessage: 'Akbar: The photos look amazing!',
    timestamp: '24 mins',
    unreadCount: 4,
    isGroup: true,
    category: 'groups',
    online: true,
  },
  {
    id: 'kira-lindegaard',
    name: 'Kira Lindegaard',
    avatar: 'https://i.pravatar.cc/150?u=kira',
    lastMessage: 'See you tomorrow at the cafe ☕',
    timestamp: '2 mins',
    isRead: true,
    category: 'favorites',
    online: true,
  },
  {
    id: 'best-girls',
    name: 'Best girls',
    avatar: 'https://i.pravatar.cc/150?u=bestgirls',
    lastMessage: 'Guys I just made $10,000 from Youtube...',
    timestamp: '20 secs',
    unreadCount: 5,
    isGroup: true,
    category: 'groups',
  },
  {
    id: 'justin-bryant',
    name: 'Justin Bryant',
    avatar: 'https://i.pravatar.cc/150?u=justin',
    lastMessage: 'I have wanted to add that to my collection.',
    timestamp: '3:13 PM',
    category: 'work',
  },
  {
    id: 'peter-johnson',
    name: 'Peter Johnson',
    avatar: 'https://i.pravatar.cc/150?u=peter',
    lastMessage: 'Sure, It happens to me all the...',
    timestamp: '15m',
    unreadCount: 2,
    isTyping: true,
    online: true,
    category: 'favorites',
  },
  {
    id: 'nia-denton',
    name: 'Nia Denton',
    avatar: 'https://i.pravatar.cc/150?u=niad',
    lastMessage: 'OMG your so funny! 😂😂😂',
    timestamp: '1:13 PM',
    category: 'favorites',
  },
  {
    id: 'devon-mccoy',
    name: 'Devon Mccoy',
    avatar: 'https://i.pravatar.cc/150?u=devon',
    lastMessage: 'Best book you read recently??',
    timestamp: '2:59 PM',
    category: 'work',
  },
  {
    id: 'arlene-mccoy',
    name: 'Arlene McCoy',
    avatar: 'https://i.pravatar.cc/150?u=arlene',
    lastMessage: 'Hey Karen! How are you?',
    timestamp: '2 mins',
    unreadCount: 1,
  },
];

export const messagesByChat: Record<string, Message[]> = {
  'visit-denpasar': [
    {
      id: 'm1',
      senderId: 'kira',
      senderName: 'Kira Lindegaard',
      text: 'Has anyone booked the hotel yet?',
      timestamp: '8:16PM',
      isOwn: false,
    },
    {
      id: 'm2',
      senderId: 'me',
      text: 'Yes! I got us a villa near the beach 🏖️',
      timestamp: '8:18PM',
      isOwn: true,
      isRead: true,
    },
    {
      id: 'm3',
      senderId: 'akbar',
      senderName: 'Akbar Lazuardi',
      text: 'Here are some photos from my last trip!',
      timestamp: '8:21PM',
      isOwn: false,
      images: [
        'https://picsum.photos/seed/den1/200/280',
        'https://picsum.photos/seed/den2/200/280',
        'https://picsum.photos/seed/den3/200/280',
      ],
      reactions: [
        { emoji: '🔥', count: 6 },
        { emoji: '😍', count: 9 },
      ],
    },
    {
      id: 'm4',
      senderId: 'me',
      text: 'These look incredible! Can\'t wait 🎉',
      timestamp: '8:22PM',
      isOwn: true,
      isRead: true,
    },
  ],
  'best-girls': [
    {
      id: 'm1',
      senderId: 'me',
      text: "Guys I just made $10,000 from Youtube. I'm going to ball this weekend. Let's party!",
      timestamp: 'Yesterday',
      isOwn: true,
    },
    {
      id: 'm2',
      senderId: 'u1',
      senderName: 'Sarah',
      text: 'OMG congrats!! 🎊 We need to celebrate ASAP',
      timestamp: 'Yesterday',
      isOwn: false,
    },
    {
      id: 'm3',
      senderId: 'u2',
      senderName: 'Emma',
      text: 'Count me in! Where are we going?',
      timestamp: 'Yesterday',
      isOwn: false,
    },
  ],
  'peter-johnson': [
    {
      id: 'm1',
      senderId: 'me',
      text: 'Wassup!!!!!! 🔥🔥🔥🔥 So what did I miss yesterday?',
      timestamp: '13:30',
      isOwn: true,
    },
    {
      id: 'm2',
      senderId: 'peter',
      text: 'So, while you were gone, a lot has happened. 😋',
      timestamp: '13:32',
      isOwn: false,
      isRead: true,
    },
    {
      id: 'm3',
      senderId: 'peter',
      text: 'Amazing place. When are you coming back from a trip? 🤝',
      timestamp: '13:46',
      isOwn: false,
      isRead: true,
    },
  ],
};

export const communities: Community[] = [
  { id: 'c1', name: 'Foodies', memberCount: 254, color: '#FF6B35' },
  { id: 'c2', name: 'Daily Inspiration', memberCount: 617, color: '#FF4081' },
  { id: 'c3', name: 'Football', memberCount: 425, color: '#34C759' },
  { id: 'c4', name: 'Families', memberCount: 103, color: '#007AFF' },
  { id: 'c5', name: 'Sneakerheads', memberCount: 178, color: '#FF9500' },
  { id: 'c6', name: 'Meditation', memberCount: 574, color: '#AF52DE' },
  { id: 'c7', name: 'Fashion', memberCount: 301, color: '#FF2D55' },
  { id: 'c8', name: 'Comedy', memberCount: 645, color: '#FFD60A' },
  { id: 'c9', name: 'Investing', memberCount: 383, color: '#30D158' },
  { id: 'c10', name: 'Fitness', memberCount: 296, color: '#64D2FF' },
];

export const insightLocations: InsightLocation[] = [
  { city: 'New York, NY', percentage: 1.0 },
  { city: 'Los Angeles, CA', percentage: 0.8 },
  { city: 'Austin, TX', percentage: 0.6 },
  { city: 'Seattle, WA', percentage: 0.4 },
  { city: 'Chicago, IL', percentage: 0.3 },
  { city: 'Miami, FL', percentage: 0.2 },
];

export const ageDemographics: AgeDemographic[] = [
  { range: '18-24', percentage: 39 },
  { range: '25-34', percentage: 49 },
  { range: '35-44', percentage: 8 },
  { range: '45-54', percentage: 3 },
  { range: '55+', percentage: 1 },
];

export const activeFriends: User[] = [
  { id: 'f1', name: 'Frank', avatar: 'https://i.pravatar.cc/150?u=frank', online: true },
  { id: 'f2', name: 'Jason', avatar: 'https://i.pravatar.cc/150?u=jasonf', online: true },
  { id: 'f3', name: 'Lee', avatar: 'https://i.pravatar.cc/150?u=lee', online: true },
  { id: 'f4', name: 'Lily', avatar: 'https://i.pravatar.cc/150?u=lilyf', online: true },
  { id: 'f5', name: 'Harrison', avatar: 'https://i.pravatar.cc/150?u=harrison', online: true },
];

export function getChatById(id: string): Chat | undefined {
  return chats.find((chat) => chat.id === id);
}

export function getMessagesForChat(id: string): Message[] {
  return messagesByChat[id] ?? [
    {
      id: 'default',
      senderId: 'system',
      text: 'Start a conversation...',
      timestamp: 'Now',
      isOwn: false,
    },
  ];
}
