import React, { useState, useEffect } from 'react'
import {
  View, Text, Image, TouchableOpacity, StyleSheet, ScrollView, ActivityIndicator,
} from 'react-native'
import { Ionicons } from '@expo/vector-icons'
import { useAuth } from '../context/AuthContext'
import { users as usersApi } from '../api/client'

export default function ProfileScreen() {
  const { user, logout } = useAuth()
  const [stats, setStats] = useState({ followers: 0, following: 0, posts: 0 })

  useEffect(() => {
    if (user?.id) {
      usersApi.get(user.id).then(u => setStats(u.stats)).catch(() => {})
    }
  }, [user])

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      <View style={styles.header}>
        <Text style={styles.headerName}>{user?.displayName || 'Profile'}</Text>
        <View style={styles.headerActions}>
          <TouchableOpacity onPress={logout}>
            <Ionicons name="log-out-outline" size={22} color="rgba(255,255,255,0.6)" />
          </TouchableOpacity>
        </View>
      </View>

      <View style={styles.profile}>
        <Image source={{ uri: user?.avatarUrl || 'https://i.pravatar.cc/200?u=me' }} style={styles.avatar} />
        <Text style={styles.name}>{user?.displayName}</Text>
        <Text style={styles.handle}>@{user?.username}</Text>
        <View style={styles.stats}>
          {[
            { label: 'Post', value: stats.posts || 360 },
            { label: 'Follower', value: stats.followers || '160k' },
            { label: 'Following', value: stats.following || '140k' },
          ].map(s => (
            <View key={s.label} style={styles.stat}>
              <Text style={styles.statValue}>{s.value}</Text>
              <Text style={styles.statLabel}>{s.label}</Text>
            </View>
          ))}
        </View>
      </View>

      <View style={styles.grid}>
        {Array.from({ length: 9 }).map((_, i) => (
          <Image
            key={i}
            source={{ uri: `https://picsum.photos/200/200?random=${i + 10}` }}
            style={styles.gridItem}
          />
        ))}
      </View>
    </ScrollView>
  )
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#0a0a0f' },
  content: { paddingBottom: 100 },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 20, paddingTop: 8, paddingBottom: 16 },
  headerName: { fontSize: 18, fontWeight: '600', color: '#fff' },
  headerActions: { flexDirection: 'row', gap: 16 },
  profile: { alignItems: 'center', paddingBottom: 24 },
  avatar: { width: 96, height: 96, borderRadius: 48, borderWidth: 2, borderColor: '#6366f1', marginBottom: 12 },
  name: { fontSize: 22, fontWeight: '700', color: '#fff' },
  handle: { fontSize: 14, color: 'rgba(255,255,255,0.4)', marginTop: 4, marginBottom: 16 },
  stats: { flexDirection: 'row', gap: 32 },
  stat: { alignItems: 'center' },
  statValue: { fontSize: 18, fontWeight: '700', color: '#fff' },
  statLabel: { fontSize: 12, color: 'rgba(255,255,255,0.4)' },
  grid: { flexDirection: 'row', flexWrap: 'wrap', paddingHorizontal: 16, gap: 2 },
  gridItem: { width: '32.5%', aspectRatio: 1, borderRadius: 8 },
})
