-- KYRIOS Seed Data
-- Mot de passe pour tous les comptes demo: Kyrios2026!

-- Demo users (password_hash = bcrypt of 'Kyrios2026!')
INSERT OR IGNORE INTO users (id, email, password_hash, username, display_name, avatar_url, bio, is_online) VALUES
('user-me', 'me@kyrios.app', '$2b$10$rQZ8K8Y5Y5Y5Y5Y5Y5Y5YuGKxHxHxHxHxHxHxHxHxHxHxHxHxHxH', 'me', 'Moi', 'https://i.pravatar.cc/150?u=me', 'Utilisateur KYRIOS', 1),
('user-kira', 'kira@kyrios.app', '$2b$10$rQZ8K8Y5Y5Y5Y5Y5Y5Y5YuGKxHxHxHxHxHxHxHxHxHxHxHxHxHxH', 'kira', 'Kira Lindegaard', 'https://i.pravatar.cc/150?u=kira', 'Travel enthusiast', 1),
('user-khai', 'khai@kyrios.app', '$2b$10$rQZ8K8Y5Y5Y5Y5Y5Y5Y5YuGKxHxHxHxHxHxHxHxHxHxHxHxHxHxH', 'khai', 'Khai Azzahra', 'https://i.pravatar.cc/150?u=khai', '', 0),
('user-akbar', 'akbar@kyrios.app', '$2b$10$rQZ8K8Y5Y5Y5Y5Y5Y5Y5YuGKxHxHxHxHxHxHxHxHxHxHxHxHxHxH', 'akbar', 'Akbar Lazuardi', 'https://i.pravatar.cc/150?u=akbar', 'Photographer', 1),
('user-haris', 'haris@kyrios.app', '$2b$10$rQZ8K8Y5Y5Y5Y5Y5Y5Y5YuGKxHxHxHxHxHxHxHxHxHxHxHxHxHxH', 'haris', 'Haris', 'https://i.pravatar.cc/150?u=haris', '', 1),
('user-darlene', 'darlene@kyrios.app', '$2b$10$rQZ8K8Y5Y5Y5Y5Y5Y5Y5YuGKxHxHxHxHxHxHxHxHxHxHxHxHxHxH', 'dw_beats', 'Darlene Beats', 'https://i.pravatar.cc/200?u=darlene', 'Music producer @dw_beats', 1),
('user-nilesh', 'nilesh@kyrios.app', '$2b$10$rQZ8K8Y5Y5Y5Y5Y5Y5Y5YuGKxHxHxHxHxHxHxHxHxHxHxHxHxHxH', 'nilesh', 'Nilesh', 'https://i.pravatar.cc/150?u=nilesh', 'Adventure seeker', 1),
('user-justin', 'justin@kyrios.app', '$2b$10$rQZ8K8Y5Y5Y5Y5Y5Y5YuGKxHxHxHxHxHxHxHxHxHxHxHxHxHxH', 'justin', 'Justin Bryant', 'https://i.pravatar.cc/150?u=justin', '', 0);

-- Conversations
INSERT OR IGNORE INTO conversations (id, name, avatar_url, is_group, created_by) VALUES
('conv-denpasar', 'Visit Denpasar', 'https://i.pravatar.cc/150?u=denpasar', 1, 'user-kira'),
('conv-kira', NULL, 'https://i.pravatar.cc/150?u=kira', 0, 'user-me'),
('conv-bestgirls', 'Best girls', 'https://i.pravatar.cc/150?u=bestgirls', 1, 'user-darlene');

-- Conversation members
INSERT OR IGNORE INTO conversation_members (id, conversation_id, user_id, role) VALUES
('cm-1', 'conv-denpasar', 'user-me', 'member'),
('cm-2', 'conv-denpasar', 'user-kira', 'admin'),
('cm-3', 'conv-denpasar', 'user-khai', 'member'),
('cm-4', 'conv-denpasar', 'user-akbar', 'member'),
('cm-5', 'conv-kira', 'user-me', 'member'),
('cm-6', 'conv-kira', 'user-kira', 'member'),
('cm-7', 'conv-bestgirls', 'user-me', 'member'),
('cm-8', 'conv-bestgirls', 'user-darlene', 'admin');

-- Messages
INSERT OR IGNORE INTO messages (id, conversation_id, sender_id, content, message_type, created_at) VALUES
('msg-1', 'conv-denpasar', 'user-kira', 'Hey everyone! I found this amazing spot in Denpasar 🌴', 'text', datetime('now', '-2 hours')),
('msg-2', 'conv-denpasar', 'user-khai', 'Are they still open at sunday?', 'text', datetime('now', '-1 hour', '-57 minutes')),
('msg-3', 'conv-denpasar', 'user-akbar', 'Check these photos!', 'media', datetime('now', '-1 hour', '-55 minutes')),
('msg-4', 'conv-kira', 'user-kira', 'See you tomorrow! ✨', 'text', datetime('now', '-2 minutes')),
('msg-5', 'conv-bestgirls', 'user-darlene', 'Guys I just made $10,000 from Youtube. Let''s party!', 'text', datetime('now', '-20 seconds'));

