export type User = {
  id: string;
  name: string;
  handle?: string;
  avatar: string;
  online?: boolean;
  initials?: string;
};

export type Message = {
  id: string;
  senderId: string;
  text: string;
  time: string;
  outgoing?: boolean;
  type?: "text" | "image" | "voice" | "video";
  imageUrl?: string;
  voiceDuration?: string;
  reactions?: { emoji: string; count: number }[];
};

export type Conversation = {
  id: string;
  user: User;
  lastMessage: string;
  time: string;
  unread?: number;
  typing?: boolean;
  read?: boolean;
};

export type Community = {
  id: string;
  name: string;
  members: number;
  color: string;
};

export type Post = {
  id: string;
  author: User;
  text: string;
  time: string;
  community?: string;
  images?: string[];
  likes: number;
  comments: number;
};

export const currentUser: User = {
  id: "me",
  name: "Alex Morgan",
  handle: "@alex_m",
  avatar: "https://i.pravatar.cc/150?u=alex",
  online: true,
};

export const activeFriends: User[] = [
  { id: "f1", name: "Frank", avatar: "https://i.pravatar.cc/150?u=frank", online: true },
  { id: "f2", name: "Jason", avatar: "https://i.pravatar.cc/150?u=jason", online: true },
  { id: "f3", name: "Lee", avatar: "https://i.pravatar.cc/150?u=lee", online: false },
  { id: "f4", name: "Lily", avatar: "https://i.pravatar.cc/150?u=lily", online: true },
  { id: "f5", name: "Harrison", avatar: "https://i.pravatar.cc/150?u=harrison", online: true },
];

export const conversations: Conversation[] = [
  {
    id: "c1",
    user: { id: "u1", name: "Peter Johnson", avatar: "https://i.pravatar.cc/150?u=peter", online: true },
    lastMessage: "Sure, It happens to me all the time...",
    time: "15m",
    unread: 2,
  },
  {
    id: "c2",
    user: { id: "u2", name: "Lily Fallen", avatar: "https://i.pravatar.cc/150?u=lilyf", online: true },
    lastMessage: "Typing...",
    time: "1m",
    typing: true,
  },
  {
    id: "c3",
    user: { id: "u3", name: "Justin Bryant", avatar: "https://i.pravatar.cc/150?u=justin", initials: "JB" },
    lastMessage: "See you at the meetup tomorrow!",
    time: "2h",
    read: true,
  },
  {
    id: "c4",
    user: { id: "u4", name: "Devon McCoy", avatar: "https://i.pravatar.cc/150?u=devon", initials: "DM" },
    lastMessage: "Thanks for the intro 🙌",
    time: "5h",
    unread: 1,
  },
  {
    id: "c5",
    user: { id: "u5", name: "Visit Denpasar", avatar: "https://i.pravatar.cc/150?u=denpasar" },
    lastMessage: "Photos from the trip are ready",
    time: "24m",
    unread: 4,
  },
  {
    id: "c6",
    user: { id: "u6", name: "Nia Denton", avatar: "https://i.pravatar.cc/150?u=nia", initials: "ND" },
    lastMessage: "OMG you're so funny! 😂😂😂",
    time: "3m",
  },
];

export const chatMessages: Record<string, Message[]> = {
  c1: [
    { id: "m1", senderId: "u1", text: "Wassup!!!!!!...", time: "8:10 PM", outgoing: false, type: "text" },
    {
      id: "m2",
      senderId: "u1",
      text: "",
      time: "8:12 PM",
      outgoing: false,
      type: "image",
      imageUrl: "https://images.unsplash.com/photo-1487958449943-2429e8be8622?w=400",
    },
    {
      id: "m3",
      senderId: "u1",
      text: "",
      time: "8:13 PM",
      outgoing: false,
      type: "voice",
      voiceDuration: "00:43",
    },
    { id: "m4", senderId: "me", text: "Ha ha glad you enjoyed that one.", time: "8:15 PM", outgoing: true },
    { id: "m5", senderId: "me", text: "Sure, It happens to me all the time...", time: "8:16 PM", outgoing: true, read: true } as Message & { read?: boolean },
  ],
  c5: [
    { id: "g1", senderId: "u7", text: "The sunset was incredible!", time: "8:16 PM", outgoing: false },
    {
      id: "g2",
      senderId: "me",
      text: "",
      time: "8:18 PM",
      outgoing: true,
      type: "image",
      imageUrl: "https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?w=400",
      reactions: [
        { emoji: "🔥", count: 6 },
        { emoji: "❤️", count: 9 },
      ],
    },
    { id: "g3", senderId: "u8", text: "Can't wait for next year", time: "8:21 PM", outgoing: false },
  ],
  c6: [
    {
      id: "n1",
      senderId: "u6",
      text: "OMG you're so funny! 😂😂😂",
      time: "2:30 PM",
      outgoing: false,
      type: "text",
    },
    { id: "n2", senderId: "me", text: "Ha ha glad you enjoyed that one.", time: "2:31 PM", outgoing: true },
  ],
};

