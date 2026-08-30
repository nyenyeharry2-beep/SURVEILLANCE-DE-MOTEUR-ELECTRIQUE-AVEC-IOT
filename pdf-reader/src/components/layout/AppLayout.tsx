import { NavLink, Outlet } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import './AppLayout.css';

const navItems = [
  { to: '/', label: 'Accueil', end: true },
  { to: '/library', label: 'Bibliothèque', end: false },
];

export function AppLayout() {
  const { user } = useAuth();

  return (
    <div className="app-shell">
      <header className="app-header">
        <div className="app-header__brand">
          <span className="app-header__logo" aria-hidden="true">
            📖
          </span>
          <div>
            <p className="app-header__eyebrow">Lumen Reader</p>
            <h1 className="app-header__title">Lecteur PDF intelligent</h1>
          </div>
        </div>

        <nav className="app-header__nav" aria-label="Navigation principale">
          {navItems.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.end}
              className={({ isActive }) =>
                `app-header__link${isActive ? ' app-header__link--active' : ''}`
              }
            >
              {item.label}
            </NavLink>
          ))}
          <NavLink
            to={user ? '/profile' : '/login'}
            className={({ isActive }) =>
              `app-header__link${isActive ? ' app-header__link--active' : ''}`
            }
          >
            {user ? 'Profil' : 'Connexion'}
          </NavLink>
        </nav>
      </header>

      <main className="app-main">
        <Outlet />
      </main>

      <nav className="bottom-nav" aria-label="Navigation mobile">
        {navItems.map((item) => (
          <NavLink
            key={item.to}
            to={item.to}
            end={item.end}
            className={({ isActive }) =>
              `bottom-nav__link${isActive ? ' bottom-nav__link--active' : ''}`
            }
          >
            {item.label}
          </NavLink>
        ))}
        <NavLink
          to={user ? '/profile' : '/login'}
          className={({ isActive }) =>
            `bottom-nav__link${isActive ? ' bottom-nav__link--active' : ''}`
          }
        >
          {user ? 'Profil' : 'Compte'}
        </NavLink>
      </nav>
    </div>
  );
}
