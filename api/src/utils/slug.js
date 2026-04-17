'use strict';

function slugify(str, { maxLength = 80 } = {}) {
  if (!str) return '';
  const normalized = String(str)
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
  return normalized.slice(0, maxLength);
}

function uniqueSlug(base, exists) {
  const slug = slugify(base);
  if (!exists) return slug;
  let candidate = slug;
  let i = 2;
  while (exists(candidate)) {
    candidate = `${slug}-${i}`;
    i += 1;
  }
  return candidate;
}

module.exports = { slugify, uniqueSlug };
