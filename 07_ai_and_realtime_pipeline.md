# 07. AI Identification Pipeline & Live/Real-Time Data Architecture
*(This is the document that makes your submission stand out — most teams will hardcode a plant list or fake the AI; this gives you a real, working, explainable pipeline.)*

## 1. AI Plant Identification Pipeline

### 1.1 Recommended Primary API: Pl@ntNet
- Purpose-built plant identification API, trained on millions of botanist-verified images, free tier available for research/education use.
- Accepts an image + optional "organ" hint (leaf, flower, fruit, bark) to boost accuracy — expose this as an optional toggle in your capture UI ("What part are you photographing?").
- Returns ranked species candidates with confidence scores and reference images.

### 1.2 Fallback / Secondary: Google Cloud Vision API (label + web detection)
- Use only if Pl@ntNet is unavailable/rate-limited, or as a cross-check for common-name matching.
- Generic — not plant-specialized — so treat its output as lower-confidence.

### 1.3 Pipeline Steps
```
1. Client resizes image (max 1600px) before upload — faster upload, cheaper storage, still accurate enough for ID.
2. Client uploads to Supabase Storage → gets storage_path.
3. Client calls Edge Function `identify-plant(storage_path, organ_hint, lat, lng)`.
4. Edge Function fetches image bytes from Storage.
5. Edge Function calls Pl@ntNet API (server-side, API key hidden).
6. Response parsed → top 3 candidates: { scientific_name, common_name, family, score, reference_image }.
7. Edge Function checks `species` table:
      - exact scientific_name match found → reuse species_id
      - not found → insert new species row (status: needs family/native_status enrichment)
8. Return candidates + species_id(s) to client for user confirmation.
9. On submission, ai_candidates JSON is stored for audit + future model improvement.
```

### 1.4 Accuracy-Boosting Techniques ("10x more accurate")
- **Multi-organ capture**: prompt user for 2 photos (leaf + flower/fruit if available) — Pl@ntNet supports multi-image queries and accuracy improves significantly with more than one organ.
- **Confidence threshold gating**: if top match < 40% confidence, force manual entry + flag for verifier rather than auto-accepting a weak guess.
- **Human-in-the-loop verification**: AI proposes, verifier (faculty) disposes — this is what actually gets you to near-100% published accuracy, since raw AI models rarely exceed ~85-90% on their own.
- **Location-based priors (stretch goal)**: once you have enough data, bias suggestions toward species already confirmed in that zone/region — most campus plants repeat across similar zones.
- **Feedback loop**: every verifier correction (`verifications` table, action='edited') is a labeled training example — export periodically if you want to fine-tune a custom model later (v2 roadmap item, good to mention to judges).

## 2. Live/Real-Time Data Architecture
*(This satisfies your explicit requirement: "I want to work on the live data.")*

### 2.1 Core Mechanism: Postgres Logical Replication → Supabase Realtime
Supabase listens to the Postgres **write-ahead log (WAL)** for changes on subscribed tables and pushes them over WebSocket to any connected client — no polling, no custom socket server.

### 2.2 Client Subscription Pattern
```javascript
// Subscribe once when the map component mounts
const channel = supabase
  .channel('public:plant_records')
  .on(
    'postgres_changes',
    { event: '*', schema: 'public', table: 'plant_records' },
    (payload) => {
      if (payload.eventType === 'INSERT') addPinToMap(payload.new);
      if (payload.eventType === 'UPDATE') updatePinOnMap(payload.new);
      if (payload.eventType === 'DELETE') removePinFromMap(payload.old);
    }
  )
  .subscribe();

// Clean up on unmount
return () => supabase.removeChannel(channel);
```

### 2.3 Scoping Realtime to an Institution (avoid noisy cross-tenant updates)
Use Supabase Realtime's **Broadcast + RLS-aware channels**, or filter client-side/server-side by `institution_id` in the subscription filter:
```javascript
.on('postgres_changes',
  { event: '*', schema: 'public', table: 'plant_records', filter: `institution_id=eq.${institutionId}` },
  handleChange
)
```

### 2.4 Optimistic UI for Snappy Feel
On submit, immediately render the pin locally (optimistic update) before the server confirms — then reconcile with the real Realtime event when it arrives. Makes the app feel instant even on slower connections.

### 2.5 Offline-First Capture (campus wifi dead zones)
- Service Worker + IndexedDB queue: if offline, save the submission (photo + form data) locally.
- Background Sync API (or manual "retry" button) flushes the queue when connectivity returns.
- This is what lets you say the system "works on live data" even in patchy real-world campus conditions — a genuinely differentiating point for judges.

### 2.6 Live Map Performance at Scale
- Use `leaflet.markercluster` so thousands of pins don't tank frame rate — clusters expand as user zooms in.
- Debounce/batch rapid-fire Realtime events (e.g., during a bulk CSV import) so the map doesn't try to re-render 500 times in one second — batch into a single re-render every ~300ms.

### 2.7 Live Analytics (bonus "wow" factor)
Because `plant_records` changes are streamed, your analytics dashboard KPI cards (total species, total plants, etc.) can also subscribe to the same channel and increment live, without a page refresh — reinforcing the "living digital inventory" narrative from the problem statement.
