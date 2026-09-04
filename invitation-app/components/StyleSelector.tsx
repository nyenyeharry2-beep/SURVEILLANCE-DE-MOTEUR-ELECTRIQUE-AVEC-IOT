import React from 'react';
import { Image, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { INVITATION_STYLES } from '../lib/styles';
import { theme } from '../lib/theme';

interface Props {
  selectedId: string;
  onSelect: (id: string) => void;
}

export function StyleSelector({ selectedId, onSelect }: Props) {
  return (
    <View style={styles.section}>
      <Text style={styles.heading}>Choisir le style d'invitation</Text>
      <Text style={styles.desc}>Sélectionnez une ambiance visuelle élégante pour l'aperçu final.</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.row}>
        {INVITATION_STYLES.map((style) => {
          const selected = style.id === selectedId;
          return (
            <Pressable
              key={style.id}
              style={[styles.card, selected && styles.cardSelected]}
              onPress={() => onSelect(style.id)}
            >
              {selected && (
                <View style={styles.checkBadge}>
                  <Ionicons name="checkmark-circle" size={22} color={theme.gold} />
                </View>
              )}
              <View style={styles.previewWrap}>
                <Image source={style.template} style={styles.previewImg} resizeMode="cover" />
              </View>
              <Text style={styles.styleName}>{style.name}</Text>
              <Text style={styles.styleSub}>{style.subtitle}</Text>
            </Pressable>
          );
        })}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  section: { marginTop: 8, marginBottom: 8 },
  heading: { fontSize: 16, fontWeight: '800', color: theme.textDark, marginBottom: 4 },
  desc: { fontSize: 13, color: theme.textMuted, marginBottom: 14, lineHeight: 18 },
  row: { gap: 12, paddingRight: 8 },
  card: {
    width: 140,
    backgroundColor: '#fff',
    borderRadius: 16,
    padding: 10,
    borderWidth: 2,
    borderColor: 'transparent',
    ...theme.shadow,
  },
  cardSelected: { borderColor: theme.gold },
  checkBadge: { position: 'absolute', top: 6, right: 6, zIndex: 2 },
  previewWrap: {
    height: 180,
    borderRadius: 10,
    overflow: 'hidden',
    marginBottom: 8,
    backgroundColor: '#f0f0f0',
  },
  previewImg: { width: '100%', height: '100%' },
  styleName: { fontSize: 12, fontWeight: '700', color: theme.textDark },
  styleSub: { fontSize: 10, color: theme.textMuted, marginTop: 2 },
});
