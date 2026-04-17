---
name: calendar-design
title: Calendar Module Design
description: End-to-end design checklist for MiET20 calendar: roles, event types, conflict detection, reminders, performance.
triggers:
  - calendar
  - agenda view
  - exam conflict
  - birthdays calendar
  - feriados integration
outputs:
  - DB schema proposal
  - Endpoints proposal
  - RBAC matrix
  - UI spec
  - Reminder plan
---

# Calendar Module Design

## When to use
Use this skill when the request matches one of the triggers above.

## Procedure
1. Define event taxonomy (birthday, delivery, exam, holiday, institutional, meeting, sensitive).
2. Define scope model (user, course, subject, academic year) and visibility rules (RBAC matrix).
3. Design DB tables: events, event_audience/scope, event_audit, reminders, optional resources bookings.
4. Design endpoints: list by range + filters, detail, create/edit/cancel, conflict-check.
5. Define UI views: month/week/agenda, filters, details panel, create/edit modal per role.
6. Define reminders: internal notifications + email scheduling strategy (cron/queue).
7. Apply caching-strategy skill for range queries and frontend dedupe.


## Safety / guardrails
- Default to least-privilege visibility.
- Birthdays: day/month only; opt-out supported.
