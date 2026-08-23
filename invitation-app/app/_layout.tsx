import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';

export default function RootLayout() {
  return (
    <>
      <StatusBar style="auto" />
      <Stack
        screenOptions={{
          headerShown: false,
          contentStyle: { backgroundColor: '#F5F0EB' },
          animation: 'slide_from_right',
        }}
      >
        <Stack.Screen name="index" />
        <Stack.Screen name="config" options={{ contentStyle: { backgroundColor: '#0D0D0D' } }} />
        <Stack.Screen name="add-guest" />
        <Stack.Screen name="preview" options={{ presentation: 'modal' }} />
        <Stack.Screen name="dashboard" />
      </Stack>
    </>
  );
}
