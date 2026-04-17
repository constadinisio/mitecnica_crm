'use strict';

const institutionsRepo = require('../institutions/institutionRepository');
const auditRepo = require('../audit/auditRepository');

async function summary() {
  const [countByStatus, countByTech, total, upcoming, recentInstitutions, recentActivity] = await Promise.all([
    institutionsRepo.countByStatus(),
    institutionsRepo.countByTechnicalStatus(),
    institutionsRepo.countTotal(),
    institutionsRepo.upcomingExpirations({ days: 30, limit: 8 }),
    institutionsRepo.listRecent(6),
    auditRepo.listRecent(10),
  ]);

  return {
    counts: {
      total,
      by_status: {
        trial: countByStatus.trial || 0,
        active: countByStatus.active || 0,
        maintenance: countByStatus.maintenance || 0,
        suspended: countByStatus.suspended || 0,
        expired: countByStatus.expired || 0,
        inactive: countByStatus.inactive || 0,
      },
      by_technical_status: {
        pending: countByTech.pending || 0,
        optimal: countByTech.optimal || 0,
        updating: countByTech.updating || 0,
        offline: countByTech.offline || 0,
      },
    },
    upcoming_expirations: upcoming,
    recent_institutions: recentInstitutions,
    recent_activity: recentActivity,
  };
}

module.exports = { summary };
