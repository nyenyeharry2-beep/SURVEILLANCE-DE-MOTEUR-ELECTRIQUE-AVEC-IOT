import { NavLink } from "react-router-dom";
import "./BottomNav.css";

const tabs = [
  { to: "/", label: "Messages", icon: "💬" },
  { to: "/communities", label: "Communities", icon: "👥" },
  { to: "/discover", label: "Discover", icon: "🧭" },
  { to: "/insights", label: "Insights", icon: "📊" },
  { to: "/profile", label: "Profile", icon: "👤" },
];

export function BottomNav() {
  return (
    <nav className="bottom-nav">
      {tabs.map((tab) => (
        <NavLink
          key={tab.to}
          to={tab.to}
          end={tab.to === "/"}
          className={({ isActive }) => `bottom-nav__item${isActive ? " bottom-nav__item--active" : ""}`}
        >
          <span className="bottom-nav__icon" aria-hidden>
            {tab.icon}
          </span>
          <span className="bottom-nav__label">{tab.label}</span>
        </NavLink>
      ))}
    </nav>
  );
}
