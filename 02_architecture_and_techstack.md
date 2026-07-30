# 02. System Architecture & Tech Stack

## 1. Architecture Style
**Client → BaaS (Backend-as-a-Service) → AI microservice → Live push to all clients.**

Using a BaaS (Supabase or Firebase) instead of hand-rolling a backend is the single biggest "10x faster" decision you can make — it gives you Auth, Database, File Storage, Row-Level Security, and **built-in real-time subscriptions** out of the box, so you don't build WebSocket infrastructure yourself.

## 2. High-Level Architecture Diagram (described)

```
┌────────────────────┐        ┌──────────────────────┐
│  Client Apps (PWA)  │        │   Public QR Landing   │
│  - Capture Screen   │        │   (read-only page)    │
│  - Live Map View    │        └──────────┬────────────┘
│  - Inventory List   │                   │
│  - Admin Dashboard  │                   │
└─────────┬───────────┘                   │
          │  HTTPS / WSS                  │ HTTPS
          ▼                                ▼
┌─────────────────────────────────────────────────────┐
│                Supabase (BaaS layer)                 │
│  ┌───────────┐ ┌───────────┐ ┌───────────────────┐  │
│  │   Auth     │ │  Postgres  │ │  Storage (images)  │  │
│  │  (JWT)     │ │  + PostGIS │ │                     │  │
│  └───────────┘ └─────┬─────┘ └───────────────────┘  │
│                       │ Realtime (logical replication)│
│                       ▼                                │
│              Realtime WebSocket Channel                │
└──────────────────────┬────────────────────────────────┘
                        │ pushes INSERT/UPDATE events
                        ▼
              All connected clients' maps
              update live automatically

          ┌───────────────────────────────┐
          │   AI Plant ID Microservice      │
          │  (Edge Function / Cloud Run)    │
          │  - Receives image               │
          │  - Calls Plant ID API (Pl@ntNet │
          │    or Google Vision + custom)   │
          │  - Returns species candidates   │
          └───────────────────────────────┘
```

## 3. Recommended Tech Stack (optimized for hackathon speed + real accuracy)

| Layer | Recommended | Why |
|---|---|---|
| **Frontend** | **Next.js (React) + TypeScript**, deployed as installable **PWA** | One codebase for web + "mobile app" via PWA install prompt; no app store delay |
| **UI/Map** | **Tailwind CSS**, **Leaflet.js** (or Mapbox GL JS if you want 3D/heatmaps) + **Leaflet.markercluster** | Leaflet is free, fast, and handles clustering for thousands of pins |
| **Backend/DB** | **Supabase** (Postgres + PostGIS + Auth + Storage + Realtime) | Gives you live data, geospatial queries, auth, and file storage without writing backend code |
| **AI Plant ID** | **Pl@ntNet API** (free tier, purpose-built for plant ID, very accurate) — fallback to **Google Cloud Vision API (label detection)** | Pl@ntNet is trained specifically on plants — far higher accuracy than generic vision models |
| **QR Codes** | `qrcode` npm library (client-side generation) or `qrcode.react` | Instant, no external service needed |
| **Hosting** | **Vercel** (frontend) + **Supabase Cloud** (backend) | Both have generous free tiers, deploy in minutes |
| **Analytics charts** | **Recharts** | Lightweight, works great in React |
| **PDF/CSV export** | `jsPDF` / `papaparse` | Client-side report generation, no server needed |
| **Offline support** | Service Worker (Next.js PWA plugin) + IndexedDB queue | Capture works even with no signal, syncs when back online |

> **Why Supabase over Firebase here specifically:** you need **geospatial queries** (PostGIS: "show all plants within this zone polygon", distance search) and **relational data** (species ↔ submissions ↔ zones ↔ verifications). Firebase's NoSQL model makes this harder. Supabase gives you SQL + PostGIS + realtime in one.

## 4. Why this stack = "10x faster, 10x more accurate"
1. **No custom backend code** for auth/DB/storage/realtime → skip 60% of typical build time.
2. **Domain-specific AI (Pl@ntNet)** instead of a generic model → much higher plant ID accuracy than trying to train your own classifier in a hackathon.
3. **Postgres Realtime** → live map "for free" via a subscription, not custom WebSocket server code.
4. **PWA instead of native app** → one build, works on any phone instantly, no app store review wait.
5. **PostGIS** → zone analytics ("which part of campus is most biodiverse") is a single SQL query, not custom geometry code.

## 5. Deployment Topology
- **Frontend**: Vercel (auto-deploys from GitHub, free SSL, edge CDN).
- **Database/Backend**: Supabase Cloud project (single region closest to campus, e.g., `ap-south-1` for India).
- **AI calls**: made from a Supabase Edge Function (server-side) so your Pl@ntNet API key is never exposed to the client.
- **Images**: stored in Supabase Storage bucket `plant-photos`, served via CDN URL.

## 6. Environment/Connections Overview

| Connection | Protocol | Notes |
|---|---|---|
| Client ↔ Supabase Auth | HTTPS | JWT-based session |
| Client ↔ Supabase Postgres (via PostgREST) | HTTPS (REST) | For standard CRUD |
| Client ↔ Supabase Realtime | WSS (WebSocket) | Subscribes to `plants` table changes |
| Client ↔ Supabase Storage | HTTPS | Direct signed-URL upload of images |
| Edge Function ↔ Pl@ntNet API | HTTPS | Server-side only, API key kept secret |
| Admin Dashboard ↔ Supabase | HTTPS + RLS-scoped JWT | Role-gated queries |