INSERT OR IGNORE INTO message_media (id, message_id, media_url, media_type, sort_order) VALUES
('mm-1', 'msg-3', 'https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?w=200&h=200&fit=crop', 'image', 0),
('mm-2', 'msg-3', 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=200&h=200&fit=crop', 'image', 1),
('mm-3', 'msg-3', 'https://images.unsplash.com/photo-1555404738-9f2795b1dbe8?w=200&h=200&fit=crop', 'image', 2);

INSERT OR IGNORE INTO message_reactions (id, message_id, user_id, emoji) VALUES
('mr-1', 'msg-3', 'user-kira', '🔥'),
('mr-2', 'msg-3', 'user-khai', '🔥'),
('mr-3', 'msg-3', 'user-me', '😍');

-- Stories
INSERT OR IGNORE INTO stories (id, user_id, media_url, is_live, expires_at) VALUES
('story-1', 'user-haris', 'https://i.pravatar.cc/150?u=haris', 1, datetime('now', '+1 day')),
('story-2', 'user-kira', 'https://i.pravatar.cc/150?u=kira', 0, datetime('now', '+1 day'));

-- Communities
INSERT OR IGNORE INTO communities (id, name, icon, color, description, created_by) VALUES
('comm-1', 'Foodies', '🍕', '#f97316', 'Partagez vos recettes favorites', 'user-me'),
('comm-2', 'Daily Inspiration', '✨', '#a855f7', 'Motivation quotidienne', 'user-me'),
('comm-3', 'Football', '⚽', '#22c55e', 'Fans de football', 'user-me'),
('comm-4', 'Sneakerheads', '👟', '#3b82f6', 'Collectionneurs de sneakers', 'user-me'),
('comm-5', 'Photography', '📷', '#ec4899', 'Photographes amateurs et pros', 'user-me'),
('comm-6', 'Travel', '✈️', '#06b6d4', 'Voyageurs du monde entier', 'user-me');

INSERT OR IGNORE INTO community_members (id, community_id, user_id) VALUES
('cmc-1', 'comm-1', 'user-me'), ('cmc-2', 'comm-2', 'user-me'), ('cmc-3', 'comm-3', 'user-me');

-- Posts
INSERT OR IGNORE INTO posts (id, author_id, caption, image_url, created_at) VALUES
('post-1', 'user-nilesh', 'Discover adventure in patagonia''s peaks or serenity provence''s hamlets — arrival', 'https://images.unsplash.com/photo-1682687220063-4742bd7fd538?w=400&h=300&fit=crop', datetime('now', '-1 hour')),
('post-2', 'user-darlene', 'Golden hour in the city never gets old 🎵', 'https://images.unsplash.com/photo-1480714378408-67cf0d13bc1b?w=400&h=300&fit=crop', datetime('now', '-3 hours'));

INSERT OR IGNORE INTO post_likes (id, post_id, user_id) VALUES
('pl-1', 'post-1', 'user-me'), ('pl-2', 'post-1', 'user-kira'), ('pl-3', 'post-2', 'user-me');

-- Follows
INSERT OR IGNORE INTO follows (id, follower_id, following_id) VALUES
('f-1', 'user-me', 'user-darlene'), ('f-2', 'user-me', 'user-nilesh'), ('f-3', 'user-kira', 'user-me');

-- Calls
INSERT OR IGNORE INTO calls (id, caller_id, callee_id, call_type, direction, status, duration_seconds, started_at) VALUES
('call-1', 'user-kira', 'user-me', 'voice', 'incoming', 'completed', 324, datetime('now', '-3 hours')),
('call-2', 'user-me', 'user-justin', 'voice', 'outgoing', 'completed', 721, datetime('now', '-1 day')),
('call-3', 'user-darlene', 'user-me', 'voice', 'incoming', 'missed', 0, datetime('now', '-2 days'));

-- Notifications
INSERT OR IGNORE INTO notifications (id, user_id, type, title, body, is_read) VALUES
('notif-1', 'user-me', 'message', 'Nouveau message', 'Kira: See you tomorrow! ✨', 0),
('notif-2', 'user-me', 'like', 'Nouveau like', 'Darlene a aimé votre post', 0),
('notif-3', 'user-me', 'follow', 'Nouveau follower', 'Haris vous suit', 1);
