# 08. API Contract
*Use this to split frontend/backend work in parallel — frontend can build against this contract while backend implements it.*

Base: Supabase auto-generates REST (via PostgREST) + Realtime for all tables. Below are the **custom Edge Functions** you need to write, plus the key auto-generated table endpoints you'll consume directly.

## 1. Custom Edge Functions

### `POST /functions/v1/identify-plant`
Identify species from an uploaded photo.

**Request:**
```json
{
  "storage_path": "plant-photos/abc123.jpg",
  "organ_hint": "leaf",
  "lat": 19.8762,
  "lng": 74.5981
}
```

**Response `200`:**
```json
{
  "candidates": [
    { "species_id": "uuid-or-null", "scientific_name": "Ficus benghalensis", "common_name": "Banyan Tree", "family": "Moraceae", "confidence": 0.91, "reference_image": "https://..." },
    { "species_id": null, "scientific_name": "Ficus religiosa", "common_name": "Peepal", "family": "Moraceae", "confidence": 0.06, "reference_image": "https://..." }
  ],
  "suggested_zone_id": "uuid-or-null"
}
```

**Errors:** `400` invalid image, `429` rate-limited, `502` AI provider unavailable (client should fall back to manual entry flow).

---

### `POST /functions/v1/submit-plant-record`
Finalizes a submission after user confirms species.

**Request:**
```json
{
  "species_id": "uuid",
  "storage_path": "plant-photos/abc123.jpg",
  "lat": 19.8762,
  "lng": 74.5981,
  "location_accuracy_m": 8.2,
  "notes": "Near main gate",
  "ai_candidates": [ /* raw candidates array for audit */ ]
}
```

**Response `201`:**
```json
{ "plant_record_id": "uuid", "status": "pending_verification" }
```

---

### `POST /functions/v1/generate-qr`
Generates/regenerates QR for a verified record.

**Request:** `{ "plant_record_id": "uuid" }`
**Response `200`:** `{ "public_slug": "xk3f9a", "qr_png_url": "https://..." }`

---

### `GET /functions/v1/analytics-summary?institution_id=uuid`
Aggregated dashboard stats (can also be a Postgres view + direct REST call instead of a function).

**Response `200`:**
```json
{
  "total_species": 142,
  "total_plants": 890,
  "verified_pct": 78.4,
  "native_vs_invasive": { "native": 620, "introduced": 210, "invasive": 40, "unknown": 20 },
  "top_zones": [ { "zone_id": "uuid", "name": "Botanical Garden", "count": 210 } ],
  "submissions_last_30_days": 134
}
```

## 2. Direct Table Access (via Supabase client, RLS-protected)

| Action | Table | Example |
|---|---|---|
| List verified plants for map | `plant_records` (join `species`) | `supabase.from('plant_records').select('*, species(*)').eq('status','verified')` |
| List pending for verifier queue | `plant_records` | `.eq('status','pending_verification')` |
| Update record status (verify/reject) | `plant_records` | `.update({status:'verified', verified_by, verified_at})` |
| Insert verification log | `verifications` | `.insert({...})` |
| List zones | `zones` | `.select('*')` |
| Search inventory | `species` | `.select('*').ilike('common_name', '%query%')` |

## 3. Public (no-auth) Endpoints

### `GET /plant/:slug` (frontend route, not API — resolves via)
```sql
select pr.*, s.*, i.name as institution_name
from plant_records pr
join species s on s.id = pr.species_id
join qr_codes q on q.plant_record_id = pr.id
join institutions i on i.id = pr.institution_id
where q.public_slug = :slug and pr.status = 'verified';
```

## 4. Realtime Channel Contract

| Channel | Event | Payload | Consumers |
|---|---|---|---|
| `public:plant_records` | INSERT/UPDATE/DELETE | full row | Live Map, Analytics Dashboard |
| `public:verifications` | INSERT | full row | Verifier queue (multi-verifier awareness) |
