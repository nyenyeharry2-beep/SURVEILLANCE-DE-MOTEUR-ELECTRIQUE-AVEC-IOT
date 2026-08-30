import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';
import { getPreferences, savePreferences } from '../services/db';
import type { UserPreferences } from '../types/document';

interface PreferencesContextValue {
  preferences: UserPreferences;
  loading: boolean;
  updatePreferences: (patch: Partial<UserPreferences>) => Promise<void>;
}

const PreferencesContext = createContext<PreferencesContextValue | null>(null);

export function PreferencesProvider({ children }: { children: ReactNode }) {
  const [preferences, setPreferences] = useState<UserPreferences>({
    id: 'default',
    speed: 1,
    voiceUri: null,
    language: 'fr-FR',
    autoPlay: false,
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getPreferences()
      .then(setPreferences)
      .finally(() => setLoading(false));
  }, []);

  const updatePreferences = useCallback(async (patch: Partial<UserPreferences>) => {
    const next = { ...preferences, ...patch, id: 'default' as const };
    setPreferences(next);
    await savePreferences(next);
  }, [preferences]);

  const value = useMemo(
    () => ({ preferences, loading, updatePreferences }),
    [preferences, loading, updatePreferences],
  );

  return (
    <PreferencesContext.Provider value={value}>{children}</PreferencesContext.Provider>
  );
}

export function usePreferences(): PreferencesContextValue {
  const context = useContext(PreferencesContext);
  if (!context) {
    throw new Error('usePreferences must be used within PreferencesProvider');
  }
  return context;
}
