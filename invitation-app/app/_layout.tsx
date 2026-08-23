import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';

export default function RootLayout() {
  return (
    <>
      <StatusBar style="dark" />
      <Stack
        screenOptions={{
          headerStyle: { backgroundColor: '#f8f4fc' },
          headerTintColor: '#5a2d82',
          headerTitleStyle: { fontWeight: '700' },
          contentStyle: { backgroundColor: '#faf8fc' },
        }}
      >
        <Stack.Screen name="index" options={{ title: 'Invitations Moïse & Sarah' }} />
        <Stack.Screen name="config" options={{ title: 'Configurer l\'événement' }} />
        <Stack.Screen name="add-guest" options={{ title: 'Ajouter un invité' }} />
        <Stack.Screen name="preview" options={{ title: 'Aperçu final', presentation: 'modal' }} />
        <Stack.Screen name="dashboard" options={{ title: 'Liste des invités' }} />
      </Stack>
    </>
  );
}
