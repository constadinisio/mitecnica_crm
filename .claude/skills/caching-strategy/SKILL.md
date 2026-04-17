---
name: caching-strategy
title: Caching & Request Dedupe Strategy
description: Implement practical caching (TTL + invalidation) and request deduplication to reduce API load.
triggers:
  - cache
  - reduce requests
  - API saturation
  - hover load
  - calendar range fetch
  - 2,000 users
outputs:
  - Cache policy table (keys/TTL)
  - Invalidation rules
  - Implementation notes
  - Verification checklist
---

# Caching & Request Dedupe Strategy

## When to use
Use this skill when the request matches one of the triggers above.

## Procedure
1. Identify read-heavy endpoints and data volatility (static vs frequently changing).
2. Choose cache layers: frontend in-memory cache + optional backend cache (if available).
3. Define cache keys (user+role+scope+dateRange+filters).
4. Define TTL per data class (e.g., birthdays daily, feriados weekly, events 30–120s).
5. Implement request coalescing: in-flight identical requests share one promise/result.
6. Implement invalidation rules on writes (create/edit/cancel) and on logout.
7. Verify with a simple metric: requests per navigation should drop; document expected behavior.


## Safety / guardrails
- Do not cache sensitive private events across users.
- Never cache auth tokens in non-secure stores.
