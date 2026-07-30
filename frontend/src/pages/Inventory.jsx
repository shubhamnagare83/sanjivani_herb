import React, { useEffect, useState } from 'react';
import { api } from '../api';

export function Inventory() {
  const [species, setSpecies] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [nativeStatus, setNativeStatus] = useState('');

  useEffect(() => {
    fetchSpecies();
  }, [search, nativeStatus]);

  const fetchSpecies = () => {
    setLoading(true);
    const params = {};
    if (search) params.search = search;
    if (nativeStatus) params.native_status = nativeStatus;

    api.getSpecies(params)
      .then(res => {
        if (res.success) setSpecies(res.species || []);
      })
      .catch(err => console.error(err))
      .finally(() => setLoading(false));
  };

  return (
    <div className="container" style={{ paddingBottom: '4rem' }}>
      
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem', flexWrap: 'wrap', gap: '1rem' }}>
        <div>
          <h1 style={{ fontSize: '2.2rem', margin: 0 }}>
            <i className="fa-solid fa-book-bookmark" style={{ color: 'var(--accent-primary)' }}></i> Species Catalog
          </h1>
          <p style={{ color: 'var(--text-secondary)', fontSize: '0.95rem' }}>
            Master botanical dictionary of mapped trees, shrubs, and medicinal herbs
          </p>
        </div>
      </div>

      {/* Filter Bar */}
      <div className="glass-card" style={{ padding: '1rem 1.5rem', marginBottom: '2rem', display: 'flex', gap: '1rem', flexWrap: 'wrap', alignItems: 'center' }}>
        <div style={{ flex: 1, minWidth: '220px' }}>
          <input
            type="text"
            className="form-control"
            placeholder="🔍 Search species by name or family..."
            value={search}
            onChange={e => setSearch(e.target.value)}
          />
        </div>

        <select
          className="form-control"
          style={{ width: 'auto', minWidth: '170px' }}
          value={nativeStatus}
          onChange={e => setNativeStatus(e.target.value)}
        >
          <option value="">All Native Statuses</option>
          <option value="native">Native Only</option>
          <option value="introduced">Introduced Only</option>
          <option value="invasive">Invasive Only</option>
        </select>
      </div>

      {/* Species Grid */}
      {loading ? (
        <div style={{ textAlign: 'center', padding: '4rem 0', color: 'var(--text-muted)' }}>
          <i className="fa-solid fa-circle-notch fa-spin" style={{ fontSize: '2.5rem', color: 'var(--accent-primary)', marginBottom: '1rem' }}></i>
          <div>Loading Species Catalog...</div>
        </div>
      ) : species.length === 0 ? (
        <div className="glass-card" style={{ textAlign: 'center', padding: '3rem', color: 'var(--text-muted)' }}>
          <i className="fa-solid fa-seedling" style={{ fontSize: '3rem', marginBottom: '1rem' }}></i>
          <h3>No Species Found</h3>
          <p>Try adjusting your search query or filters.</p>
        </div>
      ) : (
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))', gap: '1.5rem' }}>
          {species.map(sp => (
            <div key={sp.id} className="glass-card" style={{ padding: '1.5rem', display: 'flex', flexDirection: 'column' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '0.75rem' }}>
                <span className={`badge badge-${sp.native_status || 'native'}`}>
                  {sp.native_status || 'native'}
                </span>
                <span style={{ fontSize: '0.8rem', fontWeight: 600, color: 'var(--accent-primary)', background: '#ecfdf5', padding: '0.25rem 0.6rem', borderRadius: 'var(--radius-full)' }}>
                  {sp.plant_count || 0} Records
                </span>
              </div>

              <h3 style={{ fontSize: '1.2rem', marginBottom: '0.2rem' }}>
                {sp.common_name || sp.scientific_name}
              </h3>
              <div style={{ fontStyle: 'italic', color: 'var(--text-secondary)', fontSize: '0.88rem', marginBottom: '0.75rem' }}>
                {sp.scientific_name} {sp.family ? `• ${sp.family}` : ''}
              </div>

              {sp.description && (
                <p style={{ fontSize: '0.83rem', color: 'var(--text-muted)', lineHeight: 1.5, marginBottom: '1rem' }}>
                  {sp.description.length > 120 ? sp.description.substring(0, 120) + '...' : sp.description}
                </p>
              )}

              {sp.medicinal_uses && (
                <div style={{ marginTop: 'auto', paddingTop: '0.75rem', borderTop: '1px solid var(--border-color)', fontSize: '0.8rem', color: '#047857' }}>
                  <strong>💊 Medicinal:</strong> {sp.medicinal_uses.substring(0, 90)}...
                </div>
              )}
            </div>
          ))}
        </div>
      )}

    </div>
  );
}
