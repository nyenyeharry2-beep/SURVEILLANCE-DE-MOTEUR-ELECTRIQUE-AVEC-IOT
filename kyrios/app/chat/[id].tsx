import { Ionicons } from '@expo/vector-icons';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useState } from 'react';
import {
  FlatList,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Avatar } from '../../components/Avatar';
import { MessageBubble, TimestampDivider } from '../../components/MessageBubble';
import { getChatById, getMessagesForChat } from '../../constants/data';
import { BorderRadius, Colors, FontSize, FontWeight, Spacing } from '../../constants/theme';

export default function ChatScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const chat = getChatById(id ?? '');
  const messages = getMessagesForChat(id ?? '');
  const [inputText, setInputText] = useState('');

  if (!chat) {
    return (
      <SafeAreaView style={styles.container}>
        <Text style={styles.errorText}>Chat not found</Text>
      </SafeAreaView>
    );
  }

  let lastTimestamp = '';

  return (
    <SafeAreaView style={styles.container} edges={['top', 'bottom']}>
      <View style={styles.header}>
        <Pressable onPress={() => router.back()} style={styles.backButton}>
          <Ionicons name="chevron-back" size={24} color={Colors.text} />
        </Pressable>
        <Avatar uri={chat.avatar} size={40} online={chat.online} />
        <View style={styles.headerInfo}>
          <Text style={styles.chatName}>{chat.name}</Text>
          {chat.online && <Text style={styles.onlineStatus}>Online</Text>}
        </View>
        <View style={styles.headerActions}>
          <Ionicons name="call-outline" size={22} color={Colors.text} />
          <Ionicons name="videocam-outline" size={22} color={Colors.text} />
          <Ionicons name="ellipsis-vertical" size={22} color={Colors.text} />
        </View>
      </View>

      <FlatList
        data={messages}
        keyExtractor={(item) => item.id}
        contentContainerStyle={styles.messageList}
        renderItem={({ item }) => {
          const showDivider = item.timestamp !== lastTimestamp;
          lastTimestamp = item.timestamp;
          return (
            <>
              {showDivider && <TimestampDivider label={item.timestamp} />}
              <MessageBubble message={item} />
            </>
          );
        }}
      />

      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        keyboardVerticalOffset={0}
      >
        <View style={styles.inputBar}>
          <Pressable style={styles.attachButton}>
            <Ionicons name="camera-outline" size={22} color={Colors.textSecondary} />
          </Pressable>
          <TextInput
            style={styles.input}
            placeholder="Type here"
            placeholderTextColor={Colors.textMuted}
            value={inputText}
            onChangeText={setInputText}
          />
          <Pressable style={styles.sendButton}>
            <Ionicons name="add-circle" size={32} color={Colors.accent} />
          </Pressable>
        </View>
      </KeyboardAvoidingView>
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
    paddingHorizontal: Spacing.md,
    paddingVertical: Spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: Colors.border,
    gap: Spacing.sm,
  },
  backButton: {
    padding: Spacing.xs,
  },
  headerInfo: {
    flex: 1,
  },
  chatName: {
    color: Colors.text,
    fontSize: FontSize.md,
    fontWeight: FontWeight.semibold,
  },
  onlineStatus: {
    color: Colors.online,
    fontSize: FontSize.xs,
  },
  headerActions: {
    flexDirection: 'row',
    gap: Spacing.md,
  },
  messageList: {
    paddingHorizontal: Spacing.lg,
    paddingVertical: Spacing.md,
  },
  inputBar: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: Spacing.lg,
    paddingVertical: Spacing.md,
    gap: Spacing.sm,
    borderTopWidth: 1,
    borderTopColor: Colors.border,
  },
  attachButton: {
    padding: Spacing.xs,
  },
  input: {
    flex: 1,
    backgroundColor: Colors.surfaceLight,
    borderRadius: BorderRadius.full,
    paddingHorizontal: Spacing.lg,
    paddingVertical: Spacing.md,
    color: Colors.text,
    fontSize: FontSize.md,
  },
  sendButton: {
    padding: Spacing.xs,
  },
  errorText: {
    color: Colors.text,
    textAlign: 'center',
    marginTop: Spacing.xxxl,
  },
});