export const communities: Community[] = [
  { id: "co1", name: "Foodies", members: 254, color: "#F97316" },
  { id: "co2", name: "Daily Inspiration", members: 617, color: "#8B5CF6" },
  { id: "co3", name: "Football", members: 426, color: "#22C55E" },
  { id: "co4", name: "Families", members: 109, color: "#EC4899" },
  { id: "co5", name: "Sneakerheads", members: 178, color: "#EF4444" },
  { id: "co6", name: "Meditation", members: 574, color: "#06B6D4" },
  { id: "co7", name: "Fashion", members: 301, color: "#A855F7" },
  { id: "co8", name: "Comedy", members: 648, color: "#EAB308" },
  { id: "co9", name: "Investing", members: 383, color: "#14B8A6" },
  { id: "co10", name: "Fitness", members: 288, color: "#3B82F6" },
];

export const insights = {
  totalMembers: 14328,
  growth7d: 214,
  updatedHoursAgo: 3,
  locations: [
    { city: "New York, NY", pct: 1.0 },
    { city: "Los Angeles, CA", pct: 0.8 },
    { city: "Chicago, IL", pct: 0.6 },
    { city: "Houston, TX", pct: 0.5 },
    { city: "Miami, FL", pct: 0.4 },
  ],
  ageGroups: [
    { range: "13-17", pct: 4 },
    { range: "18-24", pct: 18 },
    { range: "25-34", pct: 49 },
    { range: "35-44", pct: 16 },
    { range: "45-54", pct: 8 },
    { range: "55-64", pct: 3 },
    { range: "65+", pct: 2 },
  ],
};

export const profileUser: User & {
  posts: number;
  followers: number;
  following: number;
  photos: string[];
} = {
  id: "p1",
  name: "Cassie Donk",
  handle: "@cassie_d",
  avatar: "https://i.pravatar.cc/150?u=cassie",
  posts: 426,
  followers: 987,
  following: 65,
  photos: [
    "https://images.unsplash.com/photo-1487958449943-2429e8be8622?w=300",
    "https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?w=300",
    "https://images.unsplash.com/photo-1514565131-fce0801e5785?w=300",
    "https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=300",
    "https://images.unsplash.com/photo-1477959858617-67f85cf4f1df?w=300",
    "https://images.unsplash.com/photo-1519501025264-65ba15a82390?w=300",
  ],
};

export const feedPosts: Post[] = [
  {
    id: "post1",
    author: { id: "u9", name: "Nilesh", avatar: "https://i.pravatar.cc/150?u=nilesh" },
    text: "Discover adventure in patagonia's peaks or serenity provence's @hamlets - arrival",
    time: "1h ago",
    community: "u8s",
    images: [
      "https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?w=400",
      "https://images.unsplash.com/photo-1487958449943-2429e8be8622?w=400",
    ],
    likes: 2500,
    comments: 1500,
  },
  {
    id: "post2",
    author: currentUser,
    text: "Chasing the wind and soaking up every ray... There's nothing like a day out on water to feel free.",
    time: "2h ago",
    images: ["https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=400"],
    likes: 2500,
    comments: 1500,
  },
];

export function getConversation(id: string) {
  return conversations.find((c) => c.id === id);
}

export function getMessages(conversationId: string): Message[] {
  return chatMessages[conversationId] ?? [];
}
