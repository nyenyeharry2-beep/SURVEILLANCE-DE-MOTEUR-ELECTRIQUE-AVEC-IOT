import React, { useState } from 'react'
import { View, StyleSheet, ActivityIndicator, TouchableOpacity, Text } from 'react-native'
import { StatusBar } from 'expo-status-bar'
import { SafeAreaProvider, SafeAreaView } from 'react-native-safe-area-context'
import { Ionicons } from '@expo/vector-icons'
import { AuthProvider, useAuth } from './src/context/AuthContext'
import LoginScreen from './src/screens/LoginScreen'
import ChatsScreen from './src/screens/ChatsScreen'
import ChatDetailScreen from './src/screens/ChatDetailScreen'
import DiscoverScreen from './src/screens/DiscoverScreen'
import ProfileScreen from './src/screens/ProfileScreen'
import CallsScreen from './src/screens/CallsScreen'

type Tab = 'chats' | 'calls' | 'discover' | 'profile'

function MainApp() {
  const { user, loading } = useAuth()
  const [tab, setTab] = useState<Tab>('chats')
  const [activeChat, setActiveChat] = useState<{ id: string; name: string; avatar: string } | null>(null)

  if (loading) {
    return <View style={styles.loading}><ActivityIndicator color="#6366f1" size="large" /></View>
  }

  if (!user) return <LoginScreen />

  if (activeChat) {
    return (
      <SafeAreaView style={styles.container} edges={['top']}>
        <StatusBar style="light" />
        <ChatDetailScreen
          chatId={activeChat.id}
          chatName={activeChat.name}
          chatAvatar={activeChat.avatar}
          onBack={() => setActiveChat(null)}
        />
      </SafeAreaView>
    )
  }

  const tabs: { id: Tab; icon: keyof typeof Ionicons.glyphMap }[] = [
    { id: 'chats', icon: 'chatbubbles' },
    { id: 'calls', icon: 'call' },
    { id: 'discover', icon: 'compass' },
    { id: 'profile', icon: 'person' },
  ]

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <StatusBar style="light" />
      {tab === 'chats' && (
        <ChatsScreen onOpenChat={(id) => {
          const names: Record<string, { name: string; avatar: string }> = {
            'conv-denpasar': { name: 'Visit Denpasar', avatar: 'https://i.pravatar.cc/150?u=denpasar' },
            'conv-kira': { name: 'Kira Lindegaard', avatar: 'https://i.pravatar.cc/150?u=kira' },
            'conv-bestgirls': { name: 'Best girls', avatar: 'https://i.pravatar.cc/150?u=bestgirls' },
          }
          const chat = names[id] || { name: 'Chat', avatar: 'https://i.pravatar.cc/150?u=chat' }
          setActiveChat({ id, ...chat })
        }} />
      )}
      {tab === 'calls' && <CallsScreen />}
      {tab === 'discover' && <DiscoverScreen />}
      {tab === 'profile' && <ProfileScreen />}

      <View style={styles.nav}>
        <View style={styles.navBar}>
          {tabs.map(t => (
            <TouchableOpacity key={t.id} onPress={() => setTab(t.id)} style={[styles.navItem, tab === t.id && styles.navActive]}>
              {tab === t.id && t.id === 'chats' ? (
                <View style={styles.logoSmall}><Text style={styles.logoSmallText}>K</Text></View>
              ) : (
                <Ionicons name={t.icon} size={22} color={tab === t.id ? '#fff' : 'rgba(255,255,255,0.4)'} />
              )}
            </TouchableOpacity>
          ))}
          <TouchableOpacity style={styles.navPlus}>
            <Ionicons name="add" size={22} color="rgba(255,255,255,0.7)" />
          </TouchableOpacity>
        </View>
      </View>
    </SafeAreaView>
  )
}

export default function App() {
  return (
    <SafeAreaProvider>
      <AuthProvider>
        <MainApp />
      </AuthProvider>
    </SafeAreaProvider>
  )
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#0a0a0f' },
  loading: { flex: 1, backgroundColor: '#0a0a0f', alignItems: 'center', justifyContent: 'center' },
  nav: { position: 'absolute', bottom: 24, left: 0, right: 0, alignItems: 'center' },
  navBar: {
    flexDirection: 'row', alignItems: 'center', backgroundColor: 'rgba(255,255,255,0.1)',
    borderRadius: 40, paddingHorizontal: 8, paddingVertical: 6, gap: 4,
    borderWidth: 1, borderColor: 'rgba(255,255,255,0.12)',
  },
  navItem: { padding: 10, borderRadius: 24 },
  navActive: { backgroundColor: '#6366f1' },
  navPlus: { width: 40, height: 40, borderRadius: 20, backgroundColor: 'rgba(255,255,255,0.1)', alignItems: 'center', justifyContent: 'center', marginLeft: 4 },
  logoSmall: { width: 24, height: 24, borderRadius: 12, backgroundColor: 'rgba(255,255,255,0.2)', alignItems: 'center', justifyContent: 'center' },
  logoSmallText: { color: '#fff', fontSize: 12, fontWeight: '700' },
})
