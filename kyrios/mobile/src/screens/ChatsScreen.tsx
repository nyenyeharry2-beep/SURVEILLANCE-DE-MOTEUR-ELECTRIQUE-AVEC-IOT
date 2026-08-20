import React, { useState, useEffect, useCallback } from 'react'
import {
  View, Text, FlatList, Image, TouchableOpacity, StyleSheet,
  TextInput, RefreshControl, ActivityIndicator,
} from 'react-native'
import { Ionicons } from '@expo/vector-icons'
import { chats as chatsApi, stories as storiesApi, Chat, Story } from '../api/client'

const FILTERS = ['All', 'Favorites', 'Work', 'Groups']

interface Props {
  onOpenChat: (id: string) => void
}

export default function ChatsScreen({ onOpenChat }: Props) {
  const [chatList, setChatList] = useState<Chat[]>([])
  const [storyList, setStoryList] = useState<Story[]>([])
  const [filter, setFilter] = useState('All')
  const [loading, setLoading] = useState(true)
  const [refreshing, setRefreshing] = useState(false)

  const load = useCallback(async () => {
    try {
      const [c, s] = await Promise.all([chatsApi.list(), storiesApi.list()])
      setChatList(c)
      setStoryList(s)
    } catch { /* offline */ }
    setLoading(false)
  }, [])

  useEffect(() => { load() }, [load])

  const filtered = chatList.filter(c => {
    if (filter === 'Groups') return c.isGroup
    return true
  })

  if (loading) return <View style={styles.center}><ActivityIndicator color="#6366f1" size="large" /></View>

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.title}>Chats</Text>
        <View style={styles.headerIcons}>
          <Ionicons name="search" size={22} color="rgba(255,255,255,0.6)" />
          <Ionicons name="camera-outline" size={22} color="rgba(255,255,255,0.6)" style={{ marginLeft: 16 }} />
        </View>
      </View>

      <FlatList
        data={filtered}
        keyExtractor={item => item.id}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={async () => { setRefreshing(true); await load(); setRefreshing(false) }} tintColor="#6366f1" />}
        ListHeaderComponent={
          <>
            <FlatList
              horizontal
              data={[{ id: 'me', name: 'Me', avatar: 'https://i.pravatar.cc/150?u=me', isLive: false }, ...storyList]}
              keyExtractor={item => item.id}
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.stories}
              renderItem={({ item }) => (
                <View style={styles.storyItem}>
                  <View style={[styles.storyRing, item.isLive && styles.storyLive]}>
                    <Image source={{ uri: item.avatar }} style={styles.storyAvatar} />
                  </View>
                  {item.isLive && <Text style={styles.liveBadge}>Live</Text>}
                  <Text style={styles.storyName}>{item.name}</Text>
                </View>
              )}
            />
            <View style={styles.filters}>
              {FILTERS.map(f => (
                <TouchableOpacity key={f} onPress={() => setFilter(f)} style={[styles.filterPill, filter === f && styles.filterActive]}>
                  <Text style={[styles.filterText, filter === f && styles.filterTextActive]}>{f}</Text>
                </TouchableOpacity>
              ))}
            </View>
          </>
        }
        renderItem={({ item }) => (
          <TouchableOpacity style={styles.chatItem} onPress={() => onOpenChat(item.id)}>
            <Image source={{ uri: item.avatar }} style={styles.avatar} />
            <View style={styles.chatInfo}>
              <View style={styles.chatRow}>
                <Text style={styles.chatName}>{item.name}</Text>
                <Text style={styles.chatTime}>{item.time ? new Date(item.time).toLocaleTimeString('fr', { hour: '2-digit', minute: '2-digit' }) : ''}</Text>
              </View>
              <View style={styles.chatRow}>
                <Text style={styles.chatPreview} numberOfLines={1}>{item.lastMessage}</Text>
                {item.unread > 0 ? (
                  <View style={styles.badge}><Text style={styles.badgeText}>{item.unread}</Text></View>
                ) : null}
              </View>
            </View>
          </TouchableOpacity>
        )}
      />
    </View>
  )
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#0a0a0f' },
  center: { flex: 1, backgroundColor: '#0a0a0f', alignItems: 'center', justifyContent: 'center' },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 20, paddingTop: 8, paddingBottom: 12 },
  title: { fontSize: 28, fontWeight: '700', color: '#fff' },
  headerIcons: { flexDirection: 'row' },
  stories: { paddingHorizontal: 20, paddingBottom: 12, gap: 16 },
  storyItem: { alignItems: 'center', marginRight: 16 },
  storyRing: { width: 60, height: 60, borderRadius: 30, padding: 2, borderWidth: 2, borderColor: '#6366f1' },
  storyLive: { borderColor: '#ef4444' },
  storyAvatar: { width: '100%', height: '100%', borderRadius: 28 },
  liveBadge: { fontSize: 9, fontWeight: '700', color: '#fff', backgroundColor: '#ef4444', paddingHorizontal: 6, paddingVertical: 1, borderRadius: 8, marginTop: -8 },
  storyName: { fontSize: 11, color: 'rgba(255,255,255,0.5)', marginTop: 4 },
  filters: { flexDirection: 'row', paddingHorizontal: 20, paddingBottom: 12, gap: 8 },
  filterPill: { paddingHorizontal: 16, paddingVertical: 6, borderRadius: 20 },
  filterActive: { backgroundColor: 'rgba(255,255,255,0.12)' },
  filterText: { color: 'rgba(255,255,255,0.5)', fontSize: 14 },
  filterTextActive: { color: '#fff', fontWeight: '600' },
  chatItem: { flexDirection: 'row', paddingHorizontal: 20, paddingVertical: 12, alignItems: 'center' },
  avatar: { width: 52, height: 52, borderRadius: 26 },
  chatInfo: { flex: 1, marginLeft: 12 },
  chatRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  chatName: { fontSize: 16, fontWeight: '600', color: '#fff' },
  chatTime: { fontSize: 12, color: 'rgba(255,255,255,0.4)' },
  chatPreview: { fontSize: 14, color: 'rgba(255,255,255,0.5)', flex: 1, marginTop: 2 },
  badge: { backgroundColor: '#ef4444', borderRadius: 10, minWidth: 20, height: 20, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 6 },
  badgeText: { color: '#fff', fontSize: 11, fontWeight: '700' },
})
