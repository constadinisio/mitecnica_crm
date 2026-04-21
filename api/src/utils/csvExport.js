'use strict';

/**
 * Tiny CSV writer for streamed exports. Handles quoting, BOM for Excel,
 * and ISO date normalization for Date values.
 *
 * Rows are written synchronously to the response so memory stays bounded
 * when row count grows (Node buffers internally).
 */

const BOM = '\uFEFF';

function escapeCell(value) {
  if (value === null || value === undefined) return '';
  if (value instanceof Date) return value.toISOString();
  const s = String(value);
  if (s.includes('"') || s.includes(',') || s.includes('\n') || s.includes('\r')) {
    return `"${s.replace(/"/g, '""')}"`;
  }
  return s;
}

function formatRow(fields, row) {
  return fields.map((f) => escapeCell(typeof f.map === 'function' ? f.map(row) : row[f.key])).join(',');
}

/**
 * Stream a CSV response.
 *  res:     Express response
 *  filename: suggested download name (without extension)
 *  fields:  [{ key, header, map? }]
 *  rows:    iterable or async iterable of objects
 */
async function writeCsv(res, { filename, fields, rows }) {
  const safeName = String(filename || 'export')
    .replace(/[^a-zA-Z0-9._-]/g, '_')
    .slice(0, 80);
  res.setHeader('Content-Type', 'text/csv; charset=utf-8');
  res.setHeader('Content-Disposition', `attachment; filename="${safeName}.csv"`);
  res.setHeader('Cache-Control', 'no-store');

  res.write(BOM);
  res.write(fields.map((f) => escapeCell(f.header)).join(',') + '\n');

  for await (const row of rows) {
    res.write(formatRow(fields, row) + '\n');
  }
  res.end();
}

module.exports = { writeCsv, escapeCell };
