'use strict';

const { db } = require('../../config/db');

const TABLE = 'institution_modules';

async function listByInstitution(institutionId) {
  return db(TABLE).where({ institution_id: institutionId }).select('*');
}

async function listByInstitutionIds(institutionIds) {
  if (!institutionIds || institutionIds.length === 0) return [];
  return db(TABLE)
    .whereIn('institution_id', institutionIds)
    .select('id', 'institution_id', 'module_id', 'override_mode', 'notes');
}

async function replaceOverrides(institutionId, overrides) {
  return db.transaction(async (trx) => {
    const before = await trx(TABLE).where({ institution_id: institutionId }).select('*');
    await trx(TABLE).where({ institution_id: institutionId }).del();
    if (overrides && overrides.length > 0) {
      const rows = overrides.map((o) => ({
        institution_id: institutionId,
        module_id: o.module_id,
        override_mode: o.override_mode,
        notes: o.notes || null,
      }));
      await trx(TABLE).insert(rows);
    }
    const after = await trx(TABLE).where({ institution_id: institutionId }).select('*');
    return { before, after };
  });
}

module.exports = { listByInstitution, listByInstitutionIds, replaceOverrides };
