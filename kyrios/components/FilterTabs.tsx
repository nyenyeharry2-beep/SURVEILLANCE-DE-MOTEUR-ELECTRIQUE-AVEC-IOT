import { Ionicons } from '@expo/vector-icons';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { BorderRadius, Colors, FontSize, Spacing } from '../constants/theme';
import { ChatFilter } from '../types';

interface FilterTabsProps {
  active: ChatFilter;
  onChange: (filter: ChatFilter) => void;
}

const FILTERS: { key: ChatFilter; label: string }[] = [
  { key: 'all', label: 'All' },
  { key: 'favorites', label: 'Favorites' },
  { key: 'work', label: 'Work' },
  { key: 'groups', label: 'Groups' },
];

export function FilterTabs({ active, onChange }: FilterTabsProps) {
  return (
    <ScrollView
      horizontal
      showsHorizontalScrollIndicator={false}
      contentContainerStyle={styles.container}
    >
      {FILTERS.map((filter) => {
        const isActive = active === filter.key;
        return (
          <Pressable
            key={filter.key}
            onPress={() => onChange(filter.key)}
            style={[styles.tab, isActive && styles.tabActive]}
          >
            <Text style={[styles.tabText, isActive && styles.tabTextActive]}>
              {filter.label}
            </Text>
          </Pressable>
        );
      })}
    </ScrollView>
  );
}

interface SearchBarProps {
  placeholder?: string;
  showFilter?: boolean;
}

export function SearchBar({ placeholder = 'Search...', showFilter = false }: SearchBarProps) {
  return (
    <View style={styles.searchContainer}>
      <Ionicons name="search" size={18} color={Colors.textMuted} />
      <Text style={styles.searchPlaceholder}>{placeholder}</Text>
      {showFilter && (
        <Ionicons name="options-outline" size={20} color={Colors.textSecondary} />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    paddingHorizontal: Spacing.lg,
    gap: Spacing.sm,
    paddingBottom: Spacing.md,
  },
  tab: {
    paddingHorizontal: Spacing.lg,
    paddingVertical: Spacing.sm,
    borderRadius: BorderRadius.full,
    backgroundColor: Colors.transparent,
  },
  tabActive: {
    backgroundColor: Colors.surfaceElevated,
  },
  tabText: {
    color: Colors.textSecondary,
    fontSize: FontSize.sm,
    fontWeight: '500',
  },
  tabTextActive: {
    color: Colors.text,
    fontWeight: '600',
  },
  searchContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: Colors.surfaceLight,
    marginHorizontal: Spacing.lg,
    marginBottom: Spacing.md,
    paddingHorizontal: Spacing.md,
    paddingVertical: Spacing.md,
    borderRadius: BorderRadius.lg,
    gap: Spacing.sm,
  },
  searchPlaceholder: {
    flex: 1,
    color: Colors.textMuted,
    fontSize: FontSize.md,
  },
});
