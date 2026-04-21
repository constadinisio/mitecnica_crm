'use strict';

const repo = require('./dashboardRepository');

async function summary() {
  return repo.summary();
}

async function operationalSummary(opts = {}) {
  return repo.operationalSummary(opts);
}

module.exports = { summary, operationalSummary };
