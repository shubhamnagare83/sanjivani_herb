# 03. App Flow & UI/UX

## 1. Screen Map

```
Splash/Login
   │
   ├─→ Sign Up / Login (Email or Google)
   │
   ▼
Home (Tab Navigation)
   ├── 🗺️ Live Map          (default landing tab)
   ├── 📸 Capture/Add Plant
   ├── 📋 Inventory List
   ├── 📊 Analytics (admin/faculty only)
   └── 👤 Profile / My Submissions

Public (no login) route:
   └── /plant/:qrId  → Public Plant Detail Page (from QR scan)
```

## 2. Detailed Flow: "Add a Plant" (the core happy path)

1. **Tap "Capture" (camera FAB button)**
2. Camera opens → user takes photo (or picks from gallery)
3. App silently requests GPS location in background (`navigator.geolocation`)
   - If GPS denied/unavailable → user drops a pin manually on a mini-map
4. **Loading state**: "Identifying your plant…" → image sent to AI Edge Function
5. **Results screen**: shows top-3 species candidates with confidence % and reference photos
   - User taps the correct one, OR taps "None of these — I'll name it" (manual entry, flagged for verifier review)
6. **Detail form** (pre-filled where possible):
   - Common name (auto)
   - Scientific name (auto)
   - Zone/location label (auto-suggested from GPS + campus zone polygons, editable)
   - Notes (optional, free text)
   - Photo (already attached)
7. **Submit** → record written to DB with status = `pending_verification` (or `verified` if submitter is faculty/admin)
8. **Instant feedback**: "🌱 Added! Visible on the live map now." + confetti/animation
9. **Realtime push**: all other open clients' map subscriptions receive the INSERT event → new pin fades in on their map within ~1-2s, no refresh needed.

## 3. Flow: Live Map View
- Default centered on campus (predefined lat/lng + zoom).
- Pins clustered when zoomed out (shows count bubble), expand on zoom-in.
- Pin color coding: 🟢 Verified · 🟡 Pending verification · 🔴 Invasive species flagged
- Tap pin → bottom sheet with photo, species, submitter, "View full details," "Get QR code"
- Filter bar: by zone, by verified/pending, by native/invasive, by date range, by species search

## 4. Flow: Inventory List
- Searchable/sortable table/card list of all species (grouped by species, showing count of individuals found campus-wide)
- Tap a species → species profile page (all locations of that species, aggregate photo gallery, Wikipedia-style info pulled from AI API metadata)

## 5. Flow: QR Code
- On any **verified** plant record → "Generate QR" button
- QR encodes: `https://yourapp.app/plant/{unique_id}`
- Downloadable as PNG/PDF label sized for outdoor signage (weatherproof laminate suggestion goes in your pitch, not the app)
- Scanning → public page: species name, photo, fun facts, native status, "Report an issue" link (no login needed)

## 6. Flow: Verifier/Faculty Review Queue
- Tab showing all `pending_verification` records
- Swipe/click: ✅ Approve · ✏️ Edit & Approve · ❌ Reject (with reason) · 🔀 Merge with existing species entry

## 7. Flow: Admin Analytics Dashboard
- KPI cards: Total species, Total plants logged, Contributors count, Verification rate
- Charts: Species diversity over time, Species by zone (bar), Native vs Invasive (pie), Submission activity heatmap by day/week
- Zone editor: draw polygon boundaries on map to define "Zone A – Botanical Garden," etc.
- Export button → PDF/CSV report

## 8. Key UI States to Design For
- Empty state (no plants yet — "Be the first to map this campus!")
- Offline state (banner: "You're offline — this will sync when you're back online")
- AI-uncertain state (low confidence — nudge toward manual entry/verifier flag)
- Duplicate-detection nudge ("A plant was already logged ~5m from here — is this the same one?")

## 9. Design Principles
- **Mobile-first, one-thumb operable** — most captures happen while walking campus.
- **Minimize typing** — dropdowns, auto-suggest, and AI pre-fill over free text wherever possible.
- **Instant visual reward** — the live map update is your "wow" feature; make the pin animation satisfying.
- **Green/earth visual theme** — use it to reinforce the conservation/sustainability narrative for judges.
