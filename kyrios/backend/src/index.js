import express from 'express'
import cors from 'cors'
import jwt from 'jsonwebtoken'
import bcrypt from 'bcryptjs'
import { v4 as uuidv4 } from 'uuid'
import { WebSocketServer } from 'ws'
import { createServer } from 'http'
import { initDatabase } from './db.js'

const JWT_SECRET = process.env.JWT_SECRET || 'kyrios-secret-key-2026'
const PORT = process.env.PORT || 3001

const db = initDatabase()
const app = express()
const server = createServer(app)
const wss = new WebSocketServer({ server, path: '/ws' })

app.use(cors())
app.use(express.json())

const clients = new Map()

function auth(req, res, next) {
  const token = req.headers.authorization?.replace('Bearer ', '')
  if (!token) return res.status(401).json({ error: 'Token requis' })
  try {
    req.user = jwt.verify(token, JWT_SECRET)
    next()
  } catch {
    res.status(401).json({ error: 'Token invalide' })
  }
}

function broadcast(conversationId, data) {
  const payload = JSON.stringify(data)
  for (const [, ws] of clients) {
    if (ws.readyState === 1) ws.send(payload)
  }
}

wss.on('connection', (ws, req) => {
  const url = new URL(req.url, 'http://localhost')
  const token = url.searchParams.get('token')
  if (!token) { ws.close(); return }
  try {
    const user = jwt.verify(token, JWT_SECRET)
    clients.set(user.id, ws)
    ws.on('close', () => clients.delete(user.id))
  } catch { ws.close() }
})

// ============ AUTH ============
app.post('/api/auth/register', (req, res) => {
  const { email, password, username, displayName } = req.body
  if (!email || !password || !username) return res.status(400).json({ error: 'Champs requis manquants' })
  const existing = db.prepare('SELECT id FROM users WHERE email = ? OR username = ?').get(email, username)
  if (existing) return res.status(409).json({ error: 'Email ou username déjà utilisé' })
  const id = uuidv4().replace(/-/g, '')
  const hash = bcrypt.hashSync(password, 10)
  db.prepare('INSERT INTO users (id, email, password_hash, username, display_name) VALUES (?, ?, ?, ?, ?)').run(id, email, hash, username, displayName || username)
  const token = jwt.sign({ id, username }, JWT_SECRET, { expiresIn: '30d' })
  res.json({ token, user: { id, email, username, displayName: displayName || username } })
})

app.post('/api/auth/login', (req, res) => {
  const { email, password } = req.body
  const user = db.prepare('SELECT * FROM users WHERE email = ?').get(email)
  if (!user || !bcrypt.compareSync(password, user.password_hash)) return res.status(401).json({ error: 'Identifiants invalides' })
  db.prepare('UPDATE users SET is_online = 1, last_seen = datetime(\'now\') WHERE id = ?').run(user.id)
  const token = jwt.sign({ id: user.id, username: user.username }, JWT_SECRET, { expiresIn: '30d' })
  res.json({ token, user: { id: user.id, email: user.email, username: user.username, displayName: user.display_name, avatarUrl: user.avatar_url, bio: user.bio } })
})

app.get('/api/auth/me', auth, (req, res) => {
  const user = db.prepare('SELECT id, email, username, display_name, avatar_url, bio, is_online FROM users WHERE id = ?').get(req.user.id)
  res.json({ id: user.id, email: user.email, username: user.username, displayName: user.display_name, avatarUrl: user.avatar_url, bio: user.bio, isOnline: user.is_online })
})

// ============ USERS ============
app.get('/api/users', auth, (req, res) => {
  const users = db.prepare('SELECT id, username, display_name, avatar_url, is_online FROM users WHERE id != ?').all(req.user.id)
  res.json(users.map(u => ({ id: u.id, username: u.username, displayName: u.display_name, avatarUrl: u.avatar_url, isOnline: u.is_online })))
})

app.get('/api/users/:id', auth, (req, res) => {
  const user = db.prepare('SELECT id, username, display_name, avatar_url, bio, is_online FROM users WHERE id = ?').get(req.params.id)
  if (!user) return res.status(404).json({ error: 'Utilisateur introuvable' })
  const followers = db.prepare('SELECT COUNT(*) as c FROM follows WHERE following_id = ?').get(req.params.id).c
  const following = db.prepare('SELECT COUNT(*) as c FROM follows WHERE follower_id = ?').get(req.params.id).c
  const posts = db.prepare('SELECT COUNT(*) as c FROM posts WHERE author_id = ?').get(req.params.id).c
  res.json({ id: user.id, username: user.username, displayName: user.display_name, avatarUrl: user.avatar_url, bio: user.bio, isOnline: user.is_online, stats: { followers, following, posts } })
})

// ============ STORIES ============
app.get('/api/stories', auth, (req, res) => {
  const stories = db.prepare(`
    SELECT s.*, u.display_name, u.avatar_url, u.username
    FROM stories s JOIN users u ON s.user_id = u.id
    WHERE s.expires_at > datetime('now') ORDER BY s.created_at DESC
  `).all()
  res.json(stories.map(s => ({ id: s.id, userId: s.user_id, name: s.display_name, avatar: s.avatar_url, mediaUrl: s.media_url, isLive: !!s.is_live })))
})

