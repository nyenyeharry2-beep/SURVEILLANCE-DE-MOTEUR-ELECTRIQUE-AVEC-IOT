export interface User {
  id: string;
  name: string;
  avatar: string;
  online?: boolean;
  live?: boolean;
}

export interface Story extends User {
  isOwn?: boolean;
}

export interface Chat {
  id: string;
  name: string;
  avatar: string;
  lastMessage: string;
  timestamp: string;
  unreadCount?: number;
  isGroup?: boolean;
  isRead?: boolean;
  isTyping?: boolean;
  online?: boolean;
  category?: 'favorites' | 'work' | 'groups';
}

export interface Message {
  id: string;
  senderId: string;
  senderName?: string;
  text?: string;
  timestamp: string;
  isOwn: boolean;
  images?: string[];
  reactions?: { emoji: string; count: number }[];
  isRead?: boolean;
}

export interface Community {
  id: string;
  name: string;
  memberCount: number;
  color: string;
}

export interface InsightLocation {
  city: string;
  percentage: number;
}

export interface AgeDemographic {
  range: string;
  percentage: number;
}

export type ChatFilter = 'all' | 'favorites' | 'work' | 'groups';
