# 01. Requirements Document

## 1. Problem Restated
Campuses have no digital, living record of what plants exist where. Build a platform that lets people **capture a plant photo + location**, get an **AI-identified species**, and see it appear on a **live interactive biodiversity map**, with supporting inventory, QR tagging, and analytics.

## 2. User Personas

| Persona | Goal | Access level |
|---|---|---|
| **Student/Volunteer Contributor** | Photograph & submit plants they find on campus | Submit, view own submissions |
| **Botany Faculty / Verifier** | Confirm/correct AI species identification, add notes | Verify, edit, annotate |
| **Campus Admin / Sustainability Officer** | View analytics, manage zones, export reports | Full admin, analytics, exports |
| **Public Visitor** (via QR scan) | Scan a plant's QR tag and learn about it | Read-only, single-record view |
| **System (AI service)** | Auto-classify species from image | Service account |

## 3. Functional Requirements

### 3.1 Core (MVP — must work for demo)
- FR1: User can sign up / log in (email, Google OAuth).
- FR2: User can capture/upload a plant photo from mobile or web.
- FR3: System auto-captures GPS coordinates (or lets user pin location on map if GPS unavailable/indoors).
- FR4: System sends the image to an AI plant-identification service and returns top-3 candidate species with confidence scores.
- FR5: User confirms or overrides the AI suggestion before submission.
- FR6: Submission is stored and **immediately visible** (live, no manual refresh) on a shared campus map as a pin/marker.
- FR7: Each map pin shows: species (common + scientific name), photo, submitter, date, zone/location.
- FR8: Digital inventory (searchable/filterable list view) of all identified species — filter by zone, family, native/invasive, date added.
- FR9: Each confirmed plant record gets a **unique QR code** generated, downloadable/printable for physical placement near the plant.
- FR10: Scanning the QR code opens a public read-only detail page for that plant.

### 3.2 Verification & Moderation
- FR11: Faculty/verifier role can approve, edit, reject, or merge duplicate submissions.
- FR12: Unverified vs verified records are visually distinguished on the map (e.g., pending pin color).

### 3.3 Analytics & Reporting (Expected Outcome: "Biodiversity analytics and reports")
- FR13: Dashboard shows: total species count, total individual plants logged, species diversity index (e.g., Shannon index), native vs invasive ratio, submissions over time, most biodiverse zones/heatmap.
- FR14: Exportable reports (PDF/CSV) for sustainability audits or accreditation (e.g., NAAC/NBA green campus reporting).

### 3.4 Admin & Scalability (Expected Outcome: "Scalable solution for educational institutions")
- FR15: Multi-campus/multi-tenant support — each institution has its own isolated map, inventory, and admin.
- FR16: Admin can define campus zones/boundaries (polygon) on a map for zone-wise analytics.
- FR17: Bulk import (CSV) of historical plant records if an institution already has data.

## 4. Non-Functional Requirements

| Category | Requirement |
|---|---|
| **Performance** | Map with 5,000+ pins must render smoothly (clustering required); AI ID response < 5s |
| **Live/Real-time** | New submissions must appear on other connected users' maps within ~1–2 seconds without page refresh |
| **Availability** | 99% uptime target for demo/production; offline capture with sync-later on mobile (poor campus wifi zones) |
| **Scalability** | Must support 50 → 50,000 records without redesign; multi-institution ready |
| **Usability** | Mobile-first; capture-to-submit flow in ≤ 3 taps after photo |
| **Data ownership** | Each institution's data isolated (row-level security) |
| **Accessibility** | WCAG 2.1 AA for public-facing plant detail pages |
| **Compatibility** | Works as installable PWA (Progressive Web App) — avoids App Store approval delays during a hackathon |

## 5. Out of Scope (for MVP, mark as "future work" in your pitch — shows maturity without costing you build time)
- Drone/satellite imagery integration
- Offline AI model (on-device inference) — v2
- Plant health/disease detection — v2
- Multi-language support — v2
- Native iOS/Android apps (PWA covers this for MVP)

## 6. Success Metrics (good to show judges)
- Time from photo capture to live map pin: **< 10 seconds**
- AI identification accuracy on common campus species: **> 85%** (top-1), **> 95%** (top-3)
- Judge-visible "wow" moment: two devices, one submits, the other's map updates live with no refresh.
