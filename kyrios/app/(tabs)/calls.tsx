import { Ionicons } from '@expo/vector-icons';
import { StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Colors, FontSize, FontWeight, Spacing } from '../../constants/theme';

const RECENT_CALLS = [
  { name: 'Kira Lindegaard', type: 'incoming', time: '2 mins ago', video: false },
  { name: 'Peter Johnson', type: 'outgoing', time: '15 mins ago', video: true },
  { name: 'Visit Denpasar', type: 'missed', time: '1 hour ago', video: false },
  { name: 'Nia Denton', type: 'incoming', time: '3 hours ago', video: true },
];

export default function CallsScreen() {
  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <View style={styles.header}>
        <Text style={styles.title}>Calls</Text>
      </View>

      {RECENT_CALLS.map((call) => (
        <View key={call.name} style={styles.callItem}>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>{call.name.charAt(0)}</Text>
          </View>
          <View style={styles.callInfo}>
            <Text style={[styles.name, call.type === 'missed' && styles.missed]}>
              {call.name}
            </Text>
            <View style={styles.callMeta}>
              <Ionicons
                name={
                  call.type === 'incoming'
                    ? 'arrow-down'
                    : call.type === 'outgoing'
                      ? 'arrow-up'
                      : 'close'
                }
                size={14}
                color={call.type === 'missed' ? Colors.unread : Colors.textMuted}
              />
              <Text style={styles.time}>{call.time}</Text>
            </View>
          </View>
          <View style={styles.actions}>
            <Ionicons name="call-outline" size={22} color={Colors.accent} />
            {call.video && (
              <Ionicons name="videocam-outline" size={22} color={Colors.accent} />
            )}
          </View>
        </View>
      ))}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: Colors.background,
    paddingBottom: 100,
  },
  header: {
    paddingHorizontal: Spacing.lg,
    paddingVertical: Spacing.md,
  },
  title: {
    color: Colors.text,
    fontSize: FontSize.title,
    fontWeight: FontWeight.bold,
  },
  callItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: Spacing.lg,
    paddingVertical: Spacing.md,
    gap: Spacing.md,
  },
  avatar: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: Colors.surfaceLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    color: Colors.text,
    fontSize: FontSize.lg,
    fontWeight: '600',
  },
  callInfo: {
    flex: 1,
  },
  name: {
    color: Colors.text,
    fontSize: FontSize.md,
    fontWeight: '600',
  },
  missed: {
    color: Colors.unread,
  },
  callMeta: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: 2,
  },
  time: {
    color: Colors.textMuted,
    fontSize: FontSize.sm,
  },
  actions: {
    flexDirection: 'row',
    gap: Spacing.lg,
  },
});
