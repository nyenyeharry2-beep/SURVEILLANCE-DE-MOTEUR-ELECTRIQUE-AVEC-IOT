import React from 'react';
import { Pressable, StyleSheet, Text, TextInput, TextInputProps, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { theme } from '../../lib/theme';

interface OutlinedInputProps extends TextInputProps {
  label: string;
  dark?: boolean;
  icon?: keyof typeof Ionicons.glyphMap;
  rightAction?: { icon: keyof typeof Ionicons.glyphMap; onPress: () => void };
  hint?: string;
}

export function OutlinedInput({
  label,
  dark = false,
  icon,
  rightAction,
  hint,
  style,
  ...props
}: OutlinedInputProps) {
  return (
    <View style={styles.wrapper}>
      <View style={[styles.field, dark ? styles.fieldDark : styles.fieldLight]}>
        <Text style={[styles.label, dark ? styles.labelDark : styles.labelLight]}>{label}</Text>
        <View style={styles.inputRow}>
          {icon && <Ionicons name={icon} size={20} color={dark ? '#888' : theme.gold} style={styles.icon} />}
          <TextInput
            style={[styles.input, dark ? styles.inputDark : styles.inputLight, style]}
            placeholderTextColor={dark ? '#666' : '#bbb'}
            {...props}
          />
          {rightAction && (
            <Pressable onPress={rightAction.onPress} hitSlop={8}>
              <Ionicons name={rightAction.icon} size={22} color={theme.gold} />
            </Pressable>
          )}
        </View>
      </View>
      {hint ? <Text style={[styles.hint, dark && styles.hintDark]}>{hint}</Text> : null}
    </View>
  );
}

export function BlueCheckbox({
  checked,
  onToggle,
  label,
}: {
  checked: boolean;
  onToggle: () => void;
  label: string;
}) {
  return (
    <Pressable style={styles.checkRow} onPress={onToggle}>
      <View style={[styles.checkbox, checked && styles.checkboxChecked]}>
        {checked && <Ionicons name="checkmark" size={16} color="#fff" />}
      </View>
      <Text style={styles.checkLabel}>{label}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  wrapper: { marginBottom: 16 },
  field: { borderWidth: 1.5, borderRadius: 12, paddingHorizontal: 14, paddingTop: 10, paddingBottom: 6 },
  fieldDark: { borderColor: theme.configBorder, backgroundColor: theme.configInputBg },
  fieldLight: { borderColor: '#E0D5C8', backgroundColor: '#FAFAFA' },
  label: { fontSize: 12, marginBottom: 4 },
  labelDark: { color: theme.configMuted },
  labelLight: { color: theme.textMuted },
  inputRow: { flexDirection: 'row', alignItems: 'center' },
  icon: { marginRight: 8 },
  input: { flex: 1, fontSize: 15, paddingVertical: 6 },
  inputDark: { color: theme.configText },
  inputLight: { color: theme.textDark },
  hint: { fontSize: 11, color: theme.textMuted, marginTop: 6, lineHeight: 16 },
  hintDark: { color: theme.configMuted },
  checkRow: { flexDirection: 'row', alignItems: 'center', gap: 12, marginTop: 8 },
  checkbox: {
    width: 24,
    height: 24,
    borderRadius: 4,
    borderWidth: 2,
    borderColor: theme.configBlue,
    alignItems: 'center',
    justifyContent: 'center',
  },
  checkboxChecked: { backgroundColor: theme.configBlue },
  checkLabel: { flex: 1, color: theme.configText, fontSize: 14 },
});
