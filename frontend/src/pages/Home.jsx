import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../api';

export function Home() {
  const [stats, setStats] = useState({
    total_plants: 0,
    total_species: 0,
    total_zones: 0,
    total_contributors: 0,
    recent_observations: []
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.getStats()
      .then(res => {
        if (res.success) {
          setStats(res);
        }
      })
      .catch(err => console.error(err))
      .finally(() => setLoading(false));
  }, []);

  return (
    <div className="container" style={{ maxWidth: '1100px', marginTop: '2rem' }}>
      
      {/* Hero Section */}
      <div style={{ textAlign: 'center', padding: '3rem 1.5rem 2rem', maxWidth: '850px', margin: '0 auto' }}>
        <div className="live-pulse" style={{ marginBottom: '1.5rem' }}>
          <span className="pulse-dot"></span> Live Campus Biodiversity Platform
        </div>
        <h1 style={{ fontSize: '3rem', lineHeight: 1.15, marginBottom: '1.2rem', fontWeight: 800 }}>
          Sanjivani Campus <span style={{ color: 'var(--accent-primary)' }}>Plant Diversity Mapper</span>
        </h1>
        <p style={{ fontSize: '1.1rem', color: 'var(--text-secondary)', marginBottom: '2.25rem', lineHeight: 1.7 }}>
          An interactive, geotagged biodiversity mapping platform for Sanjivani University.
          Identify, catalog, and preserve medicinal herbs and native plant species across campus zones.
        </p>
        <div style={{ display: 'flex', gap: '1rem', justifyContent: 'center', flexWrap: 'wrap' }}>
          <Link to="/dashboard" className="btn btn-primary btn-lg">
            <i className="fa-solid fa-map-location-dot"></i> Explore Live Campus Map
          </Link>
          <Link to="/capture" className="btn btn-secondary btn-lg">
            <i className="fa-solid fa-camera"></i> Capture New Observation
          </Link>
        </div>
      </div>

      {/* KPI Stats */}
      <div className="stats-grid" style={{ marginTop: '2.5rem' }}>
        <div className="kpi-card glass-card">
          <div>
            <div className="kpi-title">Mapped Plants</div>
            <div className="kpi-value">{loading ? '...' : stats.total_plants}</div>
          </div>
          <div className="kpi-icon"><i className="fa-solid fa-tree"></i></div>
        </div>

        <div className="kpi-card glass-card">
          <div>
            <div className="kpi-title">Species Cataloged</div>
            <div className="kpi-value">{loading ? '...' : stats.total_species}</div>
          </div>
          <div className="kpi-icon" style={{ background: '#eff6ff', color: '#2563eb', borderColor: '#bfdbfe' }}>
            <i className="fa-solid fa-seedling"></i>
          </div>
        </div>

        <div className="kpi-card glass-card">
          <div>
            <div className="kpi-title">Campus Zones</div>
            <div className="kpi-value">{loading ? '...' : stats.total_zones}</div>
          </div>
          <div className="kpi-icon" style={{ background: '#fffbeb', color: '#d97706', borderColor: '#fde68a' }}>
            <i className="fa-solid fa-draw-polygon"></i>
          </div>
        </div>

        <div className="kpi-card glass-card">
          <div>
            <div className="kpi-title">Contributors</div>
            <div className="kpi-value">{loading ? '...' : stats.total_contributors}</div>
          </div>
          <div className="kpi-icon" style={{ background: '#fdf2f8', color: '#db2777', borderColor: '#fbcfe8' }}>
            <i className="fa-solid fa-users"></i>
          </div>
        </div>
      </div>

      {/* Recent Observations */}
      {stats.recent_observations && stats.recent_observations.length > 0 && (
        <div style={{ marginTop: '3rem' }}>
          <h2 style={{ marginBottom: '1.25rem', fontSize: '1.6rem' }}>
            <i className="fa-solid fa-clock-rotate-left"></i> Recent Verified Observations
          </h2>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))', gap: '1.25rem' }}>
            {stats.recent_observations.map(obs => (
              <div key={obs.id} className="glass-card" style={{ padding: '1.25rem', display: 'flex', flexDirection: 'column' }}>
                <div style={{ height: '140px', background: '#f1f5f9', borderRadius: 'var(--radius-sm)', overflow: 'hidden', marginBottom: '1rem', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                  {obs.photo_url ? (
                    <img src={obs.photo_url} alt={obs.common_name} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                  ) : (
                    <span style={{ fontSize: '2.5rem', color: 'var(--accent-primary)' }}>🌿</span>
                  )}
                </div>
                <h4 style={{ fontSize: '1.05rem', marginBottom: '0.2rem' }}>{obs.common_name || obs.scientific_name}</h4>
                <div style={{ fontStyle: 'italic', fontSize: '0.85rem', color: 'var(--text-secondary)', marginBottom: '0.75rem' }}>
                  {obs.scientific_name}
                </div>
                <div style={{ marginTop: 'auto', display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '0.8rem', color: 'var(--text-muted)' }}>
                  <span>📍 {obs.zone_name || 'Campus Zone'}</span>
                  <span className="badge badge-verified">Verified</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

    </div>
  );
}
