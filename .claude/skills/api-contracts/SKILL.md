---
name: api-contracts
title: API Contract Design
description: Design or refine REST API endpoints with consistent request/response shapes, errors, pagination, and RBAC.
triggers:
  - design endpoint
  - API spec
  - add route
  - batch endpoint
  - calendar endpoints
  - notifications endpoints
outputs:
  - Endpoint table
  - Request/response JSON examples
  - RBAC matrix
  - Doc page in /docs/api/
---

# API Contract Design

## When to use
Use this skill when the request matches one of the triggers above.

## Procedure
1. Define resources and verbs (GET/POST/PATCH/DELETE) with versioned paths.
2. Specify auth requirements (JWT access token) and RBAC policy (who can read/write).
3. Define request schema (JSON body/query params) and response schema (data + meta).
4. Include pagination/filter/sort conventions for list endpoints.
5. Define error model (status codes + error codes + messages).
6. Add examples for success and failure cases.
7. Document in `/docs/api/` and link from feature docs.


## Safety / guardrails
- Never expose PII beyond need; birthdays show day/month only.
- Validate all input; sanitize user-provided text.
