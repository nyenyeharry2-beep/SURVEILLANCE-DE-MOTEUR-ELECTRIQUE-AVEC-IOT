import { BrowserRouter, Navigate, Route, Routes, useLocation } from "react-router-dom";
import { BottomNav } from "./components/BottomNav";
import { ChatScreen } from "./screens/ChatScreen";
import { CommunitiesScreen } from "./screens/CommunitiesScreen";
import { DiscoverScreen } from "./screens/DiscoverScreen";
import { InsightsScreen } from "./screens/InsightsScreen";
import { MessagesScreen } from "./screens/MessagesScreen";
import { ProfileScreen } from "./screens/ProfileScreen";
import "./App.css";

function AppShell() {
  const location = useLocation();
  const hideNav = location.pathname.startsWith("/chat/");

  return (
    <div className="app-shell">
      <main className="app-main">
        <Routes>
          <Route path="/" element={<MessagesScreen />} />
          <Route path="/chat/:id" element={<ChatScreen />} />
          <Route path="/communities" element={<CommunitiesScreen />} />
          <Route path="/insights" element={<InsightsScreen />} />
          <Route path="/profile" element={<ProfileScreen />} />
          <Route path="/discover" element={<DiscoverScreen />} />
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </main>
      {!hideNav && <BottomNav />}
    </div>
  );
}

export default function App() {
  return (
    <BrowserRouter>
      <AppShell />
    </BrowserRouter>
  );
}
