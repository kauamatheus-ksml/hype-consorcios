# Estrategia de Hospedagem

A hospedagem definida para o CRM Next.js e a Vercel. O legado PHP deve continuar separado na hospedagem atual.

## Arquitetura Recomendada

```text
crm.hypeconsorcios.com.br -> Vercel / Next.js
www.hypeconsorcios.com.br -> site publico legado PHP
```

Vantagens:

- Deploy independente do legado.
- Rollback simples via Vercel.
- Menor risco de quebrar `.htaccess` e rotas PHP existentes.
- Ambiente correto para Next.js App Router e API Routes.

## Configuracao Vercel

- Root Directory: `crm`
- Framework Preset: Next.js
- Install Command: `npm ci`
- Build Command: `npm run build`
- Output Directory: automatico

O arquivo `crm/vercel.json` ja registra os comandos de install/build.

## Uploads

A Vercel nao persiste arquivos enviados no filesystem local. Alem disso, arquivos grandes nao devem passar pelo corpo da API Route. Por isso, o CRM usa Supabase Storage com URL assinada quando `SITE_ASSETS_BUCKET` esta configurado:

```env
SITE_ASSETS_BUCKET=hype-site-assets
```

Crie esse bucket como publico no Supabase Storage antes de usar `/site-config` em producao. A API do CRM gera uma URL assinada, o navegador envia o arquivo direto para o Storage, e a URL publica final fica salva em `site_config.config_value`.

## Formularios do Site Publico

As paginas `index.php`, `leves.php`, `pesados.php` e `premio.php` continuam usando o endpoint PHP por padrao:

```text
subsystem/api/capture_lead.php
```

Quando o CRM Next estiver publicado, configure antes do script do formulario:

```html
<script>
  window.HYPE_CRM_CAPTURE_ENDPOINT = "https://crm.hypeconsorcios.com.br/api/capture-lead";
</script>
```

A API Next responde CORS e mantem o mesmo contrato de retorno do legado: `success`, `message`, `lead_id` e `redirect_whatsapp`.

## Banco

A app Next usa o PostgreSQL/Supabase existente via `DB_*`. Antes do deploy:

- Confirmar que a Vercel consegue acessar o Supabase.
- Conferir SSL no pooler.
- Nao alterar schema.
- Conferir `/api/health` no ambiente publicado.
- Conferir que `/api/health` mostra `checks.storage: true`.
- Rodar `npm run smoke` contra Preview/Staging.

## Corte

1. Subir Preview na Vercel.
2. Rodar `npm run smoke` contra a URL Preview.
3. Validar login e perfis reais.
4. Validar telas com admin/manager/seller.
5. Apontar `crm.hypeconsorcios.com.br` para a Vercel.
6. Usar por alguns dias em paralelo.
7. Redirecionar o painel administrativo antigo para o CRM Next.
