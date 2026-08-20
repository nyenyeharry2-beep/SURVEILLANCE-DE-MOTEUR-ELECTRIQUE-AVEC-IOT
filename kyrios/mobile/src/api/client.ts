// Change this to your server IP when testing on a real device
// Android emulator: http://10.0.2.2:3001
// Local network: http://YOUR_IP:3001
export const API_URL = 'http://10.0.2.2:3001'

export interface User {
  id: string
  email: string
  username: string
  displayName: string
  avatarUrl?: string
  bio?: string
  isOnline?: boolean
}

export interface Chat {
  id: string
  name: string
  avatar: string
  lastMessage: string
  time: string
  unread: number
  isGroup: boolean
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
  isOwn: boolean
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

export interface Community {
  id: string
  name: string
  icon: string
  color: string
  members: number
}

export interface Call {
  id: string
  name: string
  avatar: string
  type: string
  time: string
  duration: string
}

export interface Story {
  id: string
  userId: string
  name: string
  avatar: string
  mediaUrl: string
  isLive: boolean
}

let token: string | null = null

export function setToken(t: string | null) { token = t }
export function getToken() { return token }

async function api<T>(path: string, options: RequestInit = {}): Promise<T> {
  const headers: Record<string, string> = { 'Content-Type': 'application/json', ...(options.headers as Record<string, string> || {}) }
  if (token) headers.Authorization = `Bearer ${token}`
  const res = await fetch(`${API_URL}${path}`, { ...options, headers })
  if (!res.ok) {
    const err = await res.json().catch(() => ({ error: res.statusText }))
    throw new Error(err.error || 'Erreur API')
  }
  return res.json()
}

export const auth = {
  login: (email: string, password: string) => api<{ token: string; user: User }>('/api/auth/login', { method: 'POST', body: JSON.stringify({ email, password }) }),
  register: (email: string, password: string, username: string, displayName: string) => api<{ token: string; user: User }>('/api/auth/register', { method: 'POST', body: JSON.stringify({ email, password, username, displayName }) }),
  me: () => api<User>('/api/auth/me'),
}

export const chats = {
  list: () => api<Chat[]>('/api/conversations'),
  messages: (id: string) => api<Message[]>(`/api/conversations/${id}/messages`),
  send: (id: string, content: string) => api<Message>(`/api/conversations/${id}/messages`, { method: 'POST', body: JSON.stringify({ content }) }),
}

export const discover = {
  posts: () => api<Post[]>('/api/posts'),
  like: (id: string) => api<{ ok: boolean }>(`/api/posts/${id}/like`, { method: 'POST' }),
}

export const stories = {
  list: () => api<Story[]>('/api/stories'),
}

export const communities = {
  list: () => api<Community[]>('/api/communities'),
  join: (id: string) => api<{ ok: boolean }>(`/api/communities/${id}/join`, { method: 'POST' }),
}

export const calls = {
  list: () => api<Call[]>('/api/calls'),
}

export const users = {
  get: (id: string) => api<User & { stats: { followers: number; following: number; posts: number } }>(`/api/users/${id}`),
}
