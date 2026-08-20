import { Ionicons } from '@expo/vector-icons';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { BorderRadius, Colors, FontSize, Spacing } from '../constants/theme';
import { Chat } from '../types';
import { Avatar } from './Avatar';

interface ChatListItemProps {
  chat: Chat;
  onPress: () => void;
}

export function ChatListItem({ chat, onPress }: ChatListItemProps) {
  return (
    <Pressable style={styles.container} onPress={onPress}>
      <Avatar uri={chat.avatar} size={52} online={chat.online} />
      <View style={styles.content}>
        <View style={styles.topRow}>
          <Text style={styles.name} numberOfLines={1}>
            {chat.name}
          </Text>
          <Text style={styles.time}>{chat.timestamp}</Text>
        </View>
        <View style={styles.bottomRow}>
          {chat.isTyping ? (
            <Text style={styles.typing}>Typing...</Text>
          ) : (
            <Text style={styles.message} numberOfLines={1}>
              {chat.lastMessage}
            </Text>
          )}
          <View style={styles.indicators}>
            {chat.isRead && (
              <Ionicons name="checkmark-done" size={16} color={Colors.checkmark} />
            )}
            {chat.unreadCount != null && chat.unreadCount > 0 && (
              <View style={styles.badge}>
                <Text style={styles.badgeText}>{chat.unreadCount}</Text>
              </View>
            )}
          </View>
        </View>
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  container: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: Spacing.lg,
    paddingVertical: Spacing.md,
    gap: Spacing.md,
  },
  content: {
    flex: 1,
    gap: Spacing.xs,
  },
  topRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  name: {
    flex: 1,
    color: Colors.text,
    fontSize: FontSize.md,
    fontWeight: '600',
    marginRight: Spacing.sm,
  },
  time: {
    color: Colors.textMuted,
    fontSize: FontSize.xs,
  },
  bottomRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  message: {
    flex: 1,
    color: Colors.textSecondary,
    fontSize: FontSize.sm,
    marginRight: Spacing.sm,
  },
  typing: {
    flex: 1,
    color: Colors.accent,
    fontSize: FontSize.sm,
    fontStyle: 'italic',
  },
  indicators: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.xs,
  },
  badge: {
    backgroundColor: Colors.unread,
    minWidth: 20,
    height: 20,
    borderRadius: BorderRadius.full,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 6,
  },
  badgeText: {
    color: Colors.white,
    fontSize: FontSize.xs,
    fontWeight: '700',
  },
});
