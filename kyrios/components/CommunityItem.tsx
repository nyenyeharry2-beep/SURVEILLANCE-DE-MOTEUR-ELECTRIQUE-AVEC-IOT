import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { BorderRadius, Colors, FontSize, Gradients, Spacing } from '../constants/theme';
import { Community } from '../types';

interface CommunityItemProps {
  community: Community;
  onPress?: () => void;
}

export function CommunityItem({ community, onPress }: CommunityItemProps) {
  return (
    <Pressable style={styles.container} onPress={onPress}>
      <View style={[styles.icon, { backgroundColor: community.color }]}>
        <Text style={styles.iconText}>{community.name.charAt(0)}</Text>
      </View>
      <View style={styles.content}>
        <Text style={styles.name}>{community.name}</Text>
        <Text style={styles.members}>{community.memberCount} members</Text>
      </View>
      <Ionicons name="chevron-forward" size={18} color={Colors.textMuted} />
    </Pressable>
  );
}

interface BarChartProps {
  data: { label: string; value: number }[];
  horizontal?: boolean;
}

export function BarChart({ data, horizontal = false }: BarChartProps) {
  const maxValue = Math.max(...data.map((d) => d.value));

  return (
    <View style={styles.chart}>
      {data.map((item) => {
        const ratio = item.value / maxValue;
        return (
          <View key={item.label} style={horizontal ? styles.barRow : styles.barColumn}>
            <Text style={styles.barLabel} numberOfLines={1}>
              {item.label}
            </Text>
            <View style={horizontal ? styles.barTrackH : styles.barTrackV}>
              <LinearGradient
                colors={Gradients.accent}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={[
                  horizontal ? styles.barFillH : styles.barFillV,
                  horizontal
                    ? { width: `${ratio * 100}%` }
                    : { height: `${ratio * 100}%` },
                ]}
              />
            </View>
            <Text style={styles.barValue}>{item.value}%</Text>
          </View>
        );
      })}
    </View>
  );
}

interface StatCardProps {
  label: string;
  value: string;
  subtitle?: string;
}

export function StatCard({ label, value, subtitle }: StatCardProps) {
  return (
    <View style={styles.statCard}>
      <Text style={styles.statLabel}>{label}</Text>
      <Text style={styles.statValue}>{value}</Text>
      {subtitle && <Text style={styles.statSubtitle}>{subtitle}</Text>}
    </View>
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
  icon: {
    width: 44,
    height: 44,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconText: {
    color: Colors.white,
    fontSize: FontSize.lg,
    fontWeight: '700',
  },
  content: {
    flex: 1,
  },
  name: {
    color: Colors.text,
    fontSize: FontSize.md,
    fontWeight: '600',
  },
  members: {
    color: Colors.textSecondary,
    fontSize: FontSize.sm,
    marginTop: 2,
  },
  chart: {
    gap: Spacing.md,
    paddingHorizontal: Spacing.lg,
  },
  barRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.sm,
  },
  barColumn: {
    alignItems: 'center',
    flex: 1,
  },
  barLabel: {
    color: Colors.textSecondary,
    fontSize: FontSize.xs,
    width: 90,
  },
  barTrackH: {
    flex: 1,
    height: 8,
    backgroundColor: Colors.surfaceLight,
    borderRadius: BorderRadius.full,
    overflow: 'hidden',
  },
  barTrackV: {
    width: 32,
    height: 120,
    backgroundColor: Colors.surfaceLight,
    borderRadius: BorderRadius.sm,
    overflow: 'hidden',
    justifyContent: 'flex-end',
  },
  barFillH: {
    height: '100%',
    borderRadius: BorderRadius.full,
  },
  barFillV: {
    width: '100%',
    borderRadius: BorderRadius.sm,
  },
  barValue: {
    color: Colors.textMuted,
    fontSize: FontSize.xs,
    width: 36,
    textAlign: 'right',
  },
  statCard: {
    backgroundColor: Colors.surfaceLight,
    borderRadius: BorderRadius.lg,
    padding: Spacing.lg,
    marginHorizontal: Spacing.lg,
    marginBottom: Spacing.lg,
  },
  statLabel: {
    color: Colors.textSecondary,
    fontSize: FontSize.sm,
  },
  statValue: {
    color: Colors.text,
    fontSize: FontSize.title,
    fontWeight: '700',
    marginTop: Spacing.xs,
  },
  statSubtitle: {
    color: Colors.online,
    fontSize: FontSize.sm,
    marginTop: Spacing.xs,
  },
});
