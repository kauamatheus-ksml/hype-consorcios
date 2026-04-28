# Hype Consorcios CRM

Base Next.js para migrar o subsistema administrativo legado em PHP.

## Stack

- Next.js App Router
- TypeScript
- Tailwind CSS
- Supabase/PostgreSQL existente
- JWT em cookie HttpOnly
- `bcryptjs` com compatibilidade para hashes PHP `$2y$`
- `zustand` reservado para estado global do frontend
- `lucide-react` para icones

## Setup

```powershell
npm install
Copy-Item .env.local.example .env.local
npm run dev
```

O dev server roda em:

```text
http://127.0.0.1:3000
```

O site PHP legado pode ser validado localmente com PHP CLI:

```powershell
php -S 127.0.0.1:8000 .local-router.php
```

URLs locais usadas nesta migracao:

```text
Site PHP: http://127.0.0.1:8000
CRM Next: http://127.0.0.1:3000
```

Neste ambiente Windows, `npm run dev` usa Webpack porque o modo dev com Turbopack consumiu recurso demais. Para testar Turbopack manualmente:

```powershell
npm run dev:turbo
```

## Variaveis de ambiente

Use `.env.local.example` como modelo. A service role key deve ser usada apenas no backend/API routes.

```env
NEXT_PUBLIC_SUPABASE_URL=https://your-project-ref.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=your-supabase-anon-key
SUPABASE_SERVICE_ROLE_KEY=your-supabase-service-role-key
SITE_ASSETS_BUCKET=hype-site-assets
JWT_SECRET=replace-with-at-least-32-random-bytes
DB_HOST=aws-0-us-west-2.pooler.supabase.com
DB_NAME=postgres
DB_USER=postgres.your-project-ref
DB_PASS=your-database-password
DB_PORT=5432
TZ=America/Sao_Paulo
```

Na Vercel, configure o projeto com **Root Directory = `crm`**. O arquivo `crm/vercel.json` define `npm ci` e `npm run build`.

## Scripts

```powershell
npm run dev
npm run lint
npm run build
npm run predeploy
npm run smoke
npm run start
```

`npm run predeploy` roda lint e build. `npm run smoke` pressupoe o dev server rodando em `http://127.0.0.1:3000` e usa o banco configurado no `.env.local`. Ele valida contratos principais sem criar registros reais; a captura publica de lead e testada apenas com payload invalido.

## APIs implementadas

Health:

- `GET /api/health` valida variaveis criticas, Node.js e conexao com PostgreSQL/Supabase sem expor segredos.

Auth:

- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/auth/validate`
- `POST /api/auth/change-password`

Dashboard:

- `GET /api/dashboard/stats`
- `GET /api/dashboard/stats?seller_id=10` para filtro de vendedor quando o usuario autenticado for admin.

Leads:

- `POST /api/capture-lead` endpoint publico equivalente ao `subsystem/api/capture_lead.php`, com CORS, validacao de formulario e redirect de WhatsApp.
- `GET /api/leads`
- `GET /api/leads?page=1&limit=20&search=nome&status=new&priority=high&source=index`
- `POST /api/leads`
- `GET /api/leads/:id`
- `PUT /api/leads/:id`
- `GET /api/leads/:id/whatsapp`
- `POST /api/leads/:id/assign`
- `POST /api/leads/:id/claim`
- `POST /api/leads/:id/interactions`
- `GET /api/leads/stats`

Vendas:

- `GET /api/sales`
- `GET /api/sales?page=1&limit=20&search=contrato&status=confirmed&seller_id=10&period=month`
- `POST /api/sales`
- `POST /api/sales/convert`
- `GET /api/sales/report`
- `GET /api/sales/:id`
- `PUT /api/sales/:id`
- `POST /api/sales/:id/cancel`
- `GET /api/sales/stats`

Usuarios:

- `GET /api/users`
- `GET /api/users?status=active&role=seller,manager&search=nome`
- `POST /api/users`
- `GET /api/users/:id`
- `PUT /api/users/:id`
- `DELETE /api/users/:id` inativa o usuario, sem exclusao fisica.
- `POST /api/users/:id/toggle-status`

Perfil:

- `GET /api/profile`
- `PUT /api/profile`

Comissoes:

- `GET /api/commission-settings`
- `POST /api/commission-settings`
- `GET /api/commission/settings`
- `PUT /api/commission/settings/:sellerId`
- `GET /api/commission-reports`
- `GET /api/commission-reports?year=2026&month=4&seller_id=10`

Configuracoes do site:

- `GET /api/site-config`
- `GET /api/site-config?section=hero`
- `PUT /api/site-config`
- `POST /api/site-config/upload-url` gera URL assinada para upload direto no Supabase Storage.
- `POST /api/site-config/upload-complete` confirma o upload e salva a URL publica em `site_config`.
- `POST /api/site-config/upload` rota legada de compatibilidade para upload pequeno/local.
- `GET /api/faqs`
- `POST /api/faqs`
- `PUT /api/faqs/:id`
- `DELETE /api/faqs/:id`
- `PUT /api/faqs/reorder`

Configuracoes do sistema:

- `GET /api/system-settings`
- `GET /api/system-settings?setting=default_commission_rate`
- `PUT /api/system-settings`
- `POST /api/system-settings`

Auditoria:

- `GET /api/audit-logs`
- `GET /api/audit-logs?page=1&limit=50&search=login&action=LOGIN_SUCCESS`
- Escrita automatica em `audit_logs` para login/logout, perfil, usuarios, leads, vendas, site config, FAQs e configuracoes do sistema.

## Telas implementadas

- `/login` com formulario funcional e cookie HttpOnly via API de login.
- `/dashboard` com layout autenticado, sidebar por perfil e cards alimentados por `/api/dashboard/stats`.
- `/leads` com cards, busca, filtros, paginacao, detalhes, historico, registro de interacoes, assumir lead sem responsavel, vendas relacionadas, criacao e edicao.
- `/sales` com cards, busca, filtros por status/vendedor/periodo, paginacao, detalhes, criacao, edicao e cancelamento controlado.
- `/users` com cards, busca, filtros por funcao/status, detalhes, criacao, edicao e inativacao segura.
- `/profile` com dados da conta, estatisticas por perfil, edicao de nome/email e troca de senha.
- `/commission-settings` com lista por vendedor, leitura para manager/admin e edicao apenas para admin.
- `/commission-reports` com resumo anual/mensal de comissoes por vendedor e detalhe do mes selecionado.
- `/site-config` com abas por secao, edicao dos valores existentes em `site_config`, upload de imagens/videos e CRUD de FAQs para admin.
- `/system-settings` com configuracoes globais do sistema para admin, incluindo taxa padrao de comissao.
- `/audit-logs` com estatisticas, busca, filtro por acao e paginacao para manager/admin.

## Observacoes locais

- As chaves `NEXT_PUBLIC_SUPABASE_ANON_KEY` e `SUPABASE_SERVICE_ROLE_KEY` ainda estao como placeholder neste `.env.local`.
- Por isso, as APIs server-side de Auth usam PostgreSQL direto via `pg` e queries parametrizadas. O cliente Supabase segue configurado para quando as chaves reais forem adicionadas.
- Na Vercel, uploads do Site Config usam URL assinada e envio direto do navegador para o Supabase Storage via `SITE_ASSETS_BUCKET`. Filesystem local da Vercel nao e persistente.
- O aviso de hidratacao visto no dev server com atributos como `id="btc_aprume"` e `cz-shortcut-listen` vem de extensao do navegador, nao do codigo do app. Confirme abrindo em janela anonima sem extensoes, outro perfil do Chrome ou Chrome headless limpo. Em teste limpo, `/dashboard` hidrata sem esse elemento.
- Checklist de deploy: [`DEPLOY_CHECKLIST.md`](./DEPLOY_CHECKLIST.md).
- Estrategia de hospedagem: [`HOSTING_STRATEGY.md`](./HOSTING_STRATEGY.md).
- Mapa legado vs Next: [`LEGACY_GAP_ANALYSIS.md`](./LEGACY_GAP_ANALYSIS.md).

## Proximas fases

1. Revisar fluxos reais com usuarios antes de virar a chave.
2. Expandir o smoke test para cobrir criacao/edicao em um banco de staging.
3. Confirmar no staging que o `.htaccess` bloqueia scripts/debug PHP.
4. Preparar um banco de staging ou janela controlada para os primeiros testes operacionais.
