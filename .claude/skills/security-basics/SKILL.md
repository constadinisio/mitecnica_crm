---
name: security-basics
title: Security Baseline for MiET20
description: Baseline security checks: JWT, refresh flow, RBAC, input validation, file upload prep, rate limiting.
triggers:
  - security review
  - JWT
  - refresh token
  - role leak
  - XSS
  - upload
  - calendar notes
outputs:
  - Security checklist results
  - Mitigation plan
  - RBAC test suggestions
  - Docs update
---

# Security Baseline for MiET20

## When to use
Use this skill when the request matches one of the triggers above.

## Procedure
1. Confirm JWT verification on protected routes and refresh token rotation policy (if present).
2. Ensure RBAC checks on every endpoint that returns scoped data.
3. Validate and sanitize all user-provided text fields.
4. Add rate limits to sensitive endpoints (auth, notifications, calendar writes).
5. Ensure audit logs for critical actions (reschedule exams, suspensions).
6. Document security assumptions and mitigations.


## Safety / guardrails
- Never expose full DOB; show day/month only.
- Never log tokens/credentials.
