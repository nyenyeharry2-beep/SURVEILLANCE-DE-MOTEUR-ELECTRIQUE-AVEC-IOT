import React, { useState } from 'react'
import {
  View, Text, TextInput, TouchableOpacity, StyleSheet,
  KeyboardAvoidingView, Platform, ActivityIndicator, Alert,
} from 'react-native'
import { LinearGradient } from 'expo-linear-gradient'
import { useAuth } from '../context/AuthContext'

export default function LoginScreen() {
  const { login } = useAuth()
  const [email, setEmail] = useState('me@kyrios.app')
  const [password, setPassword] = useState('Kyrios2026!')
  const [loading, setLoading] = useState(false)

  const handleLogin = async () => {
    setLoading(true)
    try {
      await login(email, password)
    } catch (e: unknown) {
      Alert.alert('Erreur', e instanceof Error ? e.message : 'Connexion impossible')
    } finally {
      setLoading(false)
    }
  }

  return (
    <LinearGradient colors={['#0a0a0f', '#1a1a2e', '#0a0a0f']} style={styles.container}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={styles.inner}>
        <View style={styles.logo}>
          <LinearGradient colors={['#6366f1', '#a855f7', '#06b6d4']} style={styles.logoCircle}>
            <Text style={styles.logoText}>K</Text>
          </LinearGradient>
          <Text style={styles.title}>KYRIOS</Text>
          <Text style={styles.subtitle}>Messagerie & Réseau Social</Text>
        </View>

        <View style={styles.form}>
          <TextInput
            style={styles.input}
            placeholder="Email"
            placeholderTextColor="rgba(255,255,255,0.4)"
            value={email}
            onChangeText={setEmail}
            autoCapitalize="none"
            keyboardType="email-address"
          />
          <TextInput
            style={styles.input}
            placeholder="Mot de passe"
            placeholderTextColor="rgba(255,255,255,0.4)"
            value={password}
            onChangeText={setPassword}
            secureTextEntry
          />
          <TouchableOpacity style={styles.button} onPress={handleLogin} disabled={loading}>
            {loading ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text style={styles.buttonText}>Se connecter</Text>
            )}
          </TouchableOpacity>
          <Text style={styles.demo}>Compte demo: me@kyrios.app / Kyrios2026!</Text>
        </View>
      </KeyboardAvoidingView>
    </LinearGradient>
  )
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  inner: { flex: 1, justifyContent: 'center', padding: 24 },
  logo: { alignItems: 'center', marginBottom: 48 },
  logoCircle: { width: 80, height: 80, borderRadius: 40, alignItems: 'center', justifyContent: 'center', marginBottom: 16 },
  logoText: { fontSize: 36, fontWeight: '700', color: '#fff' },
  title: { fontSize: 32, fontWeight: '700', color: '#fff', letterSpacing: 2 },
  subtitle: { fontSize: 14, color: 'rgba(255,255,255,0.5)', marginTop: 8 },
  form: { gap: 12 },
  input: {
    backgroundColor: 'rgba(255,255,255,0.08)', borderRadius: 16, padding: 16,
    color: '#fff', fontSize: 16, borderWidth: 1, borderColor: 'rgba(255,255,255,0.1)',
  },
  button: {
    backgroundColor: '#6366f1', borderRadius: 16, padding: 16,
    alignItems: 'center', marginTop: 8,
  },
  buttonText: { color: '#fff', fontSize: 16, fontWeight: '600' },
  demo: { textAlign: 'center', color: 'rgba(255,255,255,0.4)', fontSize: 12, marginTop: 16 },
})
