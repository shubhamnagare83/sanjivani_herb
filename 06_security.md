# 06. Security Document

## 1. Authentication
- Supabase Auth (JWT-based). Support email/password + Google OAuth (campus SSO/Google Workspace domains ideal for colleges — restrict signup to institutional email domain if desired, e.g. `@sanjivani.edu.in`).
- Public QR landing pages require **no auth** (read-only, anon key with restricted RLS policy).

## 2. Authorization — Role-Based Access Control (RBAC)

| Role | Can submit | Can view all | Can verify/edit | Can manage zones/config | Can export reports |
|---|---|---|---|---|---|
| Public (anon) | ❌ | ✅ (single record via QR only) | ❌ | ❌ | ❌ |
| Contributor | ✅ | ✅ (verified records) | ❌ | ❌ | ❌ |
| Verifier (faculty) | ✅ | ✅ | ✅ | ❌ | ✅ |
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ |

Enforced via **Postgres Row-Level Security (RLS) policies** — not just app-layer checks — so even a compromised client can't bypass rules.

### Example RLS Policies
```sql
alter table plant_records enable row level security;

-- Anyone (even anon) can read VERIFIED records for their institution
create policy "public_read_verified"
on plant_records for select
using (status = 'verified');

-- Authenticated users can insert their own submissions
create policy "contributors_insert"
on plant_records for insert
to authenticated
with check (submitted_by = auth.uid());

-- Only verifier/admin roles can update status
create policy "verifiers_update"
on plant_records for update
to authenticated
using (
  exists (
    select 1 from users
    where users.id = auth.uid()
    and users.role in ('verifier','admin')
  )
);
```

## 3. Data Protection
- All traffic over HTTPS/WSS only (enforced by Vercel + Supabase defaults).
- Images stored in a private bucket with **signed URLs** (time-limited) rather than public-permanent links, unless explicitly published on a QR page.
- No PII beyond name/email/avatar is collected. GPS coordinates are of **plants**, not tracking of **user movement** — clarify this in your privacy note since it's a common judge question.
- Environment secrets (Pl@ntNet API key, service role key) stored only in Supabase Edge Function / Vercel server environment variables — **never** shipped to client bundle.

## 4. Abuse & Data-Quality Prevention
- Rate-limit submissions per user (e.g., max 20/hour) at the Edge Function layer to prevent spam/flooding the live map.
- Duplicate-detection: before insert, query for existing `plant_records` within ~5m radius of same species — nudge user to confirm it's a new individual, not a dupe (`ST_DWithin` PostGIS query).
- Verifier review queue is the main content-quality gate — nothing reaches "verified" (i.e., trusted, publicly QR-linked) status without human confirmation, which also guards against AI misidentification being published as fact.
- Image content check (basic): reject non-image mimetypes, cap file size (e.g., 8MB) client-side and server-side.

## 5. Infrastructure Security Checklist (OWASP-aligned)
- [ ] Input validation on all Edge Function inputs (lat/lng bounds, file type, size)
- [ ] Parameterized queries only (Supabase client / PostgREST handles this by default — avoid raw string SQL concatenation)
- [ ] CORS restricted to your deployed frontend origin(s) on Edge Functions
- [ ] Dependency scanning (`npm audit`) before submission/demo
- [ ] Secrets never committed to git (`.env.local` in `.gitignore`)
- [ ] Admin routes protected both client-side (route guard) and server-side (RLS) — never trust client-side-only checks
- [ ] Audit log (`verifications` table) for all moderation actions — accountability trail

## 6. Multi-Tenant Isolation (for the "scalable to other institutions" requirement)
- Every data-bearing table carries `institution_id`.
- RLS policies scope every query to `auth.jwt() -> institution_id` so Institution A's data is never visible to Institution B's users, even by accident.
