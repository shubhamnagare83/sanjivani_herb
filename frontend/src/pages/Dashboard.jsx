import React, { useEffect, useState, useRef } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../api';

export function Dashboard() {
  const [plants, setPlants] = useState([]);
  const [zones, setZones] = useState([]);
  const [stats, setStats] = useState({ total_plants: 0, total_species: 0, total_zones: 0, total_contributors: 0 });
  const [loading, setLoading] = useState(true);
  const [selectedZone, setSelectedZone] = useState('');
  const [selectedStatus, setSelectedStatus] = useState('');
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedPlant, setSelectedPlant] = useState(null);

  const mapRef = useRef(null);
  const leafletMap = useRef(null);
  const markersRef = useRef([]);

  // Fetch initial data
  useEffect(() => {
    Promise.all([
      api.getPlants({ public: 1 }),
      api.getZones(),
      api.getStats()
    ]).then(([plantsRes, zonesRes, statsRes]) => {
      if (plantsRes.success) setPlants(plantsRes.data || []);
      if (zonesRes.success) setZones(zonesRes.zones || []);
      if (statsRes.success) setStats(statsRes);
    }).catch(err => console.error(err))
    .finally(() => setLoading(false));
  }, []);

  // Initialize Leaflet Map
  useEffect(() => {
    if (!mapRef.current || leafletMap.current) return;

    if (window.L) {
      // Campus center (Sanjivani University default: 19.8762, 74.5981)
      const map = window.L.map(mapRef.current).setView([19.8762, 74.5981], 17);

      window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);

      leafletMap.current = map;
    }
  }, []);

  // Update map markers when plants or filters change
  useEffect(() => {
    if (!leafletMap.current || !window.L) return;

    // Clear old markers
    markersRef.current.forEach(m => leafletMap.current.removeLayer(m));
    markersRef.current = [];

    // Filter plants
    const filtered = plants.filter(p => {
      if (selectedZone && p.zone_id !== selectedZone) return false;
      if (selectedStatus && p.status !== selectedStatus) return false;
      if (searchTerm) {
        const term = searchTerm.toLowerCase();
        const common = (p.common_name || '').toLowerCase();
        const scientific = (p.scientific_name || '').toLowerCase();
        if (!common.includes(term) && !scientific.includes(term)) return false;
      }
      return true;
    });

    // Add new markers
    filtered.forEach(p => {
      if (!p.latitude || !p.longitude) return;

      const marker = window.L.marker([p.latitude, p.longitude])
        .addTo(leafletMap.current)
        .bindPopup(`
          <div style="font-family: sans-serif; padding: 4px;">
            <div style="font-weight: 700; color: #059669;">${p.common_name || p.scientific_name}</div>
            <div style="font-style: italic; font-size: 0.82rem; color: #64748b;">${p.scientific_name}</div>
            <div style="font-size: 0.8rem; margin-top: 4px;">📍 ${p.zone_name || 'Campus Zone'}</div>
          </div>
        `);

      marker.on('click', () => setSelectedPlant(p));
      markersRef.current.push(marker);
    });
  }, [plants, selectedZone, selectedStatus, searchTerm]);

  return (
    <div className="container" style={{ paddingBottom: '4rem' }}>
      
      {/* Top Header & Live Badge */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem', flexWrap: 'wrap', gap: '1rem' }}>
        <div>
          <h1 style={{ fontSize: '2.2rem', margin: 0 }}>
            <i className="fa-solid fa-map-location-dot" style={{ color: 'var(--accent-primary)' }}></i> Live Campus Map
          </h1>
          <p style={{ color: 'var(--text-secondary)', fontSize: '0.95rem' }}>
            Real-time geotagged botanical inventory across Sanjivani campus zones
          </p>
        </div>
        <div className="live-pulse">
          <span className="pulse-dot"></span> Live Sync Active
        </div>
      </div>

      {/* KPI Cards */}
      <div className="stats-grid">
        <div className="kpi-card glass-card">
          <div>
            <div className="kpi-title">Verified Plants</div>
            <div className="kpi-value">{loading ? '...' : stats.total_plants}</div>
          </div>
          <div className="kpi-icon"><i className="fa-solid fa-tree"></i></div>
        </div>

        <div className="kpi-card glass-card">
          <div>
            <div className="kpi-title">Species Count</div>
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

      {/* Filter Controls Bar */}
      <div className="glass-card" style={{ padding: '1rem 1.5rem', marginBottom: '1.5rem', display: 'flex', gap: '1rem', flexWrap: 'wrap', alignItems: 'center' }}>
        <div style={{ flex: 1, minWidth: '200px' }}>
          <input
            type="text"
            className="form-control"
            placeholder="🔍 Search plant by common or scientific name..."
            value={searchTerm}
            onChange={e => setSearchTerm(e.target.value)}
          />
        </div>

        <select
          className="form-control"
          style={{ width: 'auto', minWidth: '180px' }}
          value={selectedZone}
          onChange={e => setSelectedZone(e.target.value)}
        >
          <option value="">All Campus Zones</option>
          {zones.map(z => (
            <option key={z.id} value={z.id}>{z.name}</option>
          ))}
        </select>

        <select
          className="form-control"
          style={{ width: 'auto', minWidth: '150px' }}
          value={selectedStatus}
          onChange={e => setSelectedStatus(e.target.value)}
        >
          <option value="">All Statuses</option>
          <option value="verified">Verified Only</option>
          <option value="pending_verification">Pending Only</option>
        </select>
      </div>

      {/* Interactive Map */}
      <div className="map-container glass-card" style={{ position: 'relative' }}>
        <div ref={mapRef} style={{ width: '100%', height: '100%' }}></div>
      </div>

      {/* Selected Plant Detail Modal / Drawer */}
      {selectedPlant && (
        <div style={{ position: 'fixed', bottom: '2rem', left: '50%', transform: 'translateX(-50%)', width: '90%', maxWidth: '520px', background: '#ffffff', border: '1px solid var(--border-color)', borderRadius: 'var(--radius-md)', padding: '1.5rem', boxShadow: '0 20px 40px rgba(0,0,0,0.15)', zIndex: 2000 }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '1rem' }}>
            <div>
              <h3 style={{ fontSize: '1.25rem', margin: 0 }}>{selectedPlant.common_name || selectedPlant.scientific_name}</h3>
              <div style={{ fontStyle: 'italic', color: 'var(--text-secondary)', fontSize: '0.85rem' }}>{selectedPlant.scientific_name}</div>
            </div>
            <button onClick={() => setSelectedPlant(null)} style={{ background: 'none', border: 'none', fontSize: '1.25rem', cursor: 'pointer', color: 'var(--text-muted)' }}>&times;</button>
          </div>

          {selectedPlant.photo_url && (
            <div style={{ height: '160px', borderRadius: 'var(--radius-sm)', overflow: 'hidden', marginBottom: '1rem' }}>
              <img src={selectedPlant.photo_url} alt={selectedPlant.common_name} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
            </div>
          )}

          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.75rem', fontSize: '0.85rem', marginBottom: '1rem' }}>
            <div><strong>Zone:</strong> {selectedPlant.zone_name || 'N/A'}</div>
            <div><strong>Family:</strong> {selectedPlant.family || 'N/A'}</div>
            <div><strong>Native Status:</strong> <span className={`badge badge-${selectedPlant.native_status}`}>{selectedPlant.native_status || 'unknown'}</span></div>
            <div><strong>Coordinates:</strong> {selectedPlant.latitude?.toFixed(4)}, {selectedPlant.longitude?.toFixed(4)}</div>
          </div>

          {selectedPlant.medicinal_uses && (
            <div style={{ fontSize: '0.82rem', background: '#ecfdf5', padding: '0.75rem', borderRadius: 'var(--radius-sm)', color: '#047857', marginBottom: '1rem' }}>
              <strong>💊 Medicinal Uses:</strong> {selectedPlant.medicinal_uses}
            </div>
          )}

          <div style={{ display: 'flex', gap: '0.5rem' }}>
            <button onClick={() => setSelectedPlant(null)} className="btn btn-secondary btn-sm" style={{ flex: 1 }}>Close</button>
          </div>
        </div>
      )}

      {/* Floating Action Button (+) */}
      <Link to="/capture" className="fab" title="Capture & Map Plant">
        <i className="fa-solid fa-plus"></i>
      </Link>

    </div>
  );
}
