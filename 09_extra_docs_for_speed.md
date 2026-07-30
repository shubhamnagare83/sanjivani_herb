# 09. Extra Documents (the "10x faster" pack)
These aren't in your original list but will save you real time and points.

## 1. Hackathon Sprint Plan (assumes a 24-48hr build window — adjust to your actual timeline)

| Phase | Time | Tasks | Owner suggestion |
|---|---|---|---|
| Setup | Hr 0–2 | Create Supabase project, run schema DDL, set up Next.js repo, deploy skeleton to Vercel | Whole team |
| Core loop | Hr 2–10 | Capture screen, image upload, Edge Function `identify-plant`, submit flow | 2 devs |
| Live map | Hr 6–14 | Leaflet map, clustering, Realtime subscription, pin styling | 1 dev |
| Verifier + Admin | Hr 10–18 | Review queue, RLS policies, zone editor | 1 dev |
| QR + Public page | Hr 14–20 | QR generation, public detail page | 1 dev |
| Analytics | Hr 16–22 | Dashboard KPIs + charts | 1 dev |
| Polish + demo prep | Hr 20–24+ | Seed demo data, rehearse live-update demo on 2 devices, pitch deck | Whole team |

**Golden rule:** get the capture → AI → live map loop working end-to-end (even ugly) before polishing anything. That loop *is* the demo.

## 2. QR Code Technical Spec
- Encode: `https://<your-domain>/plant/{public_slug}` (slug, not raw UUID — shorter QR = more reliable scans)
- Generate client-side with `qrcode.react`; export as PNG (for digital) and embed in a printable PDF label template (species name + QR + institution logo) via `jsPDF` for physical signage.
- Suggested physical label size: 10cm x 7cm, laminated, for outdoor durability — mention in your pitch, not required for the app itself.

## 3. Demo Data Seeding Script (recommended)
Write a `seed.sql` or Node script that inserts ~30-50 realistic plant records across campus so your live map doesn't look empty when judges see it. Include a mix of statuses (verified/pending) and a couple of "invasive" flags to show off the analytics.

## 4. Testing Checklist
- [ ] Two browser tabs open on Live Map — submit from one, confirm pin appears on the other within 2s
- [ ] Submit with GPS denied → manual pin-drop flow works
- [ ] Submit with poor/no network → offline queue → syncs on reconnect
- [ ] Low-confidence AI result → forces manual entry path
- [ ] Verifier rejects a record → disappears from public map, stays in admin log
- [ ] QR scan on a real phone → public page loads, no login prompt
- [ ] RLS test: log in as Institution A user, confirm Institution B data is invisible
- [ ] Map performs smoothly with 500+ seeded pins (clustering test)

## 5. Judge-Scoring Alignment (map your build to what's likely being scored)

| Likely judging criterion | What to point to in your demo |
|---|---|
| Innovation/AI use | Pl@ntNet-powered real ID, not a hardcoded list |
| Technical execution | Live 2-device map update, PostGIS zone queries |
| Real-world usability | Mobile-first PWA, offline capture, QR physical signage |
| Impact/sustainability | Analytics dashboard — diversity index, native vs invasive |
| Scalability | Multi-tenant architecture, RLS isolation, works for any institution |
| Completeness | End-to-end: capture → AI → verify → map → QR → analytics → export |

## 6. Suggested Pitch Deck Outline (5-7 slides)
1. Problem (campuses have no living biodiversity record)
2. Solution overview (one architecture diagram from `02_architecture_and_techstack.md`)
3. Live demo (do this live — the real-time map update is your strongest moment)
4. AI accuracy approach (mention Pl@ntNet + human verification loop)
5. Analytics/impact screenshot (diversity index, native vs invasive)
6. Scalability story (multi-campus ready, one line about RLS)
7. Roadmap (offline on-device AI, disease detection, multi-language — shows vision beyond the hackathon)

## 7. Things to say explicitly to judges (often overlooked, easy points)
- "Data updates live — no refresh — powered by Postgres logical replication streamed over WebSocket."
- "Every AI identification is verified by a human before being published, so what's public is trustworthy, not just an AI guess."
- "The schema is multi-tenant from day one — this isn't a one-campus hack, it's deployable to any institution today."
