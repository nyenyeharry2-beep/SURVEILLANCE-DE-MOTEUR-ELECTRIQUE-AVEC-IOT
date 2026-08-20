import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { StyleSheet, Text, View } from 'react-native';
import { BorderRadius, Colors, FontSize, Gradients } from '../constants/theme';

interface AvatarProps {
  uri: string;
  size?: number;
  online?: boolean;
  showStoryRing?: boolean;
  live?: boolean;
}

export function Avatar({ uri, size = 48, online, showStoryRing, live }: AvatarProps) {
  const innerSize = showStoryRing ? size - 4 : size;

  const content = (
    <View style={[styles.container, { width: size, height: size, borderRadius: size / 2 }]}>
      <Image
        source={{ uri }}
        style={[styles.image, { width: innerSize, height: innerSize, borderRadius: innerSize / 2 }]}
        contentFit="cover"
      />
      {online && (
        <View style={[styles.onlineDot, { right: showStoryRing ? 2 : 0, bottom: showStoryRing ? 2 : 0 }]} />
      )}
      {live && (
        <View style={styles.liveBadge}>
          <Text style={styles.liveText}>Live</Text>
        </View>
      )}
    </View>
  );

  if (showStoryRing) {
    return (
      <LinearGradient
        colors={Gradients.story}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={[styles.ring, { width: size, height: size, borderRadius: size / 2 }]}
      >
        {content}
      </LinearGradient>
    );
  }

  return content;
}

const styles = StyleSheet.create({
  container: {
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: Colors.surface,
  },
  ring: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  image: {
    backgroundColor: Colors.surfaceLight,
  },
  onlineDot: {
    position: 'absolute',
    width: 12,
    height: 12,
    borderRadius: 6,
    backgroundColor: Colors.online,
    borderWidth: 2,
    borderColor: Colors.background,
  },
  liveBadge: {
    position: 'absolute',
    bottom: -4,
    backgroundColor: Colors.accent,
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: BorderRadius.sm,
  },
  liveText: {
    color: Colors.white,
    fontSize: FontSize.xs,
    fontWeight: '700',
  },
});
