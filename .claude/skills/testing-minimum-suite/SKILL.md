---
name: testing-minimum-suite
title: Minimum Automated Test Suite
description: Create a minimal but effective test suite (API + RBAC + critical flows) to protect refactors.
triggers:
  - add tests
  - regression
  - protect changes
  - calendar tests
  - auth tests
outputs:
  - Test plan
  - Test files list
  - Commands to run
  - Coverage notes
---

# Minimum Automated Test Suite

## When to use
Use this skill when the request matches one of the triggers above.

## Procedure
1. Identify critical flows (login, roles, calendar events, campus activities, grades).
2. Add API tests for success + common failures (401/403/404/422).
3. Add RBAC tests for allow/deny by role and scope.
4. Add at least one integration test for caching behavior if feasible (or unit tests for cache module).
5. Add CI-friendly commands and document how to run tests locally.
6. Ensure tests don’t rely on production secrets.


## Safety / guardrails
- Avoid flaky tests: use stable fixtures and deterministic dates/times.
