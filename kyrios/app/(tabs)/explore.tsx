import { Image } from 'expo-image';
import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { activeFriends } from '../../constants/data';
import { BorderRadius, Colors, FontSize, FontWeight, Spacing } from '../../constants/theme';
import { Avatar } from '../../components/Avatar';

const EXPLORE_POSTS = [
  { id: '1', author: 'Bran S.', views: '7.4k', image: 'https://picsum.photos/seed/exp1/300/400' },
  { id: '2', author: 'Mike Lyne', views: '5.2k', image: 'https://picsum.photos/seed/exp2/300/400' },
  { id: '3', author: 'Anna Pyke', views: '3.8k', image: 'https://picsum.photos/seed/exp3/300/400' },
  { id: '4', author: 'Claire K.', views: '2.1k', image: 'https://picsum.photos/seed/exp4/300/400' },
];

export default function ExploreScreen() {
  const router = useRouter();

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.scroll}>
        <View style={styles.header}>
          <Text style={styles.title}>Explore</Text>
          <Pressable onPress={() => router.push('/communities')}>
            <Text style={styles.link}>Communities</Text>
          </Pressable>
        </View>

        <Text style={styles.sectionTitle}>Active friends</Text>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.friendsRow}>
          {activeFriends.map((friend) => (
            <View key={friend.id} style={styles.friendItem}>
              <Avatar uri={friend.avatar} size={56} online={friend.online} showStoryRing />
              <Text style={styles.friendName}>{friend.name}</Text>
            </View>
          ))}
        </ScrollView>

        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Discover</Text>
          <Pressable onPress={() => router.push('/insights')}>
            <Text style={styles.link}>Insights</Text>
          </Pressable>
        </View>

        <View style={styles.grid}>
          {EXPLORE_POSTS.map((post) => (
            <Pressable key={post.id} style={styles.postCard}>
              <Image source={{ uri: post.image }} style={styles.postImage} contentFit="cover" />
              <View style={styles.postOverlay}>
                <Text style={styles.postAuthor}>{post.author}</Text>
                <Text style={styles.postViews}>{post.views} views</Text>
              </View>
            </Pressable>
          ))}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: Colors.background,
  },
  scroll: {
    paddingBottom: 120,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: Spacing.lg,
    paddingVertical: Spacing.md,
  },
  title: {
    color: Colors.text,
    fontSize: FontSize.title,
    fontWeight: FontWeight.bold,
  },
  link: {
    color: Colors.accent,
    fontSize: FontSize.sm,
    fontWeight: '600',
  },
  sectionTitle: {
    color: Colors.text,
    fontSize: FontSize.lg,
    fontWeight: FontWeight.semibold,
    paddingHorizontal: Spacing.lg,
    marginBottom: Spacing.md,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingRight: Spacing.lg,
    marginTop: Spacing.lg,
  },
  friendsRow: {
    paddingHorizontal: Spacing.lg,
    gap: Spacing.lg,
    marginBottom: Spacing.lg,
  },
  friendItem: {
    alignItems: 'center',
    width: 64,
  },
  friendName: {
    color: Colors.textSecondary,
    fontSize: FontSize.xs,
    marginTop: Spacing.xs,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    paddingHorizontal: Spacing.md,
    gap: Spacing.sm,
  },
  postCard: {
    width: '48%',
    aspectRatio: 0.75,
    borderRadius: BorderRadius.lg,
    overflow: 'hidden',
    backgroundColor: Colors.surfaceLight,
  },
  postImage: {
    width: '100%',
    height: '100%',
  },
  postOverlay: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    padding: Spacing.sm,
    backgroundColor: 'rgba(0,0,0,0.5)',
  },
  postAuthor: {
    color: Colors.white,
    fontSize: FontSize.sm,
    fontWeight: '600',
  },
  postViews: {
    color: Colors.textSecondary,
    fontSize: FontSize.xs,
  },
});
