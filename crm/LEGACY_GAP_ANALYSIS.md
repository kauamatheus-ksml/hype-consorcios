# Mapa de Migracao do Legado PHP

Este documento resume o estado atual da migracao do `subsystem/` PHP para `crm/` Next.js.

## Coberto no Next

| Legado PHP | Next.js |
| --- | --- |
| `subsystem/login.php`, `api/auth.php` | `/login`, `/api/auth/login`, `/api/auth/logout`, `/api/auth/validate`, `/api/auth/change-password` |
| `subsystem/dashboard.php`, `api/dashboard_stats*.php` | `/dashboard`, `/api/dashboard/stats` |
| `subsystem/leads.php`, `api/leads.php`, `api/leads_stats.php`, `api/capture_lead.php` | `/leads`, `/api/leads`, `/api/leads/:id`, `/api/leads/:id/whatsapp`, `/api/leads/:id/assign`, `/api/leads/:id/claim`, `/api/leads/:id/interactions`, `/api/leads/stats`, `/api/capture-lead` |
| `subsystem/sales.php`, `api/sales*.php`, `api/sales_stats*.php` | `/sales`, `/api/sales`, `/api/sales/convert`, `/api/sales/:id`, `/api/sales/:id/cancel`, `/api/sales/stats` |
| `api/commission_report.php` | `/commission-reports`, `/api/commission-reports`, `/api/sales/report` |
| `api/seller_commission.php`, `commission_settings.php` | `/commission-settings`, `/api/commission-settings` |
| `subsystem/users.php`, `api/users.php` | `/users`, `/api/users`, `/api/users/:id` |
| `subsystem/profile.php`, `api/profile.php` | `/profile`, `/api/profile` |
| `subsystem/site-config.php`, `api/site-config.php` | `/site-config`, `/api/site-config`, `/api/site-config/upload` |
| `api/faq.php` | CRUD de FAQs dentro de `/site-config`, `/api/faqs` |
| `api/system_settings.php` | `/system-settings`, `/api/system-settings` |
| `subsystem/audit-logs.php`, `classes/AuditLogger.php` | `/audit-logs`, `/api/audit-logs`, helper `logAuditEvent` em mutacoes principais |

## Scripts Legados que Nao Devem Ir Para Producao

Arquivos abaixo sao instaladores, corretores ou debug. Eles podem continuar no repo como historico, mas nao devem ser expostos publicamente em hospedagem:

- `install_database.php`
- `create_site_config_table.php`
- `create_faq_table.php`
- `activate_commission_system.php`
- `init_audit_system.php`
- `fix_admin.php`
- `fix_career_image.php`
- `backup_configs.php`
- `restore_configs.php`
- `debug_session.php`
- `commission_auth_debug.php`
- `commission_settings_debug.php`
- `site-config-debug.php`
- `capture_lead_debug.php`
- `test-*.php`

## Pendencias Antes do Corte

- Validar criacao/edicao real em banco de staging, porque o smoke atual evita gravacoes reais.
- Configurar `window.HYPE_CRM_CAPTURE_ENDPOINT` nas paginas publicas para apontar para `/api/capture-lead` quando o CRM Next estiver publicado no dominio final.
- Definir storage de upload se Next e PHP ficarem em servidores separados.
- Confirmar perfis reais de admin, manager e seller em homologacao.
- Scripts/debug PHP ja possuem bloqueio defensivo no `.htaccess`; antes do corte, confirmar se o Apache da hospedagem respeita essas regras.

## Recomendacao de Corte

Manter o legado PHP no ar e publicar o CRM Next em subdominio primeiro:

```text
crm.hypeconsorcios.com.br
```

Depois de homologado, redirecionar o painel administrativo antigo para o novo CRM. O site publico pode continuar no PHP ate a migracao completa da frente publica.