// ============ CONVERSATIONS ============
app.get('/api/conversations', auth, (req, res) => {
  const convs = db.prepare(`
    SELECT c.*, 
      (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
      (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_time,
      (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.created_at > COALESCE(cm.last_read_at, '1970-01-01') AND m.sender_id != ?) as unread
    FROM conversations c
    JOIN conversation_members cm ON c.id = cm.conversation_id
    WHERE cm.user_id = ?
    ORDER BY last_time DESC
  `).all(req.user.id, req.user.id)

  res.json(convs.map(c => ({
    id: c.id, name: c.name || 'Chat', avatar: c.avatar_url, isGroup: !!c.is_group,
    lastMessage: c.last_message, time: c.last_time, unread: c.unread,
  })))
})

app.get('/api/conversations/:id/messages', auth, (req, res) => {
  const messages = db.prepare(`
    SELECT m.*, u.display_name, u.avatar_url
    FROM messages m JOIN users u ON m.sender_id = u.id
    WHERE m.conversation_id = ? ORDER BY m.created_at ASC
  `).all(req.params.id)

  const result = messages.map(m => {
    const media = db.prepare('SELECT media_url FROM message_media WHERE message_id = ? ORDER BY sort_order').all(m.id).map(r => r.media_url)
    const reactions = db.prepare('SELECT emoji, COUNT(*) as count FROM message_reactions WHERE message_id = ? GROUP BY emoji').all(m.id)
    return {
      id: m.id, senderId: m.sender_id, senderName: m.display_name, senderAvatar: m.avatar_url,
      text: m.content, images: media.length ? media : undefined,
      reactions: reactions.map(r => ({ emoji: r.emoji, count: r.count })),
      time: m.created_at, isOwn: m.sender_id === req.user.id,
    }
  })
  db.prepare('UPDATE conversation_members SET last_read_at = datetime(\'now\') WHERE conversation_id = ? AND user_id = ?').run(req.params.id, req.user.id)
  res.json(result)
})

app.post('/api/conversations/:id/messages', auth, (req, res) => {
  const { content } = req.body
  const id = uuidv4().replace(/-/g, '')
  db.prepare('INSERT INTO messages (id, conversation_id, sender_id, content) VALUES (?, ?, ?, ?)').run(id, req.params.id, req.user.id, content)
  db.prepare('UPDATE conversations SET updated_at = datetime(\'now\') WHERE id = ?').run(req.params.id)
  const user = db.prepare('SELECT display_name, avatar_url FROM users WHERE id = ?').get(req.user.id)
  const msg = { id, conversationId: req.params.id, senderId: req.user.id, senderName: user.display_name, senderAvatar: user.avatar_url, text: content, time: new Date().toISOString(), isOwn: true }
  broadcast(req.params.id, { type: 'new_message', message: msg })
  res.json(msg)
})

// ============ POSTS / DISCOVER ============
app.get('/api/posts', auth, (req, res) => {
  const posts = db.prepare(`
    SELECT p.*, u.display_name, u.username, u.avatar_url,
      (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes,
      (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comments
    FROM posts p JOIN users u ON p.author_id = u.id ORDER BY p.created_at DESC
  `).all()
  res.json(posts.map(p => ({
    id: p.id, author: p.display_name, handle: '@' + p.username, avatar: p.avatar_url,
    caption: p.caption, image: p.image_url, likes: p.likes, comments: p.comments, time: p.created_at,
  })))
})

app.post('/api/posts/:id/like', auth, (req, res) => {
  try {
    db.prepare('INSERT INTO post_likes (id, post_id, user_id) VALUES (?, ?, ?)').run(uuidv4().replace(/-/g, ''), req.params.id, req.user.id)
  } catch { /* already liked */ }
  res.json({ ok: true })
})

// ============ COMMUNITIES ============
app.get('/api/communities', auth, (req, res) => {
  const comms = db.prepare(`
    SELECT c.*, (SELECT COUNT(*) FROM community_members WHERE community_id = c.id) as members FROM communities c
  `).all()
  res.json(comms.map(c => ({ id: c.id, name: c.name, icon: c.icon, color: c.color, members: c.members })))
})

app.post('/api/communities/:id/join', auth, (req, res) => {
  try {
    db.prepare('INSERT INTO community_members (id, community_id, user_id) VALUES (?, ?, ?)').run(uuidv4().replace(/-/g, ''), req.params.id, req.user.id)
  } catch { /* already member */ }
  res.json({ ok: true })
})

// ============ CALLS ============
app.get('/api/calls', auth, (req, res) => {
  const calls = db.prepare(`
    SELECT c.*, u.display_name, u.avatar_url FROM calls c
    JOIN users u ON (c.caller_id = u.id AND c.caller_id != ?) OR (c.callee_id = u.id AND c.callee_id != ?)
    WHERE c.caller_id = ? OR c.callee_id = ?
    ORDER BY c.started_at DESC LIMIT 20
  `).all(req.user.id, req.user.id, req.user.id, req.user.id)
  res.json(calls.map(c => ({
    id: c.id, name: c.display_name, avatar: c.avatar_url, type: c.direction,
    time: c.started_at, duration: c.duration_seconds ? `${Math.floor(c.duration_seconds/60)}:${String(c.duration_seconds%60).padStart(2,'0')}` : '',
  })))
})

// ============ NOTIFICATIONS ============
app.get('/api/notifications', auth, (req, res) => {
  const notifs = db.prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50').all(req.user.id)
  res.json(notifs.map(n => ({ id: n.id, type: n.type, title: n.title, body: n.body, isRead: !!n.is_read, time: n.created_at })))
})

app.get('/api/health', (_, res) => res.json({ status: 'ok', app: 'KYRIOS', version: '1.0.0' }))

server.listen(PORT, '0.0.0.0', () => {
  console.log(`KYRIOS API running on http://0.0.0.0:${PORT}`)
})
