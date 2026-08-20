import React, { useState, useEffect } from 'react'
import {
  View, Text, FlatList, Image, TouchableOpacity, StyleSheet, ActivityIndicator,
} from 'react-native'
import { Ionicons } from '@expo/vector-icons'
import { discover as discoverApi, Post } from '../api/client'

export default function DiscoverScreen() {
  const [posts, setPosts] = useState<Post[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    discoverApi.posts().then(setPosts).finally(() => setLoading(false))
  }, [])

  if (loading) return <View style={styles.center}><ActivityIndicator color="#6366f1" size="large" /></View>

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <View style={styles.headerLeft}>
          <View style={styles.notifBtn}>
            <Ionicons name="notifications-outline" size={22} color="rgba(255,255,255,0.6)" />
            <View style={styles.notifBadge}><Text style={styles.notifBadgeText}>3</Text></View>
          </View>
        </View>
        <Text style={styles.title}>Discover</Text>
        <Image source={{ uri: 'https://i.pravatar.cc/150?u=me' }} style={styles.headerAvatar} />
      </View>

      <FlatList
        data={posts}
        keyExtractor={item => item.id}
        contentContainerStyle={styles.list}
        renderItem={({ item }) => (
          <View style={styles.card}>
            <View style={styles.cardHeader}>
              <Image source={{ uri: item.avatar }} style={styles.avatar} />
              <View style={{ flex: 1 }}>
                <Text style={styles.author}>{item.author}</Text>
                <Text style={styles.time}>{item.handle} · {item.time}</Text>
              </View>
            </View>
            <Text style={styles.caption}>{item.caption}</Text>
            <Image source={{ uri: item.image }} style={styles.postImage} />
            <View style={styles.actions}>
              <TouchableOpacity style={styles.action} onPress={() => discoverApi.like(item.id)}>
                <Ionicons name="heart-outline" size={20} color="rgba(255,255,255,0.6)" />
                <Text style={styles.actionText}>{item.likes}</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.action}>
                <Ionicons name="chatbubble-outline" size={20} color="rgba(255,255,255,0.6)" />
                <Text style={styles.actionText}>{item.comments}</Text>
              </TouchableOpacity>
              <TouchableOpacity style={[styles.action, { marginLeft: 'auto' }]}>
                <Ionicons name="bookmark-outline" size={20} color="rgba(255,255,255,0.6)" />
              </TouchableOpacity>
            </View>
          </View>
        )}
      />
    </View>
  )
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#0a0a0f' },
  center: { flex: 1, backgroundColor: '#0a0a0f', alignItems: 'center', justifyContent: 'center' },
  header: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 20, paddingTop: 8, paddingBottom: 16 },
  headerLeft: { flexDirection: 'row' },
  notifBtn: { position: 'relative' },
  notifBadge: { position: 'absolute', top: -4, right: -4, backgroundColor: '#ef4444', borderRadius: 8, width: 16, height: 16, alignItems: 'center', justifyContent: 'center' },
  notifBadgeText: { color: '#fff', fontSize: 9, fontWeight: '700' },
  title: { fontSize: 20, fontWeight: '700', color: '#fff' },
  headerAvatar: { width: 36, height: 36, borderRadius: 18 },
  list: { paddingHorizontal: 16, paddingBottom: 100 },
  card: { backgroundColor: 'rgba(255,255,255,0.06)', borderRadius: 20, marginBottom: 16, overflow: 'hidden' },
  cardHeader: { flexDirection: 'row', alignItems: 'center', padding: 16, gap: 12 },
  avatar: { width: 40, height: 40, borderRadius: 20 },
  author: { fontSize: 15, fontWeight: '600', color: '#fff' },
  time: { fontSize: 12, color: 'rgba(255,255,255,0.4)' },
  caption: { paddingHorizontal: 16, paddingBottom: 12, fontSize: 14, color: 'rgba(255,255,255,0.8)', lineHeight: 20 },
  postImage: { width: '100%', height: 200 },
  actions: { flexDirection: 'row', padding: 16, gap: 16 },
  action: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  actionText: { color: 'rgba(255,255,255,0.6)', fontSize: 14 },
})
