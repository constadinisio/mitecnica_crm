'use strict';

function fromRequest(req) {
  return {
    ip: req.headers['x-forwarded-for']?.split(',')[0]?.trim() || req.ip || req.socket?.remoteAddress || null,
    userAgent: req.headers['user-agent'] || null,
  };
}

function diff(before, after, ignoreKeys = ['updated_at', 'created_at']) {
  if (!before) return { before: null, after: after || null };
  const out = { before: {}, after: {} };
  const keys = new Set([...Object.keys(before || {}), ...Object.keys(after || {})]);
  for (const k of keys) {
    if (ignoreKeys.includes(k)) continue;
    if (JSON.stringify(before?.[k]) !== JSON.stringify(after?.[k])) {
      out.before[k] = before?.[k] ?? null;
      out.after[k] = after?.[k] ?? null;
    }
  }
  if (Object.keys(out.before).length === 0 && Object.keys(out.after).length === 0) {
    return null;
  }
  return out;
}

module.exports = { fromRequest, diff };
