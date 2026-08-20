import React, { useState, useEffect, useRef } from 'react'
import {
  View, Text, FlatList, Image, TouchableOpacity, StyleSheet,
  TextInput, KeyboardAvoidingView, Platform, ActivityIndicator,
} from 'react-native'
import { Ionicons } from '@expo/vector-icons'
import { chats as chatsApi, Message } from '../api/client'

interface Props {
  chatId: string
  chatName: string
  chatAvatar: string
  onBack: () => void
}

export default function ChatDetailScreen({ chatId, chatName, chatAvatar, onBack }: Props) {
  const [messages, setMessages] = useState<Message[]>([])
  const [text, setText] = useState('')
  const [loading, setLoading] = useState(true)
  const listRef = useRef<FlatList>(null)

  useEffect(() => {
    chatsApi.messages(chatId).then(setMessages).finally(() => setLoading(false))
  }, [chatId])

  const send = async () => {
    if (!text.trim()) return
    const msg = await chatsApi.send(chatId, text.trim())
    setMessages(prev => [...prev, msg])
    setText('')
    setTimeout(() => listRef.current?.scrollToEnd(), 100)
  }

  if (loading) return <View style={styles.center}><ActivityIndicator color="#6366f1" size="large" /></View>

  return (
    <KeyboardAvoidingView style={styles.container} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <View style={styles.header}>
        <TouchableOpacity onPress={onBack}><Ionicons name="arrow-back" size={24} color="#fff" /></TouchableOpacity>
        <Image source={{ uri: chatAvatar }} style={styles.headerAvatar} />
        <View style={styles.headerInfo}>
          <Text style={styles.headerName}>{chatName}</Text>
          <Text style={styles.headerStatus}>Online</Text>
        </View>
        <Ionicons name="call-outline" size={22} color="rgba(255,255,255,0.6)" style={{ marginLeft: 'auto' }} />
        <Ionicons name="videocam-outline" size={22} color="rgba(255,255,255,0.6)" style={{ marginLeft: 16 }} />
      </View>

      <FlatList
        ref={listRef}
        data={messages}
        keyExtractor={item => item.id}
        contentContainerStyle={styles.messages}
        onContentSizeChange={() => listRef.current?.scrollToEnd()}
        renderItem={({ item }) => (
          <View style={[styles.bubbleRow, item.isOwn && styles.bubbleRowOwn]}>
            {!item.isOwn && <Image source={{ uri: item.senderAvatar }} style={styles.msgAvatar} />}
            <View style={[styles.bubble, item.isOwn ? styles.bubbleOwn : styles.bubbleOther]}>
              {!item.isOwn && <Text style={styles.senderName}>{item.senderName}</Text>}
              {item.text && <Text style={styles.msgText}>{item.text}</Text>}
              {item.images && (
                <View style={styles.imageRow}>
                  {item.images.map((img, i) => (
                    <Image key={i} source={{ uri: img }} style={styles.msgImage} />
                  ))}
                </View>
              )}
              {item.reactions && (
                <View style={styles.reactions}>
                  {item.reactions.map((r, i) => (
                    <Text key={i} style={styles.reaction}>{r.emoji} {String(r.count).padStart(2, '0')}</Text>
                  ))}
                </View>
              )}
            </View>
          </View>
        )}
      />

      <View style={styles.inputBar}>
        <TextInput
          style={styles.input}
          placeholder="Type here"
          placeholderTextColor="rgba(255,255,255,0.4)"
          value={text}
          onChangeText={setText}
          onSubmitEditing={send}
        />
        <TouchableOpacity style={styles.sendBtn} onPress={send}>
          <Ionicons name="add" size={22} color="#fff" />
        </TouchableOpacity>
      </View>
    </KeyboardAvoidingView>
  )
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#0a0a0f' },
  center: { flex: 1, backgroundColor: '#0a0a0f', alignItems: 'center', justifyContent: 'center' },
  header: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: 'rgba(255,255,255,0.08)' },
  headerAvatar: { width: 40, height: 40, borderRadius: 20, marginLeft: 12 },
  headerInfo: { marginLeft: 10 },
  headerName: { fontSize: 16, fontWeight: '600', color: '#fff' },
  headerStatus: { fontSize: 12, color: '#22c55e' },
  messages: { paddingVertical: 16 },
  bubbleRow: { flexDirection: 'row', paddingHorizontal: 16, marginBottom: 12, alignItems: 'flex-end' },
  bubbleRowOwn: { justifyContent: 'flex-end' },
  msgAvatar: { width: 32, height: 32, borderRadius: 16, marginRight: 8 },
  bubble: { maxWidth: '75%', borderRadius: 20, padding: 12, backgroundColor: 'rgba(255,255,255,0.08)' },
  bubbleOwn: { backgroundColor: 'rgba(99,102,241,0.35)', borderBottomRightRadius: 4 },
  bubbleOther: { borderBottomLeftRadius: 4 },
  senderName: { fontSize: 12, fontWeight: '600', color: '#818cf8', marginBottom: 4 },
  msgText: { fontSize: 15, color: '#fff', lineHeight: 22 },
  imageRow: { flexDirection: 'row', gap: 4, marginTop: 8 },
  msgImage: { width: 72, height: 72, borderRadius: 12 },
  reactions: { flexDirection: 'row', gap: 8, marginTop: 6 },
  reaction: { fontSize: 12, backgroundColor: 'rgba(255,255,255,0.1)', paddingHorizontal: 8, paddingVertical: 2, borderRadius: 12, overflow: 'hidden' },
  inputBar: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 12, paddingBottom: 24, gap: 8 },
  input: { flex: 1, backgroundColor: 'rgba(255,255,255,0.08)', borderRadius: 24, paddingHorizontal: 16, paddingVertical: 10, color: '#fff', fontSize: 15 },
  sendBtn: { width: 40, height: 40, borderRadius: 20, backgroundColor: 'rgba(255,255,255,0.12)', alignItems: 'center', justifyContent: 'center' },
})
