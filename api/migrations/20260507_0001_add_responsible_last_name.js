'use strict';

/**
 * Separa "Nombre" y "Apellido" del responsable a nivel de institución, y del
 * contacto a nivel de lead. Antes guardábamos un solo string ("responsible_name"
 * / "contact_name") y splitteábamos por el primer espacio en el momento de
 * armar el payload del welcome email del tenant — eso falla con nombres
 * compuestos ("María José García" → apellido = "José García").
 *
 * Migración aditiva: agrega *_last_name nullable y backfilea best-effort para
 * los registros existentes (split por primer espacio). El backfill es solo
 * para no perder data; los nombres compuestos van a quedar mal categorizados
 * y el operador puede corregirlos desde el form.
 */
exports.up = async function up(knex) {
  await knex.schema.alterTable('institutions', (t) => {
    t.string('responsible_last_name', 160).nullable();
  });
  await knex.schema.alterTable('contact_requests', (t) => {
    t.string('contact_last_name', 160).nullable();
  });

  // Backfill best-effort: split por primer espacio. Solo poblamos last_name
  // cuando hay espacio; si no, dejamos last_name null y el name original queda intacto.
  await knex.raw(`
    UPDATE institutions
    SET responsible_last_name = TRIM(SUBSTRING(responsible_name FROM POSITION(' ' IN responsible_name) + 1)),
        responsible_name      = TRIM(SUBSTRING(responsible_name FROM 1 FOR POSITION(' ' IN responsible_name) - 1))
    WHERE responsible_name IS NOT NULL
      AND POSITION(' ' IN responsible_name) > 0;
  `);

  await knex.raw(`
    UPDATE contact_requests
    SET contact_last_name = TRIM(SUBSTRING(contact_name FROM POSITION(' ' IN contact_name) + 1)),
        contact_name      = TRIM(SUBSTRING(contact_name FROM 1 FOR POSITION(' ' IN contact_name) - 1))
    WHERE contact_name IS NOT NULL
      AND POSITION(' ' IN contact_name) > 0;
  `);
};

exports.down = async function down(knex) {
  // Reconstruye el name single-string concatenando, así no perdemos data al rollback.
  await knex.raw(`
    UPDATE institutions
    SET responsible_name = TRIM(CONCAT(COALESCE(responsible_name, ''), ' ', COALESCE(responsible_last_name, '')))
    WHERE responsible_last_name IS NOT NULL;
  `);
  await knex.raw(`
    UPDATE contact_requests
    SET contact_name = TRIM(CONCAT(COALESCE(contact_name, ''), ' ', COALESCE(contact_last_name, '')))
    WHERE contact_last_name IS NOT NULL;
  `);

  await knex.schema.alterTable('contact_requests', (t) => {
    t.dropColumn('contact_last_name');
  });
  await knex.schema.alterTable('institutions', (t) => {
    t.dropColumn('responsible_last_name');
  });
};
