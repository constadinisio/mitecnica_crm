'use strict';

const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 20;
const MAX_LIMIT = 100;

function parse(query = {}) {
  let page = Number.parseInt(query.page, 10);
  let limit = Number.parseInt(query.limit, 10);
  if (!Number.isFinite(page) || page < 1) page = DEFAULT_PAGE;
  if (!Number.isFinite(limit) || limit < 1) limit = DEFAULT_LIMIT;
  if (limit > MAX_LIMIT) limit = MAX_LIMIT;
  const offset = (page - 1) * limit;
  return { page, limit, offset };
}

function buildMeta({ page, limit, total }) {
  const pages = total > 0 ? Math.ceil(total / limit) : 0;
  return {
    pagination: {
      page,
      limit,
      total,
      pages,
      hasNext: page < pages,
      hasPrev: page > 1,
    },
  };
}

module.exports = { parse, buildMeta, DEFAULT_LIMIT, MAX_LIMIT };
