-- KYRIOS MySQL Schema for InfinityFree / phpMyAdmin
-- Import this file in phpMyAdmin on InfinityFree

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(32) PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    avatar_url TEXT,
    bio TEXT DEFAULT '',
    is_online TINYINT(1) DEFAULT 0,
    last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_sessions (
    id VARCHAR(32) PRIMARY KEY,
    user_id VARCHAR(32) NOT NULL,
    token VARCHAR(512) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stories (
    id VARCHAR(32) PRIMARY KEY,
    user_id VARCHAR(32) NOT NULL,
    media_url TEXT NOT NULL,
    media_type VARCHAR(20) DEFAULT 'image',
    is_live TINYINT(1) DEFAULT 0,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS conversations (
    id VARCHAR(32) PRIMARY KEY,
    name VARCHAR(255),
    avatar_url TEXT,
    is_group TINYINT(1) DEFAULT 0,
    created_by VARCHAR(32),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS conversation_members (
    id VARCHAR(32) PRIMARY KEY,
    conversation_id VARCHAR(32) NOT NULL,
    user_id VARCHAR(32) NOT NULL,
    role VARCHAR(20) DEFAULT 'member',
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_read_at DATETIME,
    UNIQUE KEY uk_conv_user (conversation_id, user_id),
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
    id VARCHAR(32) PRIMARY KEY,
    conversation_id VARCHAR(32) NOT NULL,
    sender_id VARCHAR(32) NOT NULL,
    content TEXT,
    message_type VARCHAR(20) DEFAULT 'text',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS message_media (
    id VARCHAR(32) PRIMARY KEY,
    message_id VARCHAR(32) NOT NULL,
    media_url TEXT NOT NULL,
    media_type VARCHAR(20) DEFAULT 'image',
    sort_order INT DEFAULT 0,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS message_reactions (
    id VARCHAR(32) PRIMARY KEY,
    message_id VARCHAR(32) NOT NULL,
    user_id VARCHAR(32) NOT NULL,
    emoji VARCHAR(10) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_reaction (message_id, user_id, emoji),
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS posts (
    id VARCHAR(32) PRIMARY KEY,
    author_id VARCHAR(32) NOT NULL,
    caption TEXT,
    image_url TEXT,
    community_id VARCHAR(32),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS post_likes (
    id VARCHAR(32) PRIMARY KEY,
    post_id VARCHAR(32) NOT NULL,
    user_id VARCHAR(32) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_like (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS post_comments (
    id VARCHAR(32) PRIMARY KEY,
    post_id VARCHAR(32) NOT NULL,
    user_id VARCHAR(32) NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS follows (
    id VARCHAR(32) PRIMARY KEY,
    follower_id VARCHAR(32) NOT NULL,
    following_id VARCHAR(32) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_follow (follower_id, following_id),
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS communities (
    id VARCHAR(32) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    icon VARCHAR(10) DEFAULT '👥',
    color VARCHAR(20) DEFAULT '#6366f1',
    description TEXT DEFAULT '',
    created_by VARCHAR(32),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS community_members (
    id VARCHAR(32) PRIMARY KEY,
    community_id VARCHAR(32) NOT NULL,
    user_id VARCHAR(32) NOT NULL,
    role VARCHAR(20) DEFAULT 'member',
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_comm_user (community_id, user_id),
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS calls (
    id VARCHAR(32) PRIMARY KEY,
    caller_id VARCHAR(32) NOT NULL,
    callee_id VARCHAR(32),
    conversation_id VARCHAR(32),
    call_type VARCHAR(20) DEFAULT 'voice',
    direction VARCHAR(20) NOT NULL,
    status VARCHAR(20) DEFAULT 'completed',
    duration_seconds INT DEFAULT 0,
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ended_at DATETIME,
    FOREIGN KEY (caller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (callee_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
    id VARCHAR(32) PRIMARY KEY,
    user_id VARCHAR(32) NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT,
    data_json TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Demo password for all accounts: Kyrios2026!
INSERT INTO users (id, email, password_hash, username, display_name, avatar_url, bio, is_online) VALUES
('user-me', 'me@kyrios.app', '$2y$10$oPtpEZ5UnOTXubcIT58cdud255dKmoH2a7HMBuf.sEn3cbCRVicOC', 'me', 'Moi', 'https://i.pravatar.cc/150?u=me', 'Utilisateur KYRIOS', 1),
('user-kira', 'kira@kyrios.app', '$2y$10$oPtpEZ5UnOTXubcIT58cdud255dKmoH2a7HMBuf.sEn3cbCRVicOC', 'kira', 'Kira Lindegaard', 'https://i.pravatar.cc/150?u=kira', 'Travel enthusiast', 1),
('user-khai', 'khai@kyrios.app', '$2y$10$oPtpEZ5UnOTXubcIT58cdud255dKmoH2a7HMBuf.sEn3cbCRVicOC', 'khai', 'Khai Azzahra', 'https://i.pravatar.cc/150?u=khai', '', 0),
('user-akbar', 'akbar@kyrios.app', '$2y$10$oPtpEZ5UnOTXubcIT58cdud255dKmoH2a7HMBuf.sEn3cbCRVicOC', 'akbar', 'Akbar Lazuardi', 'https://i.pravatar.cc/150?u=akbar', 'Photographer', 1),
('user-haris', 'haris@kyrios.app', '$2y$10$oPtpEZ5UnOTXubcIT58cdud255dKmoH2a7HMBuf.sEn3cbCRVicOC', 'haris', 'Haris', 'https://i.pravatar.cc/150?u=haris', '', 1),
('user-darlene', 'darlene@kyrios.app', '$2y$10$oPtpEZ5UnOTXubcIT58cdud255dKmoH2a7HMBuf.sEn3cbCRVicOC', 'dw_beats', 'Darlene Beats', 'https://i.pravatar.cc/200?u=darlene', 'Music producer', 1),
('user-nilesh', 'nilesh@kyrios.app', '$2y$10$oPtpEZ5UnOTXubcIT58cdud255dKmoH2a7HMBuf.sEn3cbCRVicOC', 'nilesh', 'Nilesh', 'https://i.pravatar.cc/150?u=nilesh', 'Adventure seeker', 1),
('user-justin', 'justin@kyrios.app', '$2y$10$oPtpEZ5UnOTXubcIT58cdud255dKmoH2a7HMBuf.sEn3cbCRVicOC', 'justin', 'Justin Bryant', 'https://i.pravatar.cc/150?u=justin', '', 0);

INSERT INTO conversations (id, name, avatar_url, is_group, created_by) VALUES
('conv-denpasar', 'Visit Denpasar', 'https://i.pravatar.cc/150?u=denpasar', 1, 'user-kira'),
('conv-kira', NULL, 'https://i.pravatar.cc/150?u=kira', 0, 'user-me'),
('conv-bestgirls', 'Best girls', 'https://i.pravatar.cc/150?u=bestgirls', 1, 'user-darlene');

INSERT INTO conversation_members (id, conversation_id, user_id) VALUES
('cm-1','conv-denpasar','user-me'),('cm-2','conv-denpasar','user-kira'),('cm-3','conv-denpasar','user-khai'),('cm-4','conv-denpasar','user-akbar'),
('cm-5','conv-kira','user-me'),('cm-6','conv-kira','user-kira'),('cm-7','conv-bestgirls','user-me'),('cm-8','conv-bestgirls','user-darlene');

INSERT INTO messages (id, conversation_id, sender_id, content, message_type, created_at) VALUES
('msg-1','conv-denpasar','user-kira','Hey everyone! I found this amazing spot in Denpasar 🌴','text', NOW() - INTERVAL 2 HOUR),
('msg-2','conv-denpasar','user-khai','Are they still open at sunday?','text', NOW() - INTERVAL 1 HOUR),
('msg-3','conv-denpasar','user-akbar','Check these photos!','media', NOW() - INTERVAL 55 MINUTE),
('msg-4','conv-kira','user-kira','See you tomorrow! ✨','text', NOW() - INTERVAL 2 MINUTE),
('msg-5','conv-bestgirls','user-darlene','Guys I just made $10,000 from Youtube. Let''s party!','text', NOW() - INTERVAL 20 SECOND);

INSERT INTO message_media (id, message_id, media_url, sort_order) VALUES
('mm-1','msg-3','https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?w=200&h=200&fit=crop',0),
('mm-2','msg-3','https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=200&h=200&fit=crop',1),
('mm-3','msg-3','https://images.unsplash.com/photo-1555404738-9f2795b1dbe8?w=200&h=200&fit=crop',2);

INSERT INTO message_reactions (id, message_id, user_id, emoji) VALUES
('mr-1','msg-3','user-kira','🔥'),('mr-2','msg-3','user-khai','🔥'),('mr-3','msg-3','user-me','😍');

INSERT INTO stories (id, user_id, media_url, is_live, expires_at) VALUES
('story-1','user-haris','https://i.pravatar.cc/150?u=haris',1, NOW() + INTERVAL 1 DAY),
('story-2','user-kira','https://i.pravatar.cc/150?u=kira',0, NOW() + INTERVAL 1 DAY);

INSERT INTO communities (id, name, icon, color, created_by) VALUES
('comm-1','Foodies','🍕','#f97316','user-me'),('comm-2','Daily Inspiration','✨','#a855f7','user-me'),
('comm-3','Football','⚽','#22c55e','user-me'),('comm-4','Sneakerheads','👟','#3b82f6','user-me'),
('comm-5','Photography','📷','#ec4899','user-me'),('comm-6','Travel','✈️','#06b6d4','user-me');

INSERT INTO posts (id, author_id, caption, image_url, created_at) VALUES
('post-1','user-nilesh','Discover adventure in patagonia peaks','https://images.unsplash.com/photo-1682687220063-4742bd7fd538?w=400&h=300&fit=crop', NOW() - INTERVAL 1 HOUR),
('post-2','user-darlene','Golden hour in the city never gets old','https://images.unsplash.com/photo-1480714378408-67cf0d13bc1b?w=400&h=300&fit=crop', NOW() - INTERVAL 3 HOUR);

INSERT INTO post_likes (id, post_id, user_id) VALUES ('pl-1','post-1','user-me'),('pl-2','post-1','user-kira'),('pl-3','post-2','user-me');

INSERT INTO calls (id, caller_id, callee_id, call_type, direction, status, duration_seconds, started_at) VALUES
('call-1','user-kira','user-me','voice','incoming','completed',324, NOW() - INTERVAL 3 HOUR),
('call-2','user-me','user-justin','voice','outgoing','completed',721, NOW() - INTERVAL 1 DAY),
('call-3','user-darlene','user-me','voice','incoming','missed',0, NOW() - INTERVAL 2 DAY);

INSERT INTO notifications (id, user_id, type, title, body, is_read) VALUES
('notif-1','user-me','message','Nouveau message','Kira: See you tomorrow!',0),
('notif-2','user-me','like','Nouveau like','Darlene a aimé votre post',0),
('notif-3','user-me','follow','Nouveau follower','Haris vous suit',1);

SET FOREIGN_KEY_CHECKS = 1;
