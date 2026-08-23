import React, { forwardRef, useMemo } from 'react';
import { Image, StyleSheet, Text, View } from 'react-native';
import QRCode from 'react-native-qrcode-svg';
import ViewShot from 'react-native-view-shot';
import { Guest, TemplateConfig } from '../lib/types';

const TEMPLATE = require('../assets/template-invitation.png');

interface Props {
  guest: Guest;
  config: TemplateConfig;
  width?: number;
}

export const InvitationCanvas = forwardRef<React.ElementRef<typeof ViewShot>, Props>(function InvitationCanvas(
  { guest, config, width = 340 },
  ref,
) {
  const height = width * (1520 / 1080);
  const qrData = JSON.stringify({
    id: guest.id,
    name: guest.fullName,
    table: guest.tableZone,
    seats: guest.seats,
  });

  const guestNameStyle = useMemo(
    () => ({
      left: config.guestNameZone.x * width,
      top: config.guestNameZone.y * height,
      width: config.guestNameZone.width * width,
      fontSize: config.guestNameZone.fontSize * (width / 340),
      color: config.guestNameZone.color,
      textAlign: config.guestNameZone.align as 'left' | 'center' | 'right',
    }),
    [config, width, height],
  );

  const qrSize = config.qrCodeZone.size * width;
  const placementStyle = useMemo(
    () => ({
      left: config.placementZone.x * width,
      top: config.placementZone.y * height,
      width: config.placementZone.width * width,
      fontSize: config.placementZone.fontSize * (width / 340),
      color: config.placementZone.color,
    }),
    [config, width, height],
  );

  const templateSource = config.templateUri ? { uri: config.templateUri } : TEMPLATE;

  return (
    <ViewShot ref={ref} options={{ format: 'png', quality: 1 }}>
      <View style={[styles.container, { width, height }]}>
        <Image source={templateSource} style={{ width, height }} resizeMode="cover" />

        {config.embedGuestName && (
          <Text style={[styles.guestName, guestNameStyle]} numberOfLines={2}>
            {guest.fullName}
          </Text>
        )}

        {(guest.tableZone || guest.seats > 0) && (
          <Text style={[styles.placement, placementStyle]}>
            {guest.seats > 0 ? `${guest.seats} place${guest.seats > 1 ? 's' : ''}` : ''}
            {guest.tableZone ? ` • ${guest.tableZone}` : ''}
          </Text>
        )}

        <View
          style={[
            styles.qrWrapper,
            {
              left: config.qrCodeZone.x * width,
              top: config.qrCodeZone.y * height,
              width: qrSize,
              height: qrSize,
            },
          ]}
        >
          <QRCode value={qrData} size={qrSize - 8} backgroundColor="#ffffff" color="#5a2d82" />
        </View>
      </View>
    </ViewShot>
  );
});

const styles = StyleSheet.create({
  container: {
    position: 'relative',
    overflow: 'hidden',
    backgroundColor: '#fff',
  },
  guestName: {
    position: 'absolute',
    fontWeight: '700',
    fontFamily: 'serif',
  },
  placement: {
    position: 'absolute',
    textAlign: 'center',
    fontWeight: '600',
  },
  qrWrapper: {
    position: 'absolute',
    backgroundColor: '#fff',
    padding: 4,
    borderRadius: 4,
  },
});
