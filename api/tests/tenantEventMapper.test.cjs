'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');

const {
  mapInstitutionStatusChange,
  buildCreatedPayload,
  buildSuspendedPayload,
  buildReactivatedPayload,
  buildArchivedPayload,
  buildPlanChangedPayload,
  buildModulesChangedPayload,
  extractActiveModuleCodes,
} = require('../src/modules/webhookEmitter/tenantEventMapper');

// -----------------------------------------------------------------------------
// mapInstitutionStatusChange — matriz de transiciones
// -----------------------------------------------------------------------------

test('mapStatusChange: trial→active = null (ambos bucket active)', () => {
  assert.equal(mapInstitutionStatusChange('trial', 'active'), null);
});

test('mapStatusChange: active→maintenance = null', () => {
  assert.equal(mapInstitutionStatusChange('active', 'maintenance'), null);
});

test('mapStatusChange: active→suspended = tenant.suspended', () => {
  assert.equal(mapInstitutionStatusChange('active', 'suspended'), 'tenant.suspended');
});

test('mapStatusChange: trial→suspended = tenant.suspended', () => {
  assert.equal(mapInstitutionStatusChange('trial', 'suspended'), 'tenant.suspended');
});

test('mapStatusChange: active→expired = tenant.suspended (expired→suspended bucket)', () => {
  assert.equal(mapInstitutionStatusChange('active', 'expired'), 'tenant.suspended');
});

test('mapStatusChange: suspended→expired = null (mismo bucket)', () => {
  assert.equal(mapInstitutionStatusChange('suspended', 'expired'), null);
});

test('mapStatusChange: suspended→active = tenant.reactivated', () => {
  assert.equal(mapInstitutionStatusChange('suspended', 'active'), 'tenant.reactivated');
});

test('mapStatusChange: expired→active = tenant.reactivated', () => {
  assert.equal(mapInstitutionStatusChange('expired', 'active'), 'tenant.reactivated');
});

test('mapStatusChange: expired→trial = tenant.reactivated', () => {
  assert.equal(mapInstitutionStatusChange('expired', 'trial'), 'tenant.reactivated');
});

test('mapStatusChange: active→inactive = tenant.archived', () => {
  assert.equal(mapInstitutionStatusChange('active', 'inactive'), 'tenant.archived');
});

test('mapStatusChange: suspended→inactive = tenant.archived', () => {
  assert.equal(mapInstitutionStatusChange('suspended', 'inactive'), 'tenant.archived');
});

test('mapStatusChange: inactive→active = tenant.reactivated', () => {
  assert.equal(mapInstitutionStatusChange('inactive', 'active'), 'tenant.reactivated');
});

test('mapStatusChange: status desconocido = null', () => {
  assert.equal(mapInstitutionStatusChange('active', 'foobar'), null);
  assert.equal(mapInstitutionStatusChange('xxx', 'active'), null);
});

// -----------------------------------------------------------------------------
// Payload builders
// -----------------------------------------------------------------------------

const baseInstitution = {
  id: 42,
  public_code: 'INS-2026-0042',
  name: 'Escuela Piloto',
  slug: 'escuela-piloto',
  subdomain: 'escuela-piloto',
};

test('buildCreatedPayload: usa subdomain como codigo y crm_id', () => {
  const p = buildCreatedPayload(baseInstitution);
  assert.deepEqual(p, {
    crm_id: 42,
    codigo: 'escuela-piloto',
    nombre: 'Escuela Piloto',
    subdomain: 'escuela-piloto',
    plan: null,
    modulos_activos: [],
  });
});

test('buildCreatedPayload: incluye admin si la institución tiene responsible_email', () => {
  const p = buildCreatedPayload({
    ...baseInstitution,
    responsible_name: 'Juan Pérez',
    responsible_email: 'juan.perez@escuela.edu.ar',
  });
  assert.deepEqual(p.admin, {
    nombre: 'Juan',
    apellido: 'Pérez',
    email: 'juan.perez@escuela.edu.ar',
  });
});

test('buildCreatedPayload: usa contact_email como fallback si no hay responsible_email', () => {
  const p = buildCreatedPayload({
    ...baseInstitution,
    contact_email: 'admin@escuela.edu.ar',
  });
  assert.equal(p.admin?.email, 'admin@escuela.edu.ar');
  assert.equal(p.admin?.nombre, 'Administrador');
  assert.equal(p.admin?.apellido, '');
});

test('buildCreatedPayload: nombre completo con varios apellidos se splittea por primer espacio', () => {
  const p = buildCreatedPayload({
    ...baseInstitution,
    responsible_name: 'María José García López',
    responsible_email: 'maria@x.com',
  });
  assert.equal(p.admin.nombre, 'María');
  assert.equal(p.admin.apellido, 'José García López');
});

test('buildCreatedPayload: sin email no incluye admin', () => {
  const p = buildCreatedPayload(baseInstitution);
  assert.equal(p.admin, undefined);
});

test('buildSuspendedPayload: incluye motivo si viene', () => {
  assert.deepEqual(
    buildSuspendedPayload(baseInstitution, 'Falta pago febrero'),
    { codigo: 'escuela-piloto', motivo: 'Falta pago febrero' }
  );
});

test('buildSuspendedPayload: motivo null por default', () => {
  assert.deepEqual(buildSuspendedPayload(baseInstitution), {
    codigo: 'escuela-piloto',
    motivo: null,
  });
});

test('buildReactivatedPayload: solo codigo', () => {
  assert.deepEqual(buildReactivatedPayload(baseInstitution), { codigo: 'escuela-piloto' });
});

test('buildArchivedPayload: solo codigo', () => {
  assert.deepEqual(buildArchivedPayload(baseInstitution), { codigo: 'escuela-piloto' });
});

test('buildPlanChangedPayload: incluye plan.code', () => {
  assert.deepEqual(
    buildPlanChangedPayload(baseInstitution, { id: 2, code: 'professional', name: 'Professional' }),
    { codigo: 'escuela-piloto', plan: 'professional' }
  );
});

test('buildPlanChangedPayload: plan=null cuando no hay subscription', () => {
  assert.deepEqual(buildPlanChangedPayload(baseInstitution, null), {
    codigo: 'escuela-piloto',
    plan: null,
  });
});

test('buildModulesChangedPayload: lista de códigos', () => {
  const p = buildModulesChangedPayload(baseInstitution, ['campus', 'analytics']);
  assert.deepEqual(p, { codigo: 'escuela-piloto', modulos_activos: ['campus', 'analytics'] });
});

test('buildModulesChangedPayload: defaults a [] si no viene array', () => {
  assert.deepEqual(buildModulesChangedPayload(baseInstitution, null), {
    codigo: 'escuela-piloto',
    modulos_activos: [],
  });
});

// -----------------------------------------------------------------------------
// extractActiveModuleCodes
// -----------------------------------------------------------------------------

test('extractActiveModuleCodes: filtra effective_enabled=true', () => {
  const input = {
    modules: [
      { module: { code: 'campus' }, effective_enabled: true },
      { module: { code: 'analytics' }, effective_enabled: false },
      { module: { code: 'attendance' }, effective_enabled: true },
    ],
  };
  assert.deepEqual(extractActiveModuleCodes(input), ['campus', 'attendance']);
});

test('extractActiveModuleCodes: input vacío = []', () => {
  assert.deepEqual(extractActiveModuleCodes(null), []);
  assert.deepEqual(extractActiveModuleCodes({}), []);
  assert.deepEqual(extractActiveModuleCodes({ modules: [] }), []);
});
