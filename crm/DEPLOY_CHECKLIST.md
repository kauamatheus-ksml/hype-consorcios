# Checklist de Deploy do CRM Next.js na Vercel

Este checklist cobre a publicacao da app `crm/` na Vercel em paralelo ao legado PHP, sem alterar o schema do Supabase.

## Caminho Rapido: Commit + Push

Depois que o projeto estiver conectado na Vercel uma vez, o fluxo normal passa a ser:

```powershell
git status
git add -A
git commit -m "Deploy CRM Next na Vercel"
git push origin main
```

Se a Vercel estiver acompanhando a branch `main`, esse `push` cria o deploy automaticamente. Antes do primeiro `push` de producao, confirme os itens abaixo.

## 1. Projeto Vercel

- Criar/importar o repositorio na Vercel.
- Repositorio GitHub:

```text
kauamatheus-ksml/hype-consorcios
```

- Production Branch: `main`.
- Definir **Root Directory** como:

```text
crm
```

- Framework Preset: Next.js.
- Output Directory: deixar automatico.
- O arquivo `crm/vercel.json` ja define:

```json
{
  "installCommand": "npm ci",
  "buildCommand": "npm run build"
}
```

- Manter o legado PHP publicado enquanto o CRM Next e validado.

Se o projeto da Vercel ja existir mas estiver apontando para a raiz do repositorio, alterar o Root Directory para `crm` antes do proximo deploy.

## 2. Variaveis

Configurar no ambiente da Vercel:

```env
NEXT_PUBLIC_SUPABASE_URL=
NEXT_PUBLIC_SUPABASE_ANON_KEY=
SUPABASE_SERVICE_ROLE_KEY=
SITE_ASSETS_BUCKET=hype-site-assets
JWT_SECRET=
DB_HOST=
DB_NAME=postgres
DB_USER=
DB_PASS=
DB_PORT=5432
TZ=America/Sao_Paulo
```

Regras:

- `JWT_SECRET` deve ter pelo menos 32 caracteres e precisa ser igual em todas as instancias do CRM.
- `DB_*` deve apontar para o mesmo PostgreSQL/Supabase usado pelo legado.
- `SUPABASE_SERVICE_ROLE_KEY` nunca deve ficar exposta no frontend.
- `SITE_ASSETS_BUCKET` deve apontar para um bucket publico do Supabase Storage.
- `TZ=America/Sao_Paulo` deve ser mantido para reduzir divergencia entre staging, producao e localhost.

## 3. Uploads

A Vercel nao persiste arquivos enviados em filesystem local e tem limite de corpo de requisicao em Functions. Por isso, a tela `/site-config` usa upload direto do navegador para o Supabase Storage com URL assinada. Antes de usar em producao:

- Criar bucket publico no Supabase Storage, por exemplo `hype-site-assets`.
- Configurar `SITE_ASSETS_BUCKET=hype-site-assets` na Vercel.
- Confirmar que `/api/health` mostra `checks.storage: true`.

Fluxo esperado:

- `POST /api/site-config/upload-url` gera a URL assinada.
- O navegador envia o arquivo direto para o bucket.
- `POST /api/site-config/upload-complete` valida o objeto e salva a URL publica em `site_config.config_value`.

## 4. Validacao Antes de Publicar

Local:

```powershell
npm run predeploy
npm run smoke
```

Staging/Preview Vercel:

```powershell
$env:CRM_BASE_URL="https://seu-preview.vercel.app"
npm run smoke
```

Antes do smoke, conferir:

```text
https://seu-preview.vercel.app/api/health
```

O retorno esperado e `success: true`, `checks.env: true`, `checks.database: true` e `checks.storage: true`.

Se o Preview funcionar, o deploy de producao e o mesmo fluxo:

```powershell
git push origin main
```

## 5. Rotas a Conferir

- `/login`
- `/api/health`
- `/dashboard`
- `/leads`
- `/sales`
- `/users`
- `/profile`
- `/commission-settings`
- `/commission-reports`
- `/site-config`
- `/system-settings`
- `/audit-logs`

## 6. Dominio e Corte

- Publicar primeiro em Preview/Staging da Vercel.
- Depois apontar `crm.hypeconsorcios.com.br` para o projeto Vercel.
- Validar login com usuario admin, manager e seller.
- Validar criacao/edicao controlada antes de usar operacionalmente.
- Manter backup do banco antes do primeiro uso operacional.
- Depois da aprovacao, apontar o link administrativo antigo para a nova app.
