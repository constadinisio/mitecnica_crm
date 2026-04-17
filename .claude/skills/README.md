# MiET20 Skills Library

Each skill lives in its own folder under `/.claude/skills/<skill-name>/SKILL.md`.
Claude should reference skills in plans instead of repeating long procedures.

## Included skills
- **repo-explore** — Repo Explore & Map: Standard method to map project structure, modules, endpoints, and key data flows without changing behavior.
- **api-contracts** — API Contract Design: Design or refine REST API endpoints with consistent request/response shapes, errors, pagination, and RBAC.
- **rbac-permissions** — RBAC & Visibility Matrix: Create role-based access control rules and visibility matrices for new features.
- **caching-strategy** — Caching & Request Dedupe Strategy: Implement practical caching (TTL + invalidation) and request deduplication to reduce API load.
- **mysql-indexing** — MySQL Indexing for Range Queries: Add safe indexes for range queries (date ranges, course scopes) and verify with EXPLAIN.
- **prisma-migrations** — Prisma Migration Safety: Create safe Prisma schema changes with reversible migrations and minimal downtime.
- **security-basics** — Security Baseline for MiET20: Baseline security checks: JWT, refresh flow, RBAC, input validation, file upload prep, rate limiting.
- **testing-minimum-suite** — Minimum Automated Test Suite: Create a minimal but effective test suite (API + RBAC + critical flows) to protect refactors.
- **docs-standard** — Docs Standard & ADRs: Standard structure for feature docs, API docs, DB docs, and lightweight ADRs.
- **calendar-design** — Calendar Module Design: End-to-end design checklist for MiET20 calendar: roles, event types, conflict detection, reminders, performance.
- **notifications-reminders** — Notifications & Reminder Scheduling: Design internal notifications + email reminders + optional push hooks (without mobile agents).
- **mobile-migration-plan** — Mobile Migration Plan (for later): Provides planning structure for migrating PHP screens to mobile apps while reusing API and minimizing churn.
