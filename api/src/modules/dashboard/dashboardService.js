'use strict';

const repo = require('./dashboardRepository');

async function summary() {
  return repo.summary();
}

module.exports = { summary };
