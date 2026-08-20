import { Ionicons } from '@expo/vector-icons';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { stories } from '../constants/data';
import { Colors, FontSize, Spacing } from '../constants/theme';
import { Avatar } from './Avatar';

export function StoryRow() {
  return (
    <ScrollView
      horizontal
      showsHorizontalScrollIndicator={false}
      contentContainerStyle={styles.container}
    >
      {stories.map((story) => (
        <Pressable key={story.id} style={styles.storyItem}>
          <View style={styles.avatarWrapper}>
            <Avatar
              uri={story.avatar}
              size={64}
              showStoryRing={!story.isOwn}
              live={story.live}
            />
            {story.isOwn && (
              <View style={styles.addBadge}>
                <Ionicons name="add" size={14} color={Colors.white} />
              </View>
            )}
          </View>
          <Text style={styles.name} numberOfLines={1}>
            {story.name}
          </Text>
        </Pressable>
      ))}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    paddingHorizontal: Spacing.lg,
    paddingVertical: Spacing.md,
    gap: Spacing.lg,
  },
  storyItem: {
    alignItems: 'center',
    width: 72,
  },
  avatarWrapper: {
    position: 'relative',
  },
  addBadge: {
    position: 'absolute',
    bottom: 0,
    right: 0,
    width: 22,
    height: 22,
    borderRadius: 11,
    backgroundColor: Colors.accent,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: Colors.background,
  },
  name: {
    color: Colors.textSecondary,
    fontSize: FontSize.xs,
    marginTop: Spacing.xs,
    textAlign: 'center',
  },
});
