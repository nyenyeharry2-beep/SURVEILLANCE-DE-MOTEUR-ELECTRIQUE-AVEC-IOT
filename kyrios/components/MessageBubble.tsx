import { Image } from 'expo-image';
import { Ionicons } from '@expo/vector-icons';
import { StyleSheet, Text, View } from 'react-native';
import { BorderRadius, Colors, FontSize, Spacing } from '../constants/theme';
import { Message } from '../types';

interface MessageBubbleProps {
  message: Message;
}

export function MessageBubble({ message }: MessageBubbleProps) {
  const isOwn = message.isOwn;

  return (
    <View style={[styles.wrapper, isOwn ? styles.ownWrapper : styles.otherWrapper]}>
      {!isOwn && message.senderName && (
        <Text style={styles.senderName}>{message.senderName}</Text>
      )}
      <View style={[styles.bubble, isOwn ? styles.ownBubble : styles.otherBubble]}>
        {message.text && (
          <Text style={[styles.text, isOwn ? styles.ownText : styles.otherText]}>
            {message.text}
          </Text>
        )}
        {message.images && message.images.length > 0 && (
          <View style={styles.imageStack}>
            {message.images.map((uri, index) => (
              <Image
                key={uri}
                source={{ uri }}
                style={[
                  styles.stackedImage,
                  {
                    marginLeft: index * 20,
                    zIndex: message.images!.length - index,
                    transform: [{ rotate: `${(index - 1) * 4}deg` }],
                  },
                ]}
                contentFit="cover"
              />
            ))}
          </View>
        )}
        {message.reactions && message.reactions.length > 0 && (
          <View style={styles.reactions}>
            {message.reactions.map((reaction) => (
              <View key={reaction.emoji} style={styles.reaction}>
                <Text style={styles.reactionEmoji}>{reaction.emoji}</Text>
                <Text style={styles.reactionCount}>
                  {String(reaction.count).padStart(2, '0')}
                </Text>
              </View>
            ))}
          </View>
        )}
      </View>
      {isOwn && message.isRead && (
        <Ionicons
          name="checkmark-done"
          size={14}
          color={Colors.checkmark}
          style={styles.readReceipt}
        />
      )}
    </View>
  );
}

interface TimestampDividerProps {
  label: string;
}

export function TimestampDivider({ label }: TimestampDividerProps) {
  return (
    <View style={styles.divider}>
      <Text style={styles.dividerText}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper: {
    marginVertical: Spacing.xs,
    maxWidth: '80%',
  },
  ownWrapper: {
    alignSelf: 'flex-end',
    alignItems: 'flex-end',
  },
  otherWrapper: {
    alignSelf: 'flex-start',
    alignItems: 'flex-start',
  },
  senderName: {
    color: Colors.accent,
    fontSize: FontSize.xs,
    fontWeight: '600',
    marginBottom: Spacing.xs,
    marginLeft: Spacing.xs,
  },
  bubble: {
    borderRadius: BorderRadius.xl,
    padding: Spacing.md,
    overflow: 'hidden',
  },
  ownBubble: {
    backgroundColor: Colors.sentBubble,
    borderBottomRightRadius: BorderRadius.sm,
  },
  otherBubble: {
    backgroundColor: Colors.receivedBubble,
    borderBottomLeftRadius: BorderRadius.sm,
  },
  text: {
    fontSize: FontSize.md,
    lineHeight: 22,
  },
  ownText: {
    color: Colors.text,
  },
  otherText: {
    color: Colors.text,
  },
  imageStack: {
    flexDirection: 'row',
    height: 140,
    marginTop: Spacing.sm,
  },
  stackedImage: {
    width: 90,
    height: 130,
    borderRadius: BorderRadius.md,
    position: 'absolute',
    borderWidth: 2,
    borderColor: Colors.surface,
  },
  reactions: {
    flexDirection: 'row',
    gap: Spacing.sm,
    marginTop: Spacing.sm,
  },
  reaction: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: Colors.surface,
    paddingHorizontal: Spacing.sm,
    paddingVertical: Spacing.xs,
    borderRadius: BorderRadius.full,
    gap: 4,
  },
  reactionEmoji: {
    fontSize: FontSize.sm,
  },
  reactionCount: {
    color: Colors.textSecondary,
    fontSize: FontSize.xs,
    fontWeight: '600',
  },
  readReceipt: {
    marginTop: 2,
    marginRight: Spacing.xs,
  },
  divider: {
    alignItems: 'center',
    marginVertical: Spacing.lg,
  },
  dividerText: {
    color: Colors.textMuted,
    fontSize: FontSize.xs,
    fontWeight: '500',
  },
});
