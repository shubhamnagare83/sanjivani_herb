# 05. Data Flow Diagrams (DFD)

## 1. DFD Level 0 (Context Diagram)

```
                    ┌───────────────────────────────┐
   Contributor ───▶ │                                 │ ───▶ Live Map / Public
   (photo + GPS)    │   CAMPUS PLANT DIVERSITY        │      (all viewers)
                     │   MAPPER SYSTEM                 │
   Verifier ───────▶ │                                 │ ───▶ QR Landing Pages
   (approve/edit)    │                                 │      (public visitors)
                     │                                 │
   Admin ───────────▶│                                 │ ───▶ Analytics Reports
   (zones, config)   └───────────────┬─────────────────┘      (PDF/CSV)
                                      │
                                      ▼
                        External AI Plant-ID API
                          (Pl@ntNet / Vision API)
```

## 2. DFD Level 1 — Major Processes

```
1.0 Capture & Submit
    Input: Photo, GPS coords, User session
    Output: plant_records row (status=pending), plant_photos row
    Store: D1 plant_records, D2 plant_photos

2.0 AI Identification
    Input: Photo (from 1.0)
    Output: species candidates (JSON: name, confidence)
    External: Pl@ntNet API
    Store: D3 species (lookup/create)

3.0 Verification
    Input: pending plant_records, verifier action
    Output: updated status, verifications log entry
    Store: D1 plant_records, D4 verifications

4.0 Live Map Sync
    Input: any INSERT/UPDATE on D1 plant_records
    Output: real-time event pushed to all subscribed clients
    Mechanism: Postgres logical replication → Supabase Realtime → WebSocket

5.0 QR Generation
    Input: verified plant_record
    Output: qr_codes row + downloadable QR image
    Store: D5 qr_codes

6.0 Analytics Aggregation
    Input: D1 plant_records, D3 species, D6 zones
    Output: dashboard KPIs, charts, exportable report
```

## 3. DFD Level 2 — Detail of Process 1.0 + 2.0 (Capture → AI → Save, the critical path)

```
[User] 
   │ (1) opens camera, takes photo
   ▼
[Client App]
   │ (2) requests GPS via navigator.geolocation
   │ (3) uploads image → Supabase Storage (signed URL)
   ▼
[Storage: plant-photos bucket] 
   │ (4) returns storage_path
   ▼
[Client App]
   │ (5) calls Edge Function `identify-plant` with { storage_path, lat, lng }
   ▼
[Edge Function: identify-plant]
   │ (6) fetches image from Storage
   │ (7) calls Pl@ntNet API with image
   ▼
[Pl@ntNet API]
   │ (8) returns top-3 { scientific_name, common_name, score }
   ▼
[Edge Function]
   │ (9) checks D3 species table — species exists? reuse id : create new species row
   │ (10) returns candidates + species_id(s) to client
   ▼
[Client App]
   │ (11) user confirms species choice
   │ (12) INSERT into plant_records (status=pending_verification, ai_candidates=jsonb)
   ▼
[Supabase Postgres: plant_records]
   │ (13) row committed → WAL (write-ahead log) change captured
   ▼
[Supabase Realtime Engine]
   │ (14) broadcasts change event on 'plant_records' channel
   ▼
[All subscribed clients' Live Map components]
   │ (15) receive event → add/update pin on map instantly
   ▼
[End users see the new plant live]
```

## 4. Sequence Diagram — Verifier Approval Flow

```
Verifier UI          Supabase API              Postgres              Realtime
    │  GET pending records   │                        │                    │
    │───────────────────────▶│                        │                    │
    │◀───────────────────────│  (RLS-filtered rows)   │                    │
    │                        │                        │                    │
    │  PATCH status=verified │                        │                    │
    │───────────────────────▶│───────────────────────▶│                    │
    │                        │  INSERT verifications   │                    │
    │                        │───────────────────────▶│                    │
    │                        │                        │  change event      │
    │                        │                        │───────────────────▶│
    │                        │                        │                    │ push to all clients
    │◀── success ────────────│                        │                    │────────────▶ Map pin turns green (verified)
```

## 5. Sequence Diagram — QR Scan (Public, No Login)

```
Visitor phone        CDN/Vercel            Supabase (anon key, RLS read-only)
     │  scan QR → GET /plant/{slug}                │
     │────────────────────────▶│                    │
     │                         │  SELECT plant_records JOIN species WHERE qr slug = ...
     │                         │──────────────────▶│
     │                         │◀──────────────────│
     │◀────────────────────────│  render public page│
     │                         │  UPDATE qr_codes SET scan_count += 1 (fire-and-forget)
     │                         │──────────────────▶│
```

## 6. Data Volume & Retention Notes
- Photos: store at max ~1600px width (client-side resize before upload) to keep storage costs and load times low.
- `ai_candidates` JSONB kept for audit/model-improvement purposes — don't discard raw AI response.
- Soft-delete pattern recommended (`status='rejected'` instead of hard delete) to preserve audit trail for `verifications`.
