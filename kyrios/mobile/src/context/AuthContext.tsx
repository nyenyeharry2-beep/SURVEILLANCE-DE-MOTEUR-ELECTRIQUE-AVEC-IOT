import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react'
import AsyncStorage from '@react-native-async-storage/async-storage'
import { auth, setToken, User } from '../api/client'

interface AuthContextType {
  user: User | null
  loading: boolean
  login: (email: string, password: string) => Promise<void>
  logout: () => Promise<void>
}

const AuthContext = createContext<AuthContextType | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    AsyncStorage.getItem('kyrios_token').then(async (t) => {
      if (t) {
        setToken(t)
        try {
          const me = await auth.me()
          setUser(me)
        } catch {
          await AsyncStorage.removeItem('kyrios_token')
          setToken(null)
        }
      }
      setLoading(false)
    })
  }, [])

  const login = async (email: string, password: string) => {
    const { token, user: u } = await auth.login(email, password)
    setToken(token)
    await AsyncStorage.setItem('kyrios_token', token)
    setUser(u)
  }

  const logout = async () => {
    setToken(null)
    setUser(null)
    await AsyncStorage.removeItem('kyrios_token')
  }

  return (
    <AuthContext.Provider value={{ user, loading, login, logout }}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used within AuthProvider')
  return ctx
}
