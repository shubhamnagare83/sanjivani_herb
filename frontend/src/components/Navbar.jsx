import React from 'react';
import { Link, NavLink, useNavigate } from 'react-router-dom';
import { useAuth } from '../AuthContext';

export function Navbar() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <nav className="navbar">
      <Link to="/" className="nav-brand">
        <span className="icon">🌿</span>
        <span>Sanjivani Herb</span>
      </Link>

      <ul className="nav-menu">
        <li>
          <NavLink to="/" className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`} end>
            <i className="fa-solid fa-house"></i> Home
          </NavLink>
        </li>
        <li>
          <NavLink to="/dashboard" className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}>
            <i className="fa-solid fa-map-location-dot"></i> Live Map
          </NavLink>
        </li>
        <li>
          <NavLink to="/inventory" className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}>
            <i className="fa-solid fa-book-bookmark"></i> Species List
          </NavLink>
        </li>
        {user && (
          <li>
            <NavLink to="/capture" className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}>
              <i className="fa-solid fa-camera"></i> Capture Plant
            </NavLink>
          </li>
        )}
        {user && ['verifier', 'admin'].includes(user.role) && (
          <>
            <li>
              <NavLink to="/verify" className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}>
                <i className="fa-solid fa-circle-check"></i> Verify Queue
              </NavLink>
            </li>
            <li>
              <NavLink to="/analytics" className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}>
                <i className="fa-solid fa-chart-pie"></i> Analytics
              </NavLink>
            </li>
          </>
        )}

        {user ? (
          <li>
            <button onClick={handleLogout} className="btn btn-secondary btn-sm">
              <i className="fa-solid fa-right-from-bracket"></i> Logout ({user.full_name})
            </button>
          </li>
        ) : (
          <>
            <li>
              <Link to="/login" className="btn btn-secondary btn-sm">
                <i className="fa-solid fa-right-to-bracket"></i> Login
              </Link>
            </li>
            <li>
              <Link to="/register" className="btn btn-primary btn-sm">
                Register
              </Link>
            </li>
          </>
        )}
      </ul>
    </nav>
  );
}
