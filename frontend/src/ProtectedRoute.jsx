import React from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from './AuthContext';

export function ProtectedRoute({ children, roleRequired }) {
  const { user } = useAuth();

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  if (roleRequired) {
    const roleHierarchy = { contributor: 1, verifier: 2, admin: 3 };
    const userLevel = roleHierarchy[user.role] || 0;
    const requiredLevel = roleHierarchy[roleRequired] || 0;

    if (userLevel < requiredLevel) {
      return <Navigate to="/dashboard" replace />;
    }
  }

  return children;
}
