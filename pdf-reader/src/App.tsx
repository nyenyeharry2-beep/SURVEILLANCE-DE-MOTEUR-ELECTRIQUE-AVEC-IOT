import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AppLayout } from './components/layout/AppLayout';
import { AuthProvider } from './context/AuthContext';
import { LibraryProvider } from './context/LibraryContext';
import { PreferencesProvider } from './context/PreferencesContext';
import { HomePage } from './routes/HomePage';
import { LibraryPage } from './routes/LibraryPage';
import { LoginPage } from './routes/LoginPage';
import { SettingsPage } from './routes/SettingsPage';
import { ReaderPage } from './routes/ReaderPage';
import { RegisterPage } from './routes/RegisterPage';

function App() {
  return (
    <AuthProvider>
      <PreferencesProvider>
        <LibraryProvider>
          <BrowserRouter>
            <Routes>
              <Route element={<AppLayout />}>
                <Route index element={<HomePage />} />
                <Route path="library" element={<LibraryPage />} />
                <Route path="reader/:id" element={<ReaderPage />} />
                <Route path="login" element={<LoginPage />} />
                <Route path="register" element={<RegisterPage />} />
                <Route path="settings" element={<SettingsPage />} />
                <Route path="profile" element={<SettingsPage />} />
                <Route path="*" element={<Navigate to="/" replace />} />
              </Route>
            </Routes>
          </BrowserRouter>
        </LibraryProvider>
      </PreferencesProvider>
    </AuthProvider>
  );
}

export default App;
