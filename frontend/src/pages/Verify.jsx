import React, { useEffect, useState } from 'react';
import { api } from '../api';

export function Verify() {
  const [queue, setQueue] = useState([]);
  const [loading, setLoading] = useState(true);
  const [actionMessage, setActionMessage] = useState('');

  useEffect(() => {
    fetchQueue();
  }, []);

  const fetchQueue = () => {
    setLoading(true);
    api.getVerifyQueue()
      .then(res => {
        if (res.success) setQueue(res.data || []);
      })
      .catch(err => console.error(err))
      .finally(() => setLoading(false));
  };

  const handleAction = async (plantRecordId, action) => {
    try {
      const res = await api.verifyAction({
        plant_record_id: plantRecordId,
        action: action
      });

      if (res.success) {
        setActionMessage(`Observation ${action} successfully.`);
        setTimeout(() => setActionMessage(''), 3000);
        fetchQueue();
      } else {
        alert('Action error: ' + (res.error || 'Failed action'));
      }
    } catch (err) {
      alert('Error: ' + err.message);
    }
  };

  return (
    <div className="container" style={{ maxWidth: '900px', paddingBottom: '4rem' }}>
      
      <div style={{ marginBottom: '1.75rem' }}>
        <h1 style={{ fontSize: '2.2rem', margin: 0 }}>
          <i className="fa-solid fa-circle-check" style={{ color: 'var(--accent-primary)' }}></i> Verification Queue
        </h1>
        <p style={{ color: 'var(--text-secondary)', fontSize: '0.95rem' }}>
          Review pending plant observations submitted by campus contributors
        </p>
      </div>

      {actionMessage && (
        <div style={{ background: '#ecfdf5', border: '1px solid #a7f3d0', color: '#047857', padding: '0.75rem 1rem', borderRadius: 'var(--radius-sm)', marginBottom: '1.5rem', fontWeight: 600 }}>
          <i className="fa-solid fa-circle-check"></i> {actionMessage}
        </div>
      )}

      {loading ? (
        <div style={{ textAlign: 'center', padding: '4rem 0', color: 'var(--text-muted)' }}>
          <i className="fa-solid fa-circle-notch fa-spin" style={{ fontSize: '2.5rem', color: 'var(--accent-primary)', marginBottom: '1rem' }}></i>
          <div>Loading Pending Observations...</div>
        </div>
      ) : queue.length === 0 ? (
        <div className="glass-card" style={{ textAlign: 'center', padding: '3.5rem', color: 'var(--text-muted)' }}>
          <i className="fa-solid fa-circle-check" style={{ fontSize: '3.5rem', color: 'var(--accent-primary)', marginBottom: '1rem' }}></i>
          <h2>Queue Is Empty</h2>
          <p style={{ marginTop: '0.5rem' }}>All submitted plant observations have been reviewed and verified!</p>
        </div>
      ) : (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
          {queue.map(item => (
            <div key={item.id} className="glass-card" style={{ padding: '1.5rem', display: 'flex', gap: '1.5rem', flexWrap: 'wrap', alignItems: 'center' }}>
              <div style={{ width: '120px', height: '120px', background: '#f1f5f9', borderRadius: 'var(--radius-sm)', overflow: 'hidden', flexShrink: 0, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                {item.photo_url ? (
                  <img src={item.photo_url} alt={item.common_name} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                ) : (
                  <span style={{ fontSize: '2.5rem', color: 'var(--accent-primary)' }}>🌿</span>
                )}
              </div>

              <div style={{ flex: 1, minWidth: '240px' }}>
                <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center', marginBottom: '0.35rem' }}>
                  <h3 style={{ fontSize: '1.2rem', margin: 0 }}>{item.common_name || item.scientific_name}</h3>
                  <span className="badge badge-pending">Pending Review</span>
                </div>
                <div style={{ fontStyle: 'italic', color: 'var(--text-secondary)', fontSize: '0.88rem', marginBottom: '0.5rem' }}>
                  {item.scientific_name} {item.family ? `• ${item.family}` : ''}
                </div>

                <div style={{ fontSize: '0.85rem', color: 'var(--text-muted)', display: 'flex', gap: '1rem', flexWrap: 'wrap', marginBottom: '0.5rem' }}>
                  <span>📍 Zone: {item.zone_name || 'Campus'}</span>
                  <span>👤 Submitted by: {item.submitted_by_name || 'Student'}</span>
                </div>

                {item.notes && (
                  <div style={{ fontSize: '0.82rem', background: '#f8fafc', padding: '0.5rem 0.75rem', borderRadius: 'var(--radius-sm)', color: 'var(--text-secondary)' }}>
                    <strong>Notes:</strong> {item.notes}
                  </div>
                )}
              </div>

              <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem', minWidth: '130px' }}>
                <button
                  onClick={() => handleAction(item.id, 'approved')}
                  className="btn btn-primary btn-sm"
                  style={{ width: '100%' }}
                >
                  <i className="fa-solid fa-check"></i> Approve
                </button>
                <button
                  onClick={() => handleAction(item.id, 'rejected')}
                  className="btn btn-danger btn-sm"
                  style={{ width: '100%' }}
                >
                  <i className="fa-solid fa-xmark"></i> Reject
                </button>
              </div>
            </div>
          ))}
        </div>
      )}

    </div>
  );
}
