import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { AuthProvider } from './AuthContext';
import { ProtectedRoute } from './ProtectedRoute';
import { Navbar } from './components/Navbar';
import { Home } from './pages/Home';
import { Login } from './pages/Login';
import { Register } from './pages/Register';
import { Dashboard } from './pages/Dashboard';
import { Capture } from './pages/Capture';
import { Inventory } from './pages/Inventory';
import { Analytics } from './pages/Analytics';
import { Verify } from './pages/Verify';
import './App.css';

function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <div className="app-layout">
          <Navbar />
          <main>
            <Routes>
              <Route path="/" element={<Home />} />
              <Route path="/login" element={<Login />} />
              <Route path="/register" element={<Register />} />
              <Route path="/inventory" element={<Inventory />} />
              
              {/* Protected Routes */}
              <Route
                path="/dashboard"
                element={
                  <ProtectedRoute>
                    <Dashboard />
                  </ProtectedRoute>
                }
              />
              <Route
                path="/capture"
                element={
                  <ProtectedRoute>
                    <Capture />
                  </ProtectedRoute>
                }
              />
              <Route
                path="/analytics"
                element={
                  <ProtectedRoute roleRequired="verifier">
                    <Analytics />
                  </ProtectedRoute>
                }
              />
              <Route
                path="/verify"
                element={
                  <ProtectedRoute roleRequired="verifier">
                    <Verify />
                  </ProtectedRoute>
                }
              />

              {/* Fallback */}
              <Route path="*" element={<Home />} />
            </Routes>
          </main>
        </div>
      </BrowserRouter>
    </AuthProvider>
  );
}

export default App;
