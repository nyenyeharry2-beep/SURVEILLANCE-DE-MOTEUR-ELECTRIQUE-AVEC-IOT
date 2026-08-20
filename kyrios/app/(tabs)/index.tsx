import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { useMemo, useState } from 'react';
import {
  FlatList,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ChatListItem } from '../../components/ChatListItem';
import { FilterTabs } from '../../components/FilterTabs';
import { StoryRow } from '../../components/StoryRow';
import { chats } from '../../constants/data';
import { Colors, FontSize, FontWeight, Spacing } from '../../constants/theme';
import { ChatFilter } from '../../types';
import { FloatingActionButton } from './_layout';

export default function ChatsScreen() {
  const router = useRouter();
  const [filter, setFilter] = useState<ChatFilter>('all');

  const filteredChats = useMemo(() => {
    if (filter === 'all') return chats;
    return chats.filter((chat) => chat.category === filter);
  }, [filter]);

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <View style={styles.header}>
        <Text style={styles.title}>Chats</Text>
        <View style={styles.headerActions}>
          <Pressable style={styles.iconButton}>
            <Ionicons name="search" size={22} color={Colors.text} />
          </Pressable>
          <Pressable style={styles.iconButton}>
            <Ionicons name="camera-outline" size={22} color={Colors.text} />
          </Pressable>
          <Pressable style={styles.iconButton}>
            <Ionicons name="ellipsis-vertical" size={22} color={Colors.text} />
          </Pressable>
        </View>
      </View>

      <StoryRow />
      <FilterTabs active={filter} onChange={setFilter} />

      <FlatList
        data={filteredChats}
        keyExtractor={(item) => item.id}
        renderItem={({ item }) => (
          <ChatListItem
            chat={item}
            onPress={() => router.push(`/chat/${item.id}`)}
          />
        )}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
      />

      <FloatingActionButton />
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
  headerActions: {
    flexDirection: 'row',
    gap: Spacing.sm,
  },
  iconButton: {
    padding: Spacing.sm,
  },
  list: {
    paddingBottom: 120,
  },
});
