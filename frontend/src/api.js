/**
 * API Client for Sanjivani Herb PHP Backend
 */

const API_BASE = 'http://localhost:8000/api';

/**
 * Helper to make API requests with Auth Token
 */
async function request(endpoint, options = {}) {
  const token = localStorage.getItem('token');
  const headers = {
    'Accept': 'application/json',
    ...(options.headers || {})
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  // Do not set Content-Type for FormData (browser sets boundary automatically)
  if (options.body && !(options.body instanceof FormData)) {
    headers['Content-Type'] = 'application/json';
  }

  const response = await fetch(`${API_BASE}${endpoint}`, {
    ...options,
    headers
  });

  const data = await response.json().catch(() => ({ error: 'Failed to parse response' }));

  if (!response.ok) {
    throw new Error(data.error || `HTTP error ${response.status}`);
  }

  return data;
}

export const api = {
  // Public Stats for Homepage
  getStats: () => request('/stats.php'),

  // Auth APIs
  login: async (email, password) => {
    const data = await request('/auth/login.php', {
      method: 'POST',
      body: JSON.stringify({ email, password })
    });
    if (data.token) {
      localStorage.setItem('token', data.token);
      localStorage.setItem('user', JSON.stringify(data.user));
    }
    return data;
  },

  register: async (email, password, fullName) => {
    const data = await request('/auth/register.php', {
      method: 'POST',
      body: JSON.stringify({ email, password, full_name: fullName })
    });
    if (data.token) {
      localStorage.setItem('token', data.token);
      localStorage.setItem('user', JSON.stringify(data.user));
    }
    return data;
  },

  logout: async () => {
    try {
      await request('/auth/logout.php', { method: 'POST' });
    } catch (e) {
      // Ignore logout errors
    } finally {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
    }
  },

  // Plants
  getPlants: (params = {}) => {
    const query = new URLSearchParams(params).toString();
    return request(`/plants/list.php?${query}`);
  },

  createPlant: (formData) => {
    return request('/plants/create.php', {
      method: 'POST',
      body: formData
    });
  },

  // Species Catalog
  getSpecies: (params = {}) => {
    const query = new URLSearchParams(params).toString();
    return request(`/species/list.php?${query}`);
  },

  // Campus Zones
  getZones: () => request('/zones/list.php'),

  // Analytics
  getAnalytics: () => request('/analytics/summary.php'),

  // Verification Queue (Verifier/Admin)
  getVerifyQueue: (page = 1) => request(`/verify/queue.php?page=${page}`),

  verifyAction: (actionData) => {
    return request('/verify/action.php', {
      method: 'POST',
      body: JSON.stringify(actionData)
    });
  }
};
