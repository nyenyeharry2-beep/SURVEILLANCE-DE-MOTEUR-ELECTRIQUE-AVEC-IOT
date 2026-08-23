import React, { forwardRef } from 'react';
import { Image, StyleSheet, Text, View } from 'react-native';
import QRCode from 'react-native-qrcode-svg';
import ViewShot from 'react-native-view-shot';
import { getStyleById } from '../lib/styles';
import { Guest, TemplateConfig } from '../lib/types';

interface Props {
  guest: Guest;
  config: TemplateConfig;
  width?: number;
}

function formatGuestName(name: string, styleId: string): string {
  if (styleId === 'royal-bordeaux') {
    const upper = name.toUpperCase();
    return `~ ~ ${upper} ~ ~`;
  }
  return name;
}

export const InvitationCanvas = forwardRef<React.ElementRef<typeof ViewShot>, Props>(
  function InvitationCanvas({ guest, config, width = 320 }, ref) {
    const height = width * (1520 / 1080);
    const styleId = guest.styleId || config.styleId || 'kipushi-floral';
    const style = getStyleById(styleId);
    const zones = { ...config, ...style.config };

    const qrData = JSON.stringify({
      id: guest.id,
      name: guest.fullName,
      table: guest.tableZone,
      seats: guest.seats,
    });

    const guestNameStyle = {
      left: zones.guestNameZone.x * width,
      top: zones.guestNameZone.y * height,
      width: zones.guestNameZone.width * width,
      fontSize: zones.guestNameZone.fontSize * (width / 340),
      color: zones.guestNameZone.color,
      textAlign: zones.guestNameZone.align as 'left' | 'center' | 'right',
      fontWeight: styleId === 'royal-bordeaux' ? '800' as const : '700' as const,
      letterSpacing: styleId === 'royal-bordeaux' ? 2 : 0,
    };

    const qrSize = zones.qrCodeZone.size * width;
    const placementStyle = {
      left: zones.placementZone.x * width,
      top: zones.placementZone.y * height,
      width: zones.placementZone.width * width,
      fontSize: zones.placementZone.fontSize * (width / 340),
      color: zones.placementZone.color,
      textAlign: 'center' as const,
    };

    const templateSource = config.templateUri ? { uri: config.templateUri } : style.template;

    return (
      <ViewShot ref={ref} options={{ format: 'png', quality: 1 }}>
        <View style={[styles.container, { width, height }]}>
          <Image source={templateSource} style={{ width, height }} resizeMode="cover" />

          {config.embedGuestName && (
            <Text style={[styles.guestName, guestNameStyle]} numberOfLines={2}>
              {formatGuestName(guest.fullName, styleId)}
            </Text>
          )}

          {(guest.tableZone || guest.seats > 0) && styleId !== 'royal-bordeaux' && (
            <Text style={[styles.placement, placementStyle]}>
              {guest.seats > 0 ? `${guest.seats} place${guest.seats > 1 ? 's' : ''}` : ''}
              {guest.tableZone ? ` • ${guest.tableZone}` : ''}
            </Text>
          )}

          <View
            style={[
              styles.qrWrapper,
              {
                left: zones.qrCodeZone.x * width,
                top: zones.qrCodeZone.y * height,
                width: qrSize,
                height: qrSize,
              },
            ]}
          >
            <QRCode
              value={qrData}
              size={qrSize - 8}
              backgroundColor="#ffffff"
              color={styleId === 'royal-bordeaux' ? '#5C1A1A' : '#5a2d82'}
            />
          </View>
        </View>
      </ViewShot>
    );
  },
);

const styles = StyleSheet.create({
  container: { position: 'relative', overflow: 'hidden', backgroundColor: '#fff' },
  guestName: { position: 'absolute', fontFamily: 'serif' },
  placement: { position: 'absolute', fontWeight: '600' },
  qrWrapper: {
    position: 'absolute',
    backgroundColor: '#fff',
    padding: 4,
    borderRadius: 4,
  },
});
