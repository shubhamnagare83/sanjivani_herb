import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../api';

export function Capture() {
  const [step, setStep] = useState(1);
  const [photoFile, setPhotoFile] = useState(null);
  const [previewUrl, setPreviewUrl] = useState('');
  const [gpsLat, setGpsLat] = useState(19.8762);
  const [gpsLng, setGpsLng] = useState(74.5981);
  const [gpsStatus, setGpsStatus] = useState('Acquiring GPS location...');

  const [zones, setZones] = useState([]);
  const [speciesList, setSpeciesList] = useState([]);

  // Form inputs
  const [selectedSpeciesId, setSelectedSpeciesId] = useState('');
  const [commonName, setCommonName] = useState('');
  const [scientificName, setScientificName] = useState('');
  const [family, setFamily] = useState('');
  const [selectedZoneId, setSelectedZoneId] = useState('');
  const [notes, setNotes] = useState('');

  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const navigate = useNavigate();

  // Load zones & species catalog
  useEffect(() => {
    Promise.all([api.getZones(), api.getSpecies()])
      .then(([zonesRes, speciesRes]) => {
        if (zonesRes.success) setZones(zonesRes.zones || []);
        if (speciesRes.success) setSpeciesList(speciesRes.species || []);
      }).catch(err => console.error(err));

    // Get Geolocation
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        pos => {
          setGpsLat(pos.coords.latitude);
          setGpsLng(pos.coords.longitude);
          setGpsStatus(`GPS Acquired: ${pos.coords.latitude.toFixed(4)}, ${pos.coords.longitude.toFixed(4)}`);
        },
        () => {
          setGpsStatus('Using Default Sanjivani Campus Location (19.8762, 74.5981)');
        }
      );
    }
  }, []);

  const handlePhotoChange = (e) => {
    if (e.target.files && e.target.files[0]) {
      const file = e.target.files[0];
      setPhotoFile(file);
      setPreviewUrl(URL.createObjectURL(file));
    }
  };

  const handleSpeciesSelect = (val) => {
    if (!val || val === 'custom') {
      setSelectedSpeciesId('');
      if (val === 'custom') {
        setCommonName('');
        setScientificName('');
        setFamily('');
      }
    } else {
      const sp = speciesList.find(s => s.id === val);
      if (sp) {
        setSelectedSpeciesId(sp.id);
        setCommonName(sp.common_name || '');
        setScientificName(sp.scientific_name || '');
        setFamily(sp.family || '');
      }
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!photoFile) {
      setError('Please select or capture a photo first.');
      setStep(1);
      return;
    }

    setSubmitting(true);
    setError('');

    try {
      const formData = new FormData();
      formData.append('photo', photoFile);
      formData.append('latitude', gpsLat);
      formData.append('longitude', gpsLng);
      if (selectedSpeciesId) formData.append('species_id', selectedSpeciesId);
      formData.append('common_name', commonName);
      formData.append('scientific_name', scientificName);
      formData.append('family', family);
      if (selectedZoneId) formData.append('zone_id', selectedZoneId);
      if (notes) formData.append('notes', notes);

      const res = await api.createPlant(formData);

      if (res.success) {
        alert('🌱 Observation successfully added to Live Map!');
        navigate('/dashboard');
      } else {
        setError(res.error || 'Failed to submit plant record');
      }
    } catch (err) {
      setError(err.message || 'Submission error');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="container" style={{ maxWidth: '650px', marginTop: '2rem' }}>
      
      {error && (
        <div style={{ background: '#fef2f2', border: '1px solid #fecaca', color: '#b91c1c', padding: '0.75rem 1rem', borderRadius: 'var(--radius-sm)', marginBottom: '1.25rem', fontSize: '0.9rem' }}>
          <i className="fa-solid fa-circle-exclamation"></i> {error}
        </div>
      )}

      {/* STEP 1: Photo Capture & Location */}
      {step === 1 && (
        <div className="glass-card" style={{ padding: '2rem' }}>
          <h2 style={{ marginBottom: '0.5rem' }}>
            <i className="fa-solid fa-camera"></i> Step 1: Photo & Location
          </h2>
          <p style={{ color: 'var(--text-secondary)', marginBottom: '1.5rem', fontSize: '0.95rem' }}>
            Take a photo or pick from gallery. GPS coordinates will be attached automatically.
          </p>

          <div style={{ border: '2px dashed var(--border-color)', borderRadius: 'var(--radius-md)', padding: '2.5rem', textAlign: 'center', marginBottom: '1.5rem', background: 'rgba(0,0,0,0.02)' }}>
            <input
              type="file"
              id="photoInput"
              accept="image/*"
              capture="environment"
              style={{ display: 'none' }}
              onChange={handlePhotoChange}
            />

            {previewUrl && (
              <div style={{ marginBottom: '1rem' }}>
                <img src={previewUrl} alt="Preview" style={{ maxWidth: '100%', maxHeight: '250px', borderRadius: 'var(--radius-sm)' }} />
              </div>
            )}

            <button
              type="button"
              onClick={() => document.getElementById('photoInput').click()}
              className="btn btn-primary btn-lg"
            >
              <i className="fa-solid fa-camera"></i> {previewUrl ? 'Change Photo' : 'Choose / Take Photo'}
            </button>
          </div>

          {/* GPS Status */}
          <div style={{ background: 'rgba(255,255,255,0.03)', border: '1px solid var(--border-color)', padding: '0.75rem 1rem', borderRadius: 'var(--radius-sm)', marginBottom: '1.5rem', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <div>
              <i className="fa-solid fa-location-dot" style={{ color: 'var(--accent-primary)', marginRight: '0.5rem' }}></i>
              <span>{gpsStatus}</span>
            </div>
          </div>

          <button
            type="button"
            className="btn btn-primary"
            style={{ width: '100%' }}
            disabled={!photoFile}
            onClick={() => setStep(2)}
          >
            Proceed to Fill Details <i className="fa-solid fa-arrow-right"></i>
          </button>
        </div>
      )}

      {/* STEP 2: Manual Details Entry */}
      {step === 2 && (
        <div className="glass-card" style={{ padding: '2rem' }}>
          <h2 style={{ marginBottom: '0.5rem' }}>
            <i className="fa-solid fa-pen-to-square"></i> Step 2: Plant Details (Manual Entry)
          </h2>
          <p style={{ color: 'var(--text-secondary)', marginBottom: '1.5rem', fontSize: '0.95rem' }}>
            Select an existing species from the catalog or enter custom plant details manually.
          </p>

          <form onSubmit={handleSubmit}>
            {/* Species Select */}
            <div className="form-group">
              <label className="form-label">Select Species from Database Catalog</label>
              <select
                className="form-control"
                value={selectedSpeciesId || (scientificName ? 'custom' : '')}
                onChange={e => handleSpeciesSelect(e.target.value)}
              >
                <option value="">-- Select Existing Species or Choose Custom --</option>
                {speciesList.map(sp => (
                  <option key={sp.id} value={sp.id}>
                    {sp.common_name ? `${sp.common_name} — ` : ''}{sp.scientific_name}
                  </option>
                ))}
                <option value="custom">➕ Enter Custom Species Manually</option>
              </select>
            </div>

            <div className="form-group">
              <label className="form-label">Common Name *</label>
              <input
                type="text"
                className="form-control"
                placeholder="e.g. Neem Tree, Banyan Tree"
                value={commonName}
                onChange={e => setCommonName(e.target.value)}
                required
              />
            </div>

            <div className="form-group">
              <label className="form-label">Scientific Name *</label>
              <input
                type="text"
                className="form-control"
                placeholder="e.g. Azadirachta indica"
                value={scientificName}
                onChange={e => setScientificName(e.target.value)}
                required
              />
            </div>

            <div className="form-group">
              <label className="form-label">Family (Optional)</label>
              <input
                type="text"
                className="form-control"
                placeholder="e.g. Meliaceae, Moraceae"
                value={family}
                onChange={e => setFamily(e.target.value)}
              />
            </div>

            <div className="form-group">
              <label className="form-label">Campus Zone</label>
              <select
                className="form-control"
                value={selectedZoneId}
                onChange={e => setSelectedZoneId(e.target.value)}
              >
                <option value="">Auto-detect by GPS Proximity</option>
                {zones.map(z => (
                  <option key={z.id} value={z.id}>{z.name}</option>
                ))}
              </select>
            </div>

            <div className="form-group">
              <label className="form-label">Observation Notes (Optional)</label>
              <textarea
                className="form-control"
                rows="3"
                placeholder="e.g. Healthy condition, near science department entrance"
                value={notes}
                onChange={e => setNotes(e.target.value)}
              ></textarea>
            </div>

            <div style={{ display: 'flex', gap: '1rem', marginTop: '1.5rem' }}>
              <button type="button" onClick={() => setStep(1)} className="btn btn-secondary">
                <i className="fa-solid fa-arrow-left"></i> Back
              </button>
              <button type="submit" className="btn btn-primary" style={{ flex: 1 }} disabled={submitting}>
                {submitting ? <i className="fa-solid fa-spinner fa-spin"></i> : <i className="fa-solid fa-paper-plane"></i>} Submit to Live Map
              </button>
            </div>
          </form>
        </div>
      )}

    </div>
  );
}
