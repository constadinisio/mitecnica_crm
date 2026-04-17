---
name: prisma-migrations
title: Prisma Migration Safety
description: Create safe Prisma schema changes with reversible migrations and minimal downtime.
triggers:
  - prisma migration
  - add table
  - alter table
  - calendar tables
  - device tokens table
outputs:
  - Prisma schema diff
  - Migration SQL
  - Rollback notes
  - Docs
---

# Prisma Migration Safety

## When to use
Use this skill when the request matches one of the triggers above.

## Procedure
1. Design schema change (tables, columns, constraints) and confirm with feature requirements.
2. Prefer additive changes (new columns/tables) over destructive operations.
3. Use nullable columns first, then backfill, then enforce constraints if needed.
4. Generate migration and review SQL for safety.
5. Add indexes and foreign keys carefully.
6. Document migration steps and any data backfill in `/docs/db/migrations.md`.


## Safety / guardrails
- Never drop columns/tables without explicit user approval.
- Avoid locking large tables during peak usage.
