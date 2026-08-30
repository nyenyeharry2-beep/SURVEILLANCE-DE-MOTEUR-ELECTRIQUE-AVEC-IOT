import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';
import { getCurrentUser, loginUser, logoutUser, registerUser, updateUserName } from '../services/authService';
import type { UserProfile } from '../types/document';

interface AuthContextValue {
  user: UserProfile | null;
  loading: boolean;
  register: (name: string, email: string, password: string) => Promise<void>;
  login: (email: string, password: string) => Promise<void>;
  logout: () => void;
  updateName: (name: string) => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<UserProfile | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getCurrentUser()
      .then(setUser)
      .finally(() => setLoading(false));
  }, []);

  const register = useCallback(async (name: string, email: string, password: string) => {
    const profile = await registerUser(name, email, password);
    setUser(profile);
  }, []);

  const login = useCallback(async (email: string, password: string) => {
    const profile = await loginUser(email, password);
    setUser(profile);
  }, []);

  const logout = useCallback(() => {
    logoutUser();
    setUser(null);
  }, []);

  const updateName = useCallback(async (name: string) => {
    if (!user) {
      return;
    }
    const updated = await updateUserName(user.id, name);
    setUser(updated);
  }, [user]);

  const value = useMemo(
    () => ({ user, loading, register, login, logout, updateName }),
    [user, loading, register, login, logout, updateName],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
}
