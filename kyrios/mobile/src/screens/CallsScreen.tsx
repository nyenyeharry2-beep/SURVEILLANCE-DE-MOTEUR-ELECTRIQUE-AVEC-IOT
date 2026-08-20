import React, { useState, useEffect } from 'react'
import {
  View, Text, FlatList, Image, StyleSheet, ActivityIndicator,
} from 'react-native'
import { calls as callsApi, Call } from '../api/client'

export default function CallsScreen() {
  const [callList, setCallList] = useState<Call[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    callsApi.list().then(setCallList).finally(() => setLoading(false))
  }, [])

  if (loading) return <View style={styles.center}><ActivityIndicator color="#6366f1" size="large" /></View>

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Calls</Text>
      <FlatList
        data={callList}
        keyExtractor={item => item.id}
        contentContainerStyle={styles.list}
        renderItem={({ item }) => (
          <View style={styles.item}>
            <Image source={{ uri: item.avatar }} style={styles.avatar} />
            <View style={styles.info}>
              <Text style={styles.name}>{item.name}</Text>
              <Text style={[styles.detail, item.type === 'missed' && styles.missed]}>
                {item.type === 'incoming' ? '↙' : item.type === 'outgoing' ? '↗' : '↙'} {item.time}
                {item.duration ? ` · ${item.duration}` : ''}
              </Text>
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
  title: { fontSize: 28, fontWeight: '700', color: '#fff', paddingHorizontal: 20, paddingTop: 8, paddingBottom: 16 },
  list: { paddingHorizontal: 20 },
  item: { flexDirection: 'row', alignItems: 'center', paddingVertical: 12 },
  avatar: { width: 48, height: 48, borderRadius: 24 },
  info: { marginLeft: 12 },
  name: { fontSize: 16, fontWeight: '600', color: '#fff' },
  detail: { fontSize: 14, color: 'rgba(255,255,255,0.4)', marginTop: 2 },
  missed: { color: '#ef4444' },
})
