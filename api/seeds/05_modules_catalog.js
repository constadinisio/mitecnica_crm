'use strict';

const MODULES = [
  { code: 'attendance',   name: 'Asistencias',        description: 'Control de asistencia de alumnos y docentes.',         category: 'academic',       status: 'active', is_core: true  },
  { code: 'grades',       name: 'Calificaciones',     description: 'Carga y seguimiento de notas por materia.',             category: 'academic',       status: 'active', is_core: true  },
  { code: 'campus',       name: 'Campus Virtual',     description: 'Aula virtual con tareas, foros y material de clase.',   category: 'academic',       status: 'active', is_core: false },
  { code: 'report_cards', name: 'Boletines',          description: 'Generación y distribución de boletines.',               category: 'academic',       status: 'active', is_core: true  },
  { code: 'families',     name: 'Familias',           description: 'Comunicación con familias y app para tutores.',         category: 'communication',  status: 'active', is_core: false },
  { code: 'doe',          name: 'DOE / Orientación',  description: 'Seguimiento de intervenciones del equipo de orientación.', category: 'administration', status: 'active', is_core: false },
  { code: 'inventory',    name: 'Inventario',         description: 'Gestión de equipamiento y recursos por aula.',          category: 'administration', status: 'active', is_core: false },
  { code: 'analytics',    name: 'Analítica',          description: 'Dashboards y reportes avanzados por institución.',      category: 'analytics',      status: 'active', is_core: false },
];

exports.seed = async function seed(knex) {
  for (const mod of MODULES) {
    const existing = await knex('modules_catalog').where({ code: mod.code }).first();
    if (existing) {
      await knex('modules_catalog').where({ id: existing.id }).update({ ...mod, updated_at: knex.fn.now() });
    } else {
      await knex('modules_catalog').insert(mod);
    }
  }
};
