# 🌿 Campus Plant Diversity Mapper — Full Solution Documentation
**Sanjivani University — Surprise Problem Statement Solution Pack**

This folder is the complete engineering documentation set for building an AI-powered, live, geotagged campus biodiversity mapping platform. It's organized so a team can split work immediately: one person on mobile capture, one on backend/DB, one on AI, one on the map/dashboard.

## 📁 Document Index

| # | Document | What it's for |
|---|----------|----------------|
| 1 | `01_requirements.md` | Functional + non-functional requirements, user personas, scope (MVP vs stretch) |
| 2 | `02_architecture_and_techstack.md` | System architecture, recommended tech stack, why it's the fastest path to a working demo |
| 3 | `03_app_flow_and_uiux.md` | Screen-by-screen user flow, wireframe descriptions, states |
| 4 | `04_database_schema.md` | Full relational schema (tables, columns, types, keys, indexes), ERD description |
| 5 | `05_data_flow_diagrams.md` | DFD Level 0/1/2, sequence diagrams for key flows (capture → identify → geotag → publish) |
| 6 | `06_security.md` | AuthN/AuthZ, data protection, abuse prevention, role-based access, OWASP checklist |
| 7 | `07_ai_and_realtime_pipeline.md` | AI plant-ID pipeline design + **live/real-time data architecture** (this is the "10x" enabler) |
| 8 | `08_api_contract.md` | REST/GraphQL endpoint contract you can hand to frontend + backend devs to build in parallel |
| 9 | `09_extra_docs_for_speed.md` | Sprint plan, QR-code spec, analytics/report spec, testing checklist, pitch-deck outline, judge-scoring alignment |

## 🚀 Recommended build order (for a hackathon timeline)
1. Lock schema (`04`) + API contract (`08`) first — this lets frontend and backend devs work in parallel without blocking each other.
2. Stand up Supabase (DB + Auth + Storage + Realtime) — see `02` and `07`.
3. Build capture → AI-ID → geotag flow (mobile-first PWA) — see `03`.
4. Wire the live map (Mapbox/Leaflet + Supabase Realtime subscription) — see `07`.
5. Add QR generation + analytics dashboard last — see `09`.

## 🧠 The core idea in one sentence
A student/staff member photographs a plant on campus → the app calls a plant-ID AI API → the result + GPS coordinates + photo are saved to a live database → the record instantly appears on a shared interactive campus map and public inventory, with a QR code generated for physical signage.

Start with `01_requirements.md`.
