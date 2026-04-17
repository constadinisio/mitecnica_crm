/**
 * Mi Tecnica CRM — PM2 ecosystem file.
 * Usage:
 *   pm2 start infra/pm2/ecosystem.config.cjs
 *   pm2 reload mitecnica-crm-api
 *   pm2 logs mitecnica-crm-api
 */

module.exports = {
  apps: [
    {
      name: 'mitecnica-crm-api',
      cwd: './api',
      script: 'src/server.js',
      instances: 1,                // increase to 'max' for multi-core in production
      exec_mode: 'fork',           // switch to 'cluster' together with instances > 1
      node_args: '--max-old-space-size=512',
      autorestart: true,
      max_restarts: 10,
      restart_delay: 2000,
      watch: false,
      env: {
        NODE_ENV: 'production',
      },
      env_development: {
        NODE_ENV: 'development',
      },
      error_file:  './crm/storage/logs/pm2-api-error.log',
      out_file:    './crm/storage/logs/pm2-api-out.log',
      merge_logs: true,
      log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
    },
  ],
};
