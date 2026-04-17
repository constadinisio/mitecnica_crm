---
name: docs-standard
title: Docs Standard & ADRs
description: Standard structure for feature docs, API docs, DB docs, and lightweight ADRs.
triggers:
  - write docs
  - document feature
  - ADR
  - api docs
  - db docs
outputs:
  - Feature doc template applied
  - ADR entry
  - Updated docs index
---

# Docs Standard & ADRs

## When to use
Use this skill when the request matches one of the triggers above.

## Procedure
1. Create `/docs/<feature>/README.md` with Overview, Roles, Data model, API, UI, Performance, Security.
2. Add endpoint tables and example payloads.
3. Add a small ADR in `/docs/adr/` for major decisions (cache strategy, conflict detection).
4. Update any existing index pages so docs are discoverable.
5. Keep docs concise and actionable.


## Safety / guardrails
- Do not include secrets or personal data in docs.
