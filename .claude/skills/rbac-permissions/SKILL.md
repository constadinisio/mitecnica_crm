---
name: rbac-permissions
title: RBAC & Visibility Matrix
description: Create role-based access control rules and visibility matrices for new features.
triggers:
  - permissions
  - roles
  - who can see
  - family role
  - calendar visibility
  - campus permissions
outputs:
  - Role×Action matrix
  - Policy rules
  - Test cases list
  - Docs section
---

# RBAC & Visibility Matrix

## When to use
Use this skill when the request matches one of the triggers above.

## Procedure
1. List roles (admin/directivo, personal, docente, alumno, familia, DOE/preceptoría).
2. List resources/actions (read list, read detail, create, edit, cancel, export).
3. Define constraints (course scope, academic-year scope, subject scope).
4. Produce a visibility matrix and enforce it server-side (middleware/policy).
5. Add tests for a few critical allow/deny cases.
6. Document the matrix in feature docs.


## Safety / guardrails
- Default deny: if unclear, restrict and ask.
- Avoid broad queries that leak data across courses/years.
