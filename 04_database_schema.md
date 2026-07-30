# 04. Database Schema (PostgreSQL + PostGIS via Supabase)

## 1. ERD Overview (described)

```
institutions ──1:N── zones ──1:N── plant_records ──N:1── species
     │                                    │
     │                                    ├──1:N── plant_photos
     │                                    ├──1:N── verifications
     │                                    └──1:1── qr_codes
     │
     └──1:N── users (via user_institutions)

users ──1:N── plant_records (submitted_by)
users ──1:N── verifications (verified_by)
```

## 2. Table Definitions

### 2.1 `institutions`
Multi-tenant root — each college/university is isolated here.

| Column | Type | Constraints |
|---|---|---|
| id | uuid | PK, default `gen_random_uuid()` |
| name | text | not null |
| slug | text | unique, not null (used in URLs) |
| campus_center_lat | double precision | |
| campus_center_lng | double precision | |
| default_zoom | int | default 17 |
| created_at | timestamptz | default now() |

### 2.2 `users` (extends Supabase `auth.users`)

| Column | Type | Constraints |
|---|---|---|
| id | uuid | PK, references `auth.users(id)` |
| institution_id | uuid | FK → institutions.id |
| full_name | text | |
| role | text | check in `('contributor','verifier','admin')`, default `'contributor'` |
| avatar_url | text | |
| created_at | timestamptz | default now() |

### 2.3 `zones`
Campus sub-areas (for zone-wise analytics), stored as polygons.

| Column | Type | Constraints |
|---|---|---|
| id | uuid | PK |
| institution_id | uuid | FK → institutions.id, not null |
| name | text | not null (e.g., "Botanical Garden") |
| boundary | geometry(Polygon, 4326) | PostGIS polygon |
| color_hex | text | default `'#22c55e'` |
| created_at | timestamptz | default now() |

### 2.4 `species`
Master species dictionary — deduplicated so multiple plant instances share one species entry.

| Column | Type | Constraints |
|---|---|---|
| id | uuid | PK |
| scientific_name | text | not null, unique |
| common_name | text | |
| family | text | |
| native_status | text | check in `('native','introduced','invasive','unknown')`, default `'unknown'` |
| description | text | (from AI API metadata / Wikipedia) |
| reference_image_url | text | |
| ai_source | text | e.g. `'plantnet'` |
| created_at | timestamptz | default now() |

### 2.5 `plant_records`
The core table — one row per physical plant instance logged on campus.

| Column | Type | Constraints |
|---|---|---|
| id | uuid | PK, default `gen_random_uuid()` |
| institution_id | uuid | FK → institutions.id, not null |
| species_id | uuid | FK → species.id, nullable until identified |
| zone_id | uuid | FK → zones.id, nullable |
| location | geography(Point, 4326) | **PostGIS point** — indexed (GiST) |
| location_accuracy_m | real | GPS accuracy at capture time |
| status | text | check in `('pending_verification','verified','rejected')`, default `'pending_verification'` |
| ai_confidence | numeric(5,2) | top match confidence % |
| ai_candidates | jsonb | raw top-3 AI response, for audit/debug |
| notes | text | |
| submitted_by | uuid | FK → users.id |
| verified_by | uuid | FK → users.id, nullable |
| verified_at | timestamptz | nullable |
| created_at | timestamptz | default now() |
| updated_at | timestamptz | default now() |

**Indexes:**
```sql
CREATE INDEX idx_plant_records_location ON plant_records USING GIST (location);
CREATE INDEX idx_plant_records_institution ON plant_records (institution_id);
CREATE INDEX idx_plant_records_species ON plant_records (species_id);
CREATE INDEX idx_plant_records_status ON plant_records (status);
```

### 2.6 `plant_photos`
Supports multiple photos per plant record (e.g., leaf close-up, full plant, bark).

| Column | Type | Constraints |
|---|---|---|
| id | uuid | PK |
| plant_record_id | uuid | FK → plant_records.id, not null |
| storage_path | text | not null (Supabase Storage path) |
| is_primary | boolean | default false |
| created_at | timestamptz | default now() |

### 2.7 `verifications`
Audit trail of verification actions (supports "edit & approve," "reject with reason").

| Column | Type | Constraints |
|---|---|---|
| id | uuid | PK |
| plant_record_id | uuid | FK → plant_records.id, not null |
| action | text | check in `('approved','rejected','edited','merged')` |
| reason | text | |
| performed_by | uuid | FK → users.id |
| created_at | timestamptz | default now() |

### 2.8 `qr_codes`
One QR per verified plant record; kept separate so regenerating doesn't touch core record.

| Column | Type | Constraints |
|---|---|---|
| id | uuid | PK |
| plant_record_id | uuid | FK → plant_records.id, unique, not null |
| public_slug | text | unique, not null (short code used in URL) |
| scan_count | int | default 0 |
| created_at | timestamptz | default now() |

## 3. Sample DDL (core tables)

```sql
create extension if not exists postgis;
create extension if not exists "uuid-ossp";

create table institutions (
  id uuid primary key default gen_random_uuid(),
  name text not null,
  slug text unique not null,
  campus_center_lat double precision,
  campus_center_lng double precision,
  default_zoom int default 17,
  created_at timestamptz default now()
);

create table species (
  id uuid primary key default gen_random_uuid(),
  scientific_name text not null unique,
  common_name text,
  family text,
  native_status text check (native_status in ('native','introduced','invasive','unknown')) default 'unknown',
  description text,
  reference_image_url text,
  ai_source text,
  created_at timestamptz default now()
);

create table plant_records (
  id uuid primary key default gen_random_uuid(),
  institution_id uuid references institutions(id) not null,
  species_id uuid references species(id),
  zone_id uuid,
  location geography(Point, 4326),
  location_accuracy_m real,
  status text check (status in ('pending_verification','verified','rejected')) default 'pending_verification',
  ai_confidence numeric(5,2),
  ai_candidates jsonb,
  notes text,
  submitted_by uuid references auth.users(id),
  verified_by uuid references auth.users(id),
  verified_at timestamptz,
  created_at timestamptz default now(),
  updated_at timestamptz default now()
);

create index idx_plant_records_location on plant_records using gist (location);
```

## 4. Why this schema supports "live data" natively
- `plant_records` is the single table clients subscribe to via **Supabase Realtime**: `supabase.channel('plants').on('postgres_changes', {event: 'INSERT', schema: 'public', table: 'plant_records'}, callback)`.
- Because `species`, `zones`, and `plant_records` are normalized, updating a species name once (e.g., a verifier corrects it) instantly reflects everywhere that species is referenced — no data duplication to fix.
- `geography(Point,4326)` + GiST index means "plants within 50m of me" or "plants inside this zone polygon" are single fast SQL queries — critical for a smooth live map at scale.
