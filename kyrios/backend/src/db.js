import Database from 'better-sqlite3'
import { readFileSync, existsSync, mkdirSync } from 'fs'
import { dirname, join } from 'path'
import { fileURLToPath } from 'url'
import bcrypt from 'bcryptjs'

const __dirname = dirname(fileURLToPath(import.meta.url))
const DB_PATH = join(__dirname, '../../database/kyrios.db')
const SCHEMA_PATH = join(__dirname, '../../database/schema.sql')
const SEED_PATH = join(__dirname, '../../database/seed.sql')

export function initDatabase() {
  mkdirSync(dirname(DB_PATH), { recursive: true })
  const db = new Database(DB_PATH)
  db.pragma('journal_mode = WAL')
  db.pragma('foreign_keys = ON')

  const schema = readFileSync(SCHEMA_PATH, 'utf-8')
  db.exec(schema)

  const userCount = db.prepare('SELECT COUNT(*) as c FROM users').get().c
  if (userCount === 0) {
    seedDatabase(db)
  }

  return db
}

function seedDatabase(db) {
  const hash = bcrypt.hashSync('Kyrios2026!', 10)

  const users = [
    ['user-me', 'me@kyrios.app', 'me', 'Moi', 'https://i.pravatar.cc/150?u=me', 1],
    ['user-kira', 'kira@kyrios.app', 'kira', 'Kira Lindegaard', 'https://i.pravatar.cc/150?u=kira', 1],
    ['user-khai', 'khai@kyrios.app', 'khai', 'Khai Azzahra', 'https://i.pravatar.cc/150?u=khai', 0],
    ['user-akbar', 'akbar@kyrios.app', 'akbar', 'Akbar Lazuardi', 'https://i.pravatar.cc/150?u=akbar', 1],
    ['user-haris', 'haris@kyrios.app', 'haris', 'Haris', 'https://i.pravatar.cc/150?u=haris', 1],
    ['user-darlene', 'darlene@kyrios.app', 'dw_beats', 'Darlene Beats', 'https://i.pravatar.cc/200?u=darlene', 1],
    ['user-nilesh', 'nilesh@kyrios.app', 'nilesh', 'Nilesh', 'https://i.pravatar.cc/150?u=nilesh', 1],
    ['user-justin', 'justin@kyrios.app', 'justin', 'Justin Bryant', 'https://i.pravatar.cc/150?u=justin', 0],
  ]

  const insertUser = db.prepare(
    'INSERT INTO users (id, email, password_hash, username, display_name, avatar_url, is_online) VALUES (?, ?, ?, ?, ?, ?, ?)'
  )
  for (const u of users) insertUser.run(u[0], u[1], hash, u[2], u[3], u[4], u[5])

  db.prepare(`INSERT INTO conversations (id, name, avatar_url, is_group, created_by) VALUES
    ('conv-denpasar', 'Visit Denpasar', 'https://i.pravatar.cc/150?u=denpasar', 1, 'user-kira'),
    ('conv-kira', NULL, 'https://i.pravatar.cc/150?u=kira', 0, 'user-me'),
    ('conv-bestgirls', 'Best girls', 'https://i.pravatar.cc/150?u=bestgirls', 1, 'user-darlene')`).run()

  const members = [
    ['conv-denpasar', 'user-me'], ['conv-denpasar', 'user-kira'], ['conv-denpasar', 'user-khai'], ['conv-denpasar', 'user-akbar'],
    ['conv-kira', 'user-me'], ['conv-kira', 'user-kira'],
    ['conv-bestgirls', 'user-me'], ['conv-bestgirls', 'user-darlene'],
  ]
  const insertMember = db.prepare('INSERT INTO conversation_members (id, conversation_id, user_id) VALUES (?, ?, ?)')
  members.forEach(([c, u], i) => insertMember.run(`cm-${i}`, c, u))

  const insertMsg = db.prepare('INSERT INTO messages (id, conversation_id, sender_id, content, message_type, created_at) VALUES (?, ?, ?, ?, ?, ?)')
  insertMsg.run('msg-1', 'conv-denpasar', 'user-kira', 'Hey everyone! I found this amazing spot in Denpasar 🌴', 'text', new Date(Date.now() - 7200000).toISOString())
  insertMsg.run('msg-2', 'conv-denpasar', 'user-khai', 'Are they still open at sunday?', 'text', new Date(Date.now() - 3600000).toISOString())
  insertMsg.run('msg-3', 'conv-denpasar', 'user-akbar', 'Check these photos!', 'media', new Date(Date.now() - 3300000).toISOString())
  insertMsg.run('msg-4', 'conv-kira', 'user-kira', 'See you tomorrow! ✨', 'text', new Date(Date.now() - 120000).toISOString())
  insertMsg.run('msg-5', 'conv-bestgirls', 'user-darlene', "Guys I just made $10,000 from Youtube. Let's party!", 'text', new Date(Date.now() - 20000).toISOString())

  const insertMedia = db.prepare('INSERT INTO message_media (id, message_id, media_url, sort_order) VALUES (?, ?, ?, ?)')
  ;[
    ['mm-1', 'msg-3', 'https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?w=200&h=200&fit=crop', 0],
    ['mm-2', 'msg-3', 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=200&h=200&fit=crop', 1],
    ['mm-3', 'msg-3', 'https://images.unsplash.com/photo-1555404738-9f2795b1dbe8?w=200&h=200&fit=crop', 2],
  ].forEach(m => insertMedia.run(...m))

  db.prepare(`INSERT INTO message_reactions (id, message_id, user_id, emoji) VALUES ('mr-1','msg-3','user-kira','🔥'),('mr-2','msg-3','user-khai','🔥'),('mr-3','msg-3','user-me','😍')`).run()

  db.prepare(`INSERT INTO stories (id, user_id, media_url, is_live, expires_at) VALUES
    ('story-1','user-haris','https://i.pravatar.cc/150?u=haris',1,datetime('now','+1 day')),
    ('story-2','user-kira','https://i.pravatar.cc/150?u=kira',0,datetime('now','+1 day'))`).run()

  const communities = [
    ['comm-1','Foodies','🍕','#f97316'], ['comm-2','Daily Inspiration','✨','#a855f7'],
    ['comm-3','Football','⚽','#22c55e'], ['comm-4','Sneakerheads','👟','#3b82f6'],
    ['comm-5','Photography','📷','#ec4899'], ['comm-6','Travel','✈️','#06b6d4'],
  ]
  const insertComm = db.prepare('INSERT INTO communities (id, name, icon, color, created_by) VALUES (?, ?, ?, ?, ?)')
  communities.forEach(c => insertComm.run(c[0], c[1], c[2], c[3], 'user-me'))

  db.prepare(`INSERT INTO posts (id, author_id, caption, image_url, created_at) VALUES
    ('post-1','user-nilesh','Discover adventure in patagonia peaks','https://images.unsplash.com/photo-1682687220063-4742bd7fd538?w=400&h=300&fit=crop',datetime('now','-1 hour')),
    ('post-2','user-darlene','Golden hour in the city never gets old','https://images.unsplash.com/photo-1480714378408-67cf0d13bc1b?w=400&h=300&fit=crop',datetime('now','-3 hours'))`).run()

  db.prepare(`INSERT INTO calls (id, caller_id, callee_id, call_type, direction, status, duration_seconds, started_at) VALUES
    ('call-1','user-kira','user-me','voice','incoming','completed',324,datetime('now','-3 hours')),
    ('call-2','user-me','user-justin','voice','outgoing','completed',721,datetime('now','-1 day')),
    ('call-3','user-darlene','user-me','voice','incoming','missed',0,datetime('now','-2 days'))`).run()

  db.prepare(`INSERT INTO notifications (id, user_id, type, title, body, is_read) VALUES
    ('notif-1','user-me','message','Nouveau message','Kira: See you tomorrow!',0),
    ('notif-2','user-me','like','Nouveau like','Darlene a aimé votre post',0),
    ('notif-3','user-me','follow','Nouveau follower','Haris vous suit',1)`).run()

  console.log('Database seeded successfully')
}

export { DB_PATH }
