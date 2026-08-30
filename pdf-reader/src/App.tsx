import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AppLayout } from './components/layout/AppLayout';
import { LibraryProvider } from './context/LibraryContext';
import { HomePage } from './routes/HomePage';
import { LibraryPage } from './routes/LibraryPage';
import { ReaderPage } from './routes/ReaderPage';

function App() {
  return (
    <LibraryProvider>
      <BrowserRouter>
        <Routes>
          <Route element={<AppLayout />}>
            <Route index element={<HomePage />} />
            <Route path="library" element={<LibraryPage />} />
            <Route path="reader/:id" element={<ReaderPage />} />
            <Route path="*" element={<Navigate to="/" replace />} />
          </Route>
        </Routes>
      </BrowserRouter>
    </LibraryProvider>
  );
}

export default App;
