import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { FlatList, Pressable, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { CommunityItem } from '../components/CommunityItem';
import { SearchBar } from '../components/FilterTabs';
import { communities } from '../constants/data';
import { Colors, FontSize, FontWeight, Spacing } from '../constants/theme';

export default function CommunitiesScreen() {
  const router = useRouter();

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <View style={styles.header}>
        <Pressable onPress={() => router.back()}>
          <Ionicons name="chevron-back" size={24} color={Colors.text} />
        </Pressable>
        <Text style={styles.title}>Communities</Text>
        <Pressable>
          <Ionicons name="add" size={26} color={Colors.accent} />
        </Pressable>
      </View>

      <SearchBar placeholder="Search communities..." showFilter />

      <FlatList
        data={communities}
        keyExtractor={(item) => item.id}
        renderItem={({ item }) => <CommunityItem community={item} />}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
      />
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
  list: {
    paddingBottom: Spacing.xxxl,
  },
});
