---
name: mobile-migration-plan
title: Mobile Migration Plan (for later)
description: Provides planning structure for migrating PHP screens to mobile apps while reusing API and minimizing churn.
triggers:
  - mobile migration
  - android ios plan
  - screen by screen
  - tablet layouts
  - phone redesign
outputs:
  - Screen inventory
  - Navigation map
  - Milestone roadmap
---

# Mobile Migration Plan (for later)

## When to use
Use this skill when the request matches one of the triggers above.

## Procedure
1. Inventory screens per role and map them to API endpoints and data models.
2. Define navigation map for mobile by role.
3. Define tablet vs phone layout rules.
4. Define caching and pagination rules to avoid API load.
5. Define incremental milestones (skeleton -> auth -> core features -> parity).


## Safety / guardrails
- Do not attempt full rewrite in one iteration.
