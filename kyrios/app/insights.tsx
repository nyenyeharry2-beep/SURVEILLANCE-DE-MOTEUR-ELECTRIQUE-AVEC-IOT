import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { BarChart, StatCard } from '../components/CommunityItem';
import { ageDemographics, insightLocations } from '../constants/data';
import { BorderRadius, Colors, FontSize, FontWeight, Spacing } from '../constants/theme';

type GenderFilter = 'all' | 'female' | 'male' | 'nonbinary';

const GENDER_FILTERS: { key: GenderFilter; label: string }[] = [
  { key: 'all', label: 'All' },
  { key: 'female', label: 'Female' },
  { key: 'male', label: 'Male' },
  { key: 'nonbinary', label: 'Non-binary' },
];

export default function InsightsScreen() {
  const router = useRouter();
  const [genderFilter, setGenderFilter] = useState<GenderFilter>('all');

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <View style={styles.header}>
        <Pressable onPress={() => router.back()}>
          <Ionicons name="chevron-back" size={24} color={Colors.text} />
        </Pressable>
        <Text style={styles.title}>Insights</Text>
        <View style={{ width: 24 }} />
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.scroll}>
        <Text style={styles.updated}>Updated 3 hours ago</Text>

        <StatCard
          label="Total Members"
          value="14,328"
          subtitle="▲ 214 new members over the last 7 days"
        />

        <Text style={styles.sectionTitle}>Top Locations</Text>
        <BarChart
          horizontal
          data={insightLocations.map((loc) => ({
            label: loc.city,
            value: loc.percentage,
          }))}
        />

        <Text style={[styles.sectionTitle, { marginTop: Spacing.xxl }]}>Age Demographics</Text>
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.filterRow}
        >
          {GENDER_FILTERS.map((filter) => (
            <Pressable
              key={filter.key}
              onPress={() => setGenderFilter(filter.key)}
              style={[styles.filterTab, genderFilter === filter.key && styles.filterTabActive]}
            >
              <Text
                style={[
                  styles.filterText,
                  genderFilter === filter.key && styles.filterTextActive,
                ]}
              >
                {filter.label}
              </Text>
            </Pressable>
          ))}
        </ScrollView>

        <View style={styles.ageChart}>
          {ageDemographics.map((demo) => (
            <View key={demo.range} style={styles.ageBar}>
              <View style={styles.ageBarTrack}>
                <View style={[styles.ageBarFill, { height: `${demo.percentage}%` }]} />
              </View>
              <Text style={styles.ageLabel}>{demo.range}</Text>
              <Text style={styles.ageValue}>{demo.percentage}%</Text>
            </View>
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
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: Spacing.lg,
    paddingVertical: Spacing.md,
  },
  title: {
    color: Colors.text,
    fontSize: FontSize.xl,
    fontWeight: FontWeight.bold,
  },
  scroll: {
    paddingBottom: Spacing.xxxl,
  },
  updated: {
    color: Colors.textMuted,
    fontSize: FontSize.sm,
    paddingHorizontal: Spacing.lg,
    marginBottom: Spacing.md,
  },
  sectionTitle: {
    color: Colors.text,
    fontSize: FontSize.lg,
    fontWeight: FontWeight.semibold,
    paddingHorizontal: Spacing.lg,
    marginBottom: Spacing.md,
    marginTop: Spacing.lg,
  },
  filterRow: {
    paddingHorizontal: Spacing.lg,
    gap: Spacing.sm,
    marginBottom: Spacing.lg,
  },
  filterTab: {
    paddingHorizontal: Spacing.lg,
    paddingVertical: Spacing.sm,
    borderRadius: BorderRadius.full,
  },
  filterTabActive: {
    backgroundColor: Colors.surfaceElevated,
  },
  filterText: {
    color: Colors.textSecondary,
    fontSize: FontSize.sm,
  },
  filterTextActive: {
    color: Colors.text,
    fontWeight: '600',
  },
  ageChart: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    paddingHorizontal: Spacing.lg,
    height: 180,
    alignItems: 'flex-end',
  },
  ageBar: {
    alignItems: 'center',
    flex: 1,
  },
  ageBarTrack: {
    width: 28,
    height: 120,
    backgroundColor: Colors.surfaceLight,
    borderRadius: BorderRadius.sm,
    justifyContent: 'flex-end',
    overflow: 'hidden',
  },
  ageBarFill: {
    width: '100%',
    backgroundColor: Colors.accent,
    borderRadius: BorderRadius.sm,
  },
  ageLabel: {
    color: Colors.textSecondary,
    fontSize: FontSize.xs,
    marginTop: Spacing.sm,
  },
  ageValue: {
    color: Colors.textMuted,
    fontSize: FontSize.xs,
    marginTop: 2,
  },
});
