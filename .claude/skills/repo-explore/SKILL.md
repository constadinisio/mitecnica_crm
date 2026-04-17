---
name: repo-explore
title: Repo Explore & Map
description: Standard method to map project structure, modules, endpoints, and key data flows without changing behavior.
triggers:
  - audit codebase
  - map modules
  - understand repo
  - where is X implemented
  - inventory screens/endpoints
outputs:
  - Module map
  - Screen→Endpoint→DB map
  - Hotspot list with file paths
---

# Repo Explore & Map

## When to use
Use this skill when the request matches one of the triggers above.

## Procedure
1. Read `AGENTS.md` to align constraints.
2. List top-level folders and identify backend vs web UI areas.
3. Locate API routes/controllers/services and DB schema (Prisma schema + migrations).
4. Locate PHP pages/templates and identify which endpoints they call.
5. Produce a map: modules → screens → endpoints → DB tables, with role visibility notes.
6. Identify hotspots: large list endpoints, N+1 patterns, heavy queries, repeated requests.


## Safety / guardrails
- Do not modify code in Explore phase.
- Avoid assumptions; cite exact file paths and functions.
