import React, { useEffect, useState } from 'react';
import { api } from '../api';

export function Analytics() {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.getAnalytics()
      .then(res => {
        if (res.success) setData(res);
      })
      .catch(err => console.error(err))
      .finally(() => setLoading(false));
  }, []);

  if (loading) {
    return (
      <div className="container" style={{ textAlign: 'center', padding: '5rem 0', color: 'var(--text-muted)' }}>
        <i className="fa-solid fa-circle-notch fa-spin" style={{ fontSize: '3rem', color: 'var(--accent-primary)', marginBottom: '1rem' }}></i>
        <div>Loading Analytics Dashboard...</div>
      </div>
    );
  }

  if (!data) {
    return (
      <div className="container" style={{ textAlign: 'center', padding: '3rem' }}>
        <h2>Failed to load analytics data</h2>
      </div>
    );
  }

  return (
    <div className="container" style={{ paddingBottom: '4rem' }}>
      
      <div style={{ marginBottom: '1.75rem' }}>
        <h1 style={{ fontSize: '2.2rem', margin: 0 }}>
          <i className="fa-solid fa-chart-pie" style={{ color: 'var(--accent-primary)' }}></i> Biodiversity Analytics
        </h1>
        <p style={{ color: 'var(--text-secondary)', fontSize: '0.95rem' }}>
          Verification accuracy, campus zone distribution, and native species composition
        </p>
      </div>

      {/* KPI Cards */}
      <div className="stats-grid">
        <div className="kpi-card glass-card">
          <div>
            <div className="kpi-title">Total Mapped</div>
            <div className="kpi-value">{data.total_plants}</div>
          </div>
          <div className="kpi-icon"><i className="fa-solid fa-tree"></i></div>
        </div>

        <div className="kpi-card glass-card">
          <div>
            <div className="kpi-title">Verification Rate</div>
            <div className="kpi-value">{data.verified_pct}%</div>
          </div>
          <div className="kpi-icon" style={{ background: '#ecfdf5', color: '#059669', borderColor: '#a7f3d0' }}>
            <i className="fa-solid fa-circle-check"></i>
          </div>
        </div>

        <div className="kpi-card glass-card">
          <div>
            <div className="kpi-title">Species Count</div>
            <div className="kpi-value">{data.total_species}</div>
          </div>
          <div className="kpi-icon" style={{ background: '#eff6ff', color: '#2563eb', borderColor: '#bfdbfe' }}>
            <i className="fa-solid fa-seedling"></i>
          </div>
        </div>

        <div className="kpi-card glass-card">
          <div>
            <div className="kpi-title">Active Contributors</div>
            <div className="kpi-value">{data.total_contributors}</div>
          </div>
          <div className="kpi-icon" style={{ background: '#fdf2f8', color: '#db2777', borderColor: '#fbcfe8' }}>
            <i className="fa-solid fa-users"></i>
          </div>
        </div>
      </div>

      {/* Distribution Cards */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))', gap: '1.5rem', marginBottom: '2rem' }}>
        
        {/* Verification Breakdown */}
        <div className="glass-card" style={{ padding: '1.5rem' }}>
          <h3 style={{ fontSize: '1.15rem', marginBottom: '1.25rem' }}>
            <i className="fa-solid fa-list-check"></i> Record Verification Status
          </h3>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '0.85rem' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <span className="badge badge-verified">Verified</span>
              <strong style={{ fontSize: '1.1rem' }}>{data.status_breakdown.verified || 0}</strong>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <span className="badge badge-pending">Pending Review</span>
              <strong style={{ fontSize: '1.1rem' }}>{data.status_breakdown.pending_verification || 0}</strong>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <span className="badge badge-rejected">Rejected</span>
              <strong style={{ fontSize: '1.1rem' }}>{data.status_breakdown.rejected || 0}</strong>
            </div>
          </div>
        </div>

        {/* Native Status Breakdown */}
        <div className="glass-card" style={{ padding: '1.5rem' }}>
          <h3 style={{ fontSize: '1.15rem', marginBottom: '1.25rem' }}>
            <i className="fa-solid fa-leaf"></i> Native Species Composition
          </h3>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '0.85rem' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <span className="badge badge-native">Native</span>
              <strong style={{ fontSize: '1.1rem' }}>{data.native_breakdown.native || 0}</strong>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <span className="badge badge-invasive">Invasive</span>
              <strong style={{ fontSize: '1.1rem' }}>{data.native_breakdown.invasive || 0}</strong>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <span className="badge badge-verified" style={{ background: '#f1f5f9', color: '#475569', borderColor: '#cbd5e1' }}>Introduced / Unknown</span>
              <strong style={{ fontSize: '1.1rem' }}>{(data.native_breakdown.introduced || 0) + (data.native_breakdown.unknown || 0)}</strong>
            </div>
          </div>
        </div>

      </div>

      {/* Top Species & Top Contributors */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(340px, 1fr))', gap: '1.5rem' }}>
        
        {/* Top Species */}
        <div className="glass-card" style={{ padding: '1.5rem' }}>
          <h3 style={{ fontSize: '1.15rem', marginBottom: '1.25rem' }}>
            <i className="fa-solid fa-ranking-star"></i> Most Observed Species
          </h3>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
            {data.top_species.map((sp, idx) => (
              <div key={idx} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '0.5rem 0', borderBottom: '1px solid var(--border-color)' }}>
                <div>
                  <div style={{ fontWeight: 600 }}>{sp.common_name || sp.scientific_name}</div>
                  <div style={{ fontStyle: 'italic', fontSize: '0.8rem', color: 'var(--text-muted)' }}>{sp.scientific_name}</div>
                </div>
                <span className="badge badge-verified">{sp.cnt} plants</span>
              </div>
            ))}
          </div>
        </div>

        {/* Top Contributors */}
        <div className="glass-card" style={{ padding: '1.5rem' }}>
          <h3 style={{ fontSize: '1.15rem', marginBottom: '1.25rem' }}>
            <i className="fa-solid fa-award"></i> Top Campus Contributors
          </h3>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
            {data.top_contributors.map((c, idx) => (
              <div key={idx} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '0.5rem 0', borderBottom: '1px solid var(--border-color)' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
                  <span style={{ fontWeight: 800, color: 'var(--accent-primary)', width: '20px' }}>#{idx + 1}</span>
                  <div style={{ fontWeight: 600 }}>{c.full_name}</div>
                </div>
                <span className="badge badge-native">{c.cnt} submissions</span>
              </div>
            ))}
          </div>
        </div>

      </div>

    </div>
  );
}
