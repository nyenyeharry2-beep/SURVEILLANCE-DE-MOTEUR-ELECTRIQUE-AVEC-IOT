import { Image } from 'expo-image';
import { Ionicons } from '@expo/vector-icons';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { currentUser } from '../../constants/data';
import { BorderRadius, Colors, FontSize, FontWeight, Spacing } from '../../constants/theme';

const PHOTOS = [
  'https://picsum.photos/seed/p1/200/200',
  'https://picsum.photos/seed/p2/200/200',
  'https://picsum.photos/seed/p3/200/200',
  'https://picsum.photos/seed/p4/200/200',
  'https://picsum.photos/seed/p5/200/200',
  'https://picsum.photos/seed/p6/200/200',
];

export default function ProfileScreen() {
  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.scroll}>
        <View style={styles.header}>
          <Text style={styles.title}>Profile</Text>
          <Ionicons name="settings-outline" size={24} color={Colors.text} />
        </View>

        <View style={styles.profileSection}>
          <Image source={{ uri: currentUser.avatar }} style={styles.avatar} contentFit="cover" />
          <Text style={styles.name}>Cassie Donk ✌️</Text>
          <Text style={styles.handle}>@cassie_donk</Text>
        </View>

        <View style={styles.statsRow}>
          <View style={styles.stat}>
            <Text style={styles.statNumber}>426</Text>
            <Text style={styles.statLabel}>Photos</Text>
          </View>
          <View style={styles.statDivider} />
          <View style={styles.stat}>
            <Text style={styles.statNumber}>987</Text>
            <Text style={styles.statLabel}>Followers</Text>
          </View>
          <View style={styles.statDivider} />
          <View style={styles.stat}>
            <Text style={styles.statNumber}>65</Text>
            <Text style={styles.statLabel}>Following</Text>
          </View>
        </View>

        <View style={styles.photosHeader}>
          <Text style={styles.sectionTitle}>Photos</Text>
          <Text style={styles.viewAll}>View all ›</Text>
        </View>

        <View style={styles.photoGrid}>
          {PHOTOS.map((uri, index) => (
            <Image
              key={uri}
              source={{ uri }}
              style={[
                styles.photo,
                index % 3 === 0 && styles.photoLarge,
              ]}
              contentFit="cover"
            />
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
  profileSection: {
    alignItems: 'center',
    paddingVertical: Spacing.xl,
  },
  avatar: {
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: Colors.surfaceLight,
  },
  name: {
    color: Colors.text,
    fontSize: FontSize.xl,
    fontWeight: FontWeight.bold,
    marginTop: Spacing.md,
  },
  handle: {
    color: Colors.textSecondary,
    fontSize: FontSize.md,
    marginTop: Spacing.xs,
  },
  statsRow: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: Spacing.lg,
    marginHorizontal: Spacing.lg,
    backgroundColor: Colors.surfaceLight,
    borderRadius: BorderRadius.lg,
  },
  stat: {
    flex: 1,
    alignItems: 'center',
  },
  statNumber: {
    color: Colors.text,
    fontSize: FontSize.xl,
    fontWeight: FontWeight.bold,
  },
  statLabel: {
    color: Colors.textSecondary,
    fontSize: FontSize.sm,
    marginTop: 2,
  },
  statDivider: {
    width: 1,
    height: 32,
    backgroundColor: Colors.border,
  },
  photosHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: Spacing.lg,
    marginTop: Spacing.xl,
    marginBottom: Spacing.md,
  },
  sectionTitle: {
    color: Colors.text,
    fontSize: FontSize.lg,
    fontWeight: FontWeight.semibold,
  },
  viewAll: {
    color: Colors.accent,
    fontSize: FontSize.sm,
  },
  photoGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    paddingHorizontal: Spacing.lg,
    gap: Spacing.sm,
  },
  photo: {
    width: '31%',
    aspectRatio: 1,
    borderRadius: BorderRadius.md,
    backgroundColor: Colors.surfaceLight,
  },
  photoLarge: {
    width: '48%',
    aspectRatio: 0.8,
  },
});
