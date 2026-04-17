---
name: mysql-indexing
title: MySQL Indexing for Range Queries
description: Add safe indexes for range queries (date ranges, course scopes) and verify with EXPLAIN.
triggers:
  - slow query
  - index
  - calendar range
  - events between
  - optimize prisma query
outputs:
  - Index proposals
  - Migration SQL
  - EXPLAIN summary
  - Docs note
---

# MySQL Indexing for Range Queries

## When to use
Use this skill when the request matches one of the triggers above.

## Procedure
1. Identify the slow query and its WHERE/ORDER BY clauses.
2. Propose composite indexes aligned with access patterns (e.g., (scope_id, start_at)).
3. Check existing indexes to avoid duplicates.
4. Add indexes via Prisma migration (or direct SQL if the repo uses that).
5. Validate with EXPLAIN and measure before/after query time if possible.
6. Document new indexes and rationale in `/docs/db/`.


## Safety / guardrails
- Avoid overly wide composite indexes.
- Ensure indexes match actual query predicates.
