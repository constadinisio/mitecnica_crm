---
name: notifications-reminders
title: Notifications & Reminder Scheduling
description: Design internal notifications + email reminders + optional push hooks (without mobile agents).
triggers:
  - reminder
  - notification
  - email schedule
  - calendar reminders
  - exam reminder
outputs:
  - Trigger matrix
  - Scheduling design
  - DB table proposal
  - Templates list
  - Docs section
---

# Notifications & Reminder Scheduling

## When to use
Use this skill when the request matches one of the triggers above.

## Procedure
1. Identify trigger events (new exam, deadline approaching, rescheduled, cancelled).
2. Define recipient rules by role and scope.
3. Define delivery channels (internal + email).
4. Design a scheduling mechanism (DB table + cron worker) and idempotency keys.
5. Implement templates for message bodies and localization if needed.
6. Add rate limits and opt-out preferences where appropriate.


## Safety / guardrails
- Avoid spamming: consolidate notifications and rate limit.
- Do not include sensitive notes in email.
