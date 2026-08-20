-- KYRIOS Database Schema (PostgreSQL / SQLite compatible)
-- Application de messagerie et réseau social

-- ============================================
-- USERS & AUTH
-- ============================================

CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    username TEXT UNIQUE NOT NULL,
    display_name TEXT NOT NULL,
    avatar_url TEXT,
    bio TEXT DEFAULT '',
    is_online INTEGER DEFAULT 0,
    last_seen TEXT DEFAULT (datetime('now')),
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS user_sessions (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token TEXT UNIQUE NOT NULL,
    expires_at TEXT NOT NULL,
    created_at TEXT DEFAULT (datetime('now'))
);

-- ============================================
-- STORIES
-- ============================================

CREATE TABLE IF NOT EXISTS stories (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    media_url TEXT NOT NULL,
    media_type TEXT DEFAULT 'image',
    is_live INTEGER DEFAULT 0,
    expires_at TEXT NOT NULL,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS story_views (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    story_id TEXT NOT NULL REFERENCES stories(id) ON DELETE CASCADE,
    viewer_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    viewed_at TEXT DEFAULT (datetime('now')),
    UNIQUE(story_id, viewer_id)
);

-- ============================================
-- CHATS & MESSAGES
-- ============================================

CREATE TABLE IF NOT EXISTS conversations (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    name TEXT,
    avatar_url TEXT,
    is_group INTEGER DEFAULT 0,
    created_by TEXT REFERENCES users(id),
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS conversation_members (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    conversation_id TEXT NOT NULL REFERENCES conversations(id) ON DELETE CASCADE,
    user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role TEXT DEFAULT 'member',
    joined_at TEXT DEFAULT (datetime('now')),
    last_read_at TEXT,
    UNIQUE(conversation_id, user_id)
);

CREATE TABLE IF NOT EXISTS messages (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    conversation_id TEXT NOT NULL REFERENCES conversations(id) ON DELETE CASCADE,
    sender_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    content TEXT,
    message_type TEXT DEFAULT 'text',
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS message_media (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    message_id TEXT NOT NULL REFERENCES messages(id) ON DELETE CASCADE,
    media_url TEXT NOT NULL,
    media_type TEXT DEFAULT 'image',
    sort_order INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS message_reactions (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    message_id TEXT NOT NULL REFERENCES messages(id) ON DELETE CASCADE,
    user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    emoji TEXT NOT NULL,
    created_at TEXT DEFAULT (datetime('now')),
    UNIQUE(message_id, user_id, emoji)
);

-- ============================================
-- SOCIAL / DISCOVER
-- ============================================

CREATE TABLE IF NOT EXISTS posts (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    author_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    caption TEXT,
    image_url TEXT,
    community_id TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS post_likes (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    post_id TEXT NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TEXT DEFAULT (datetime('now')),
    UNIQUE(post_id, user_id)
);

CREATE TABLE IF NOT EXISTS post_comments (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    post_id TEXT NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    content TEXT NOT NULL,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS follows (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    follower_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    following_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TEXT DEFAULT (datetime('now')),
    UNIQUE(follower_id, following_id)
);

-- ============================================
-- COMMUNITIES
-- ============================================

CREATE TABLE IF NOT EXISTS communities (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    name TEXT NOT NULL,
    icon TEXT DEFAULT '👥',
    color TEXT DEFAULT '#6366f1',
    description TEXT DEFAULT '',
    created_by TEXT REFERENCES users(id),
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS community_members (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    community_id TEXT NOT NULL REFERENCES communities(id) ON DELETE CASCADE,
    user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role TEXT DEFAULT 'member',
    joined_at TEXT DEFAULT (datetime('now')),
    UNIQUE(community_id, user_id)
);

-- ============================================
-- CALLS
-- ============================================

CREATE TABLE IF NOT EXISTS calls (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    caller_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    callee_id TEXT REFERENCES users(id),
    conversation_id TEXT REFERENCES conversations(id),
    call_type TEXT DEFAULT 'voice',
    direction TEXT NOT NULL,
    status TEXT DEFAULT 'completed',
    duration_seconds INTEGER DEFAULT 0,
    started_at TEXT DEFAULT (datetime('now')),
    ended_at TEXT
);

-- ============================================
-- NOTIFICATIONS
-- ============================================

CREATE TABLE IF NOT EXISTS notifications (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type TEXT NOT NULL,
    title TEXT NOT NULL,
    body TEXT,
    data_json TEXT,
    is_read INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now'))
);

-- ============================================
-- INDEXES
-- ============================================

CREATE INDEX IF NOT EXISTS idx_messages_conversation ON messages(conversation_id, created_at);
CREATE INDEX IF NOT EXISTS idx_messages_sender ON messages(sender_id);
CREATE INDEX IF NOT EXISTS idx_conversation_members_user ON conversation_members(user_id);
CREATE INDEX IF NOT EXISTS idx_posts_author ON posts(author_id, created_at);
CREATE INDEX IF NOT EXISTS idx_stories_user ON stories(user_id, expires_at);
CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id, is_read);
CREATE INDEX IF NOT EXISTS idx_sessions_token ON user_sessions(token);
