# 🚀 Hype Consórcios — Migração PHP → Next.js
## Checklist Completo de Desenvolvimento

> **Objetivo:** Recriar o subsistema administrativo (CRM) em Next.js (App Router) com a mesma identidade visual, conectando ao mesmo banco PostgreSQL no Supabase.

---

## 📌 Notas para a IA

- Marcar `[x]` ao concluir cada item, `[/]` para em progresso
- Adicionar observações após cada item quando necessário
- O banco PostgreSQL Supabase já existe com dados reais — **NÃO alterar schema**
- Manter identidade visual: `--primary: #3be1c9`, `--primary-foreground: #242328`, font Inter
- Conexão Supabase: host `aws-0-us-west-2.pooler.supabase.com`, db `postgres`, port `5432`

---

## FASE 0 — Setup do Projeto Next.js
- [x] Criar projeto Next.js com App Router dentro de `/crm` (ou pasta dedicada)
- [x] Configurar TypeScript, ESLint
- [x] Instalar dependências: `@supabase/supabase-js`, `bcryptjs`, `jose` (JWT), `zustand`
- [x] Criar `.env.local` com variáveis do Supabase (DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_PORT, NEXT_PUBLIC_SUPABASE_URL, SUPABASE_SERVICE_ROLE_KEY, JWT_SECRET)
- [x] Configurar `next.config.js` (imagens remotas, redirects)
- [x] Criar estrutura base de pastas:
  ```
  crm/
  ├── app/
  │   ├── (auth)/login/
  │   ├── (dashboard)/
  │   │   ├── dashboard/
  │   │   ├── leads/
  │   │   ├── sales/
  │   │   ├── users/
  │   │   ├── profile/
  │   │   ├── site-config/
  │   │   ├── commission-settings/
  │   │   └── audit-logs/
  │   └── api/
  │       ├── auth/
  │       ├── leads/
  │       ├── sales/
  │       ├── users/
  │       ├── dashboard/
  │       ├── site-config/
  │       ├── faq/
  │       ├── commission/
  │       ├── audit-logs/
  │       └── capture-lead/
  ├── components/
  ├── lib/
  ├── hooks/
  ├── types/
  └── styles/
  ```

---

## FASE 1 — Infraestrutura (lib/, types/, styles/)

### 1.1 Conexão com Banco de Dados
- [x] `lib/db.ts` — Cliente Supabase (createClient server-side com service role key)
- [x] `lib/db-direct.ts` — Pool de conexão direta PostgreSQL via `pg` para queries complexas
- [x] Testar conexão com query simples `SELECT 1`

### 1.2 Tipos TypeScript
- [x] `types/user.ts` — Interface User (id, username, email, password_hash, full_name, role, status, created_at, updated_at, last_login, created_by)
- [x] `types/lead.ts` — Interface Lead (id, name, email, phone, vehicle_interest, has_down_payment, down_payment_value, source_page, ip_address, user_agent, status, priority, notes, assigned_to, created_at, updated_at, contacted_at)
- [x] `types/sale.ts` — Interface Sale (id, lead_id, seller_id, sale_value, commission_percentage, commission_value, commission_installments, monthly_commission, vehicle_sold, payment_type, down_payment, financing_months, monthly_payment, contract_number, notes, status, sale_date, created_at, updated_at, created_by)
- [x] `types/lead-interaction.ts` — Interface LeadInteraction
- [x] `types/audit-log.ts` — Interface AuditLog
- [x] `types/site-config.ts` — Interface SiteConfig
- [x] `types/faq.ts` — Interface FAQ
- [x] `types/commission-settings.ts` — Interface SellerCommissionSettings
- [x] `types/enums.ts` — Enums: UserRole (admin|manager|seller|viewer), LeadStatus (new|contacted|negotiating|converted|lost), LeadPriority (low|medium|high|urgent), SaleStatus (pending|confirmed|cancelled), InteractionType (call|whatsapp|email|meeting|note|status_change)

### 1.3 Design System (CSS)
- [x] `styles/globals.css` — CSS variables extraídas do style.css atual:
  - Cores: `--primary: #3be1c9`, `--primary-foreground: #242328`, `--dark: #1e293b`
  - Gradientes, sombras, transições, tipografia (Inter)
- [ ] `styles/dashboard.css` — Layout do painel (sidebar + conteúdo)
- [ ] `styles/components.css` — Cards, tabelas, modais, badges, formulários
- [x] Importar Google Fonts Inter no layout

### 1.4 Autenticação
- [x] `lib/auth.ts` — Funções: hashPassword, verifyPassword (bcryptjs), generateToken, verifyToken (jose/JWT)
- [x] `lib/session.ts` — Funções: createSession (insere em user_sessions), validateSession (valida cookie `crm_session`), destroySession
- [x] `middleware.ts` — Middleware Next.js para proteger rotas `/dashboard/*`, redirecionar para `/login` se não autenticado
- [x] Hierarquia de roles: viewer(1) < seller(2) < manager(3) < admin(4)
- [x] Função `hasPermission(userRole, requiredRole)` replicando a lógica do Auth.php

---

## FASE 2 — API Routes (Backend Next.js)

### 2.1 Auth API
- [x] `api/auth/login/route.ts` — POST: validar username/email + senha, criar sessão, setar cookie `crm_session`, retornar user data
- [x] `api/auth/logout/route.ts` — POST: deletar sessão do DB, limpar cookie
- [x] `api/auth/validate/route.ts` — GET: validar sessão atual, retornar user data
- [x] `api/auth/change-password/route.ts` — POST: verificar senha atual, atualizar hash, invalidar sessões

### 2.2 Dashboard API
- [x] `api/dashboard/stats/route.ts` — GET: replicar lógica do `dashboard_stats_simple.php`
  - Total vendas, receita, comissões, leads, taxa conversão
  - Filtro por seller_id (admin), dados próprios (seller)
  - Top vendedores, leads por fonte, vendas mensais (6 meses), leads por status, leads recentes

### 2.3 Leads API
- [x] `api/leads/route.ts` — GET: listar leads com filtros (status, priority, assigned_to, date_from, date_to, search), paginação
- [x] `api/leads/route.ts` — POST: criar lead manualmente
- [x] `api/leads/[id]/route.ts` — GET: lead por ID com interações
- [x] `api/leads/[id]/route.ts` — PUT: atualizar lead (campos permitidos: name, email, phone, vehicle_interest, has_down_payment, down_payment_value, status, priority, notes, assigned_to)
- [x] `api/leads/[id]/assign/route.ts` — POST: atribuir lead a vendedor
- [x] `api/leads/[id]/interactions/route.ts` — GET: listar interações, POST: adicionar interação
- [x] `api/leads/[id]/whatsapp/route.ts` — GET: gerar URL WhatsApp com mensagem personalizada
- [x] `api/leads/stats/route.ts` — GET: estatísticas de leads por status e período

### 2.4 Sales API
- [x] `api/sales/route.ts` — GET: listar vendas com filtros (status, seller_id, date_from, date_to, search), paginação
- [x] `api/sales/convert/route.ts` — POST: converter lead em venda (transação: criar sale + atualizar lead status + registrar interação)
- [x] `api/sales/[id]/route.ts` — GET: venda por ID, PUT: atualizar venda (recalcular comissão automaticamente)
- [x] `api/sales/[id]/cancel/route.ts` — POST: cancelar venda (reverter lead para negotiating)
- [x] `api/sales/report/route.ts` — GET: relatório por vendedor
- [x] `api/sales/stats/route.ts` — GET: estatísticas de vendas

### 2.5 Users API
- [x] `api/users/route.ts` — GET: listar usuários com filtros (role, status), POST: criar usuário (hash senha, validar duplicidade)
- [x] `api/users/[id]/route.ts` — GET: user por ID, PUT: atualizar user (role, status, full_name, email)
- [x] `api/users/[id]/toggle-status/route.ts` — POST: ativar/desativar usuário

### 2.6 Profile API
- [x] `api/profile/route.ts` — GET: dados do perfil + estatísticas, PUT: atualizar perfil (com audit log)

### 2.7 Site Config API
- [x] `api/site-config/route.ts` — GET: listar configs por seção, PUT: atualizar config (chave-valor)
- [x] `api/site-config/upload/route.ts` — POST: upload de imagens/vídeos para `assets/images/admin/`

### 2.8 FAQ API
- [x] `api/faq/route.ts` — GET: listar FAQs, POST: criar FAQ
- [x] `api/faq/[id]/route.ts` — PUT: atualizar FAQ, DELETE: remover FAQ
- [x] `api/faq/reorder/route.ts` — PUT: reordenar FAQs

### 2.9 Commission API
- [x] `api/commission/settings/route.ts` — GET: configurações globais e por vendedor
- [x] `api/commission/settings/[sellerId]/route.ts` — PUT: atualizar config de comissão do vendedor
- [x] `api/commission/report/route.ts` — GET: relatório de comissões

### 2.10 Audit Logs API
- [x] `api/audit-logs/route.ts` — GET: listar logs com filtros (user_id, action, date_from, date_to), paginação

### 2.11 Capture Lead (Público)
- [x] `api/capture-lead/route.ts` — POST: captura de lead público (sem autenticação), validação de telefone, prevenção de duplicados em 24h, geração de URL WhatsApp

---

## FASE 3 — Componentes UI Reutilizáveis

### 3.1 Layout
- [ ] `components/Sidebar.tsx` — Sidebar com navegação baseada em role (replicar sidebar.php):
  - Admin: Dashboard, Leads, Vendas, Usuários, Comissões, Config Site, Audit Logs, Perfil
  - Manager: Dashboard, Leads, Vendas, Comissões, Perfil
  - Seller: Dashboard, Leads, Vendas, Perfil
  - Viewer: Dashboard, Perfil
- [ ] `components/Header.tsx` — Header com nome do usuário, role badge, botão logout
- [ ] `components/DashboardLayout.tsx` — Layout wrapper (sidebar + header + content area)

### 3.2 UI Primitivos
- [ ] `components/ui/Card.tsx` — Card com variantes (default, stat, elevated)
- [ ] `components/ui/Button.tsx` — Botão com variantes (primary, outline, danger, success/cta)
- [ ] `components/ui/Badge.tsx` — Badge para status (new=azul, contacted=amarelo, negotiating=laranja, converted=verde, lost=vermelho)
- [ ] `components/ui/Modal.tsx` — Modal reutilizável com overlay
- [ ] `components/ui/Table.tsx` — Tabela responsiva com suporte a ordenação
- [ ] `components/ui/Input.tsx` — Input, Select, Textarea estilizados
- [ ] `components/ui/Pagination.tsx` — Componente de paginação
- [ ] `components/ui/SearchFilter.tsx` — Barra de busca com filtros
- [ ] `components/ui/StatCard.tsx` — Card de estatística (ícone, valor, label, trend)
- [ ] `components/ui/LoadingSpinner.tsx` — Spinner/skeleton de carregamento
- [ ] `components/ui/Toast.tsx` — Notificações toast (sucesso, erro, info)

### 3.3 Componentes de Domínio
- [ ] `components/leads/LeadTable.tsx` — Tabela de leads com ações inline
- [ ] `components/leads/LeadModal.tsx` — Modal de detalhes/edição de lead
- [ ] `components/leads/LeadFilters.tsx` — Filtros de leads (status, prioridade, vendedor, data)
- [ ] `components/leads/InteractionTimeline.tsx` — Timeline de interações do lead
- [ ] `components/sales/SaleTable.tsx` — Tabela de vendas
- [ ] `components/sales/SaleModal.tsx` — Modal de detalhes/edição de venda
- [ ] `components/sales/ConvertLeadModal.tsx` — Modal de conversão lead → venda
- [ ] `components/sales/SaleFilters.tsx` — Filtros de vendas
- [ ] `components/users/UserTable.tsx` — Tabela de usuários
- [ ] `components/users/UserModal.tsx` — Modal criar/editar usuário
- [ ] `components/dashboard/StatsGrid.tsx` — Grid de cards de estatísticas
- [ ] `components/dashboard/ChartCard.tsx` — Card com gráfico (vendas mensais, leads por fonte)
- [ ] `components/dashboard/RecentLeads.tsx` — Lista de leads recentes
- [ ] `components/dashboard/TopSellers.tsx` — Ranking de vendedores

---

## FASE 4 — Páginas do Sistema

### 4.1 Login
- [x] `app/(auth)/login/page.tsx` — Tela de login com:
  - Campos: username/email + senha + checkbox "Lembrar-me"
  - Identidade visual Hype (fundo escuro #242328, logo, gradiente #3be1c9)
  - Validação client-side, mensagens de erro
  - Redirect para /dashboard após login

### 4.2 Dashboard
- [x] `app/(dashboard)/dashboard/page.tsx` — Dashboard principal:
  - Cards: Total Leads, Total Vendas, Receita Total, Comissões, Taxa de Conversão, Vendas Pendentes
  - Filtro por vendedor (apenas admin)
  - Gráfico de vendas mensais (últimos 6 meses)
  - Leads por fonte (pizza/donut)
  - Leads por status (barras)
  - Top vendedores do mês
  - Leads recentes (tabela resumida)

### 4.3 Leads
- [x] `app/(dashboard)/leads/page.tsx` — Gerenciamento de leads:
  - Tabela com colunas: Nome, Telefone, Email, Veículo, Status (badge), Prioridade, Vendedor, Data
  - Filtros: busca, status, prioridade, fonte, data
  - Ações: ver detalhes, editar, atribuir vendedor, WhatsApp
  - Modal de detalhes com timeline de interações
  - Formulário de adicionar interação (tipo, descrição, resultado)
  - Botão WhatsApp que gera URL personalizada
  - Paginação

### 4.4 Vendas
- [x] `app/(dashboard)/sales/page.tsx` — Gerenciamento de vendas:
  - Cards resumo: Total vendas, Valor confirmado, Comissões, Pendentes
  - Tabela com: Cliente, Veículo, Valor, Comissão, Vendedor, Status, Data
  - Filtros: busca, status, vendedor, período
  - Modal de detalhes com todas as informações
  - Ação de cancelar venda
  - Converter lead em venda (modal com campos: vendedor, valor, veículo, tipo pagamento, entrada, parcelas, contrato, comissão%)

### 4.5 Usuários (Admin/Manager)
- [x] `app/(dashboard)/users/page.tsx` — Gestão de usuários:
  - Tabela: Nome, Username, Email, Role (badge), Status, Último Login, Criado Por
  - Criar novo usuário (modal com: username, email, senha, nome completo, role)
  - Editar usuário (role, status)
  - Toggle ativar/desativar

### 4.6 Perfil
- [x] `app/(dashboard)/profile/page.tsx` — Perfil do usuário:
  - Dados pessoais editáveis (nome, email)
  - Alterar senha (senha atual + nova + confirmação)
  - Estatísticas pessoais (leads atribuídos, vendas, comissões)
  - Audit log pessoal

### 4.7 Configurações do Site (Admin)
- [x] `app/(dashboard)/site-config/page.tsx` — Config do site por seções:
  - Seção Hero: título, subtítulo, vídeo, logo
  - Seção About: títulos, textos
  - Seção Veículos: preços, descrições, imagens (leves, premium, pesados)
  - Seção FAQ: gerenciar perguntas (CRUD + reordenar)
  - Seção Localização: título, subtítulo
  - Seção Clientes: título, subtítulo, imagens (10 slots)
  - Seção Carreira: título, subtítulo, imagem
  - Seção Meta/SEO: title, description, keywords, og_image
  - Seção Empresa: nome, telefone, whatsapp, instagram, endereço, CNPJ
  - Upload de imagens/vídeos com preview

### 4.8 Configurações de Comissão (Admin)
- [x] `app/(dashboard)/commission-settings/page.tsx` — Comissões:
  - Config global padrão (% comissão, parcelas)
  - Config individual por vendedor
  - Campos: commission_percentage, commission_installments, min_sale_value, max_sale_value, bonus_percentage, bonus_threshold
  - Relatório de comissões

### 4.9 Audit Logs (Admin)
- [x] `app/(dashboard)/audit-logs/page.tsx` — Logs de auditoria:
  - Tabela: Usuário, Ação, Descrição, IP, Data
  - Filtros: usuário, ação, período
  - Detalhes expandíveis (old_values, new_values como JSON)
  - Paginação

---

## FASE 5 — Hooks e Utilidades

- [ ] `hooks/useAuth.ts` — Hook de autenticação (user, isLoading, login, logout)
- [ ] `hooks/useLeads.ts` — Hook para CRUD de leads com SWR/React Query
- [ ] `hooks/useSales.ts` — Hook para CRUD de vendas
- [ ] `hooks/useUsers.ts` — Hook para CRUD de usuários
- [ ] `hooks/useDashboard.ts` — Hook para stats do dashboard
- [x] `lib/utils.ts` — Funções utilitárias:
  - `formatCurrency(value)` — Formatar R$ brasileiro
  - `formatPhone(phone)` — Formatar telefone (XX) XXXXX-XXXX
  - `formatDate(date)` — Formatar data pt-BR
  - `generateWhatsAppURL(lead)` — Gerar URL WhatsApp
  - `getStatusColor(status)` — Cor do badge por status
  - `getRoleName(role)` — Nome legível do role

---

## FASE 6 — Integração e Testes

- [x] Testar login/logout com usuários existentes no banco
- [x] Testar CRUD de leads (criar, listar, filtrar, atualizar, atribuir)
- [x] Testar conversão lead → venda (transação completa)
- [x] Testar cálculo automático de comissões
- [x] Testar upload de imagens no site-config
- [x] Testar CRUD de FAQs com reordenação
- [x] Testar permissões por role (admin vê tudo, seller vê só seus dados)
- [x] Testar captura pública de lead (endpoint sem auth)
- [x] Testar WhatsApp URL generation
- [x] Validar responsividade mobile em todas as páginas
- [x] Verificar timezone America/Sao_Paulo em todas as datas

---

## FASE 7 — Deploy e Migração

- [ ] Configurar variáveis de ambiente no Vercel
- [ ] Deploy inicial no Vercel
- [ ] Testar todas as funcionalidades em produção
- [ ] Configurar domínio/subdomínio para o CRM
- [ ] Redirecionar `/subsystem` antigo para nova URL
- [x] Documentar novas rotas e endpoints

---

## 📊 Referência: Schema do Banco (NÃO ALTERAR)

### Tabelas existentes:
| Tabela | Descrição |
|--------|-----------|
| `users` | Usuários (admin, manager, seller, viewer) |
| `leads` | Leads capturados |
| `lead_interactions` | Histórico de interações |
| `sales` | Vendas convertidas |
| `seller_commission_settings` | Config comissão por vendedor |
| `user_sessions` | Sessões ativas |
| `audit_logs` | Logs de auditoria |
| `site_config` | Config do site (chave-valor por seção) |
| `faqs` | Perguntas frequentes |
| `system_settings` | Config do sistema |

### Views existentes:
- `leads_detailed` — Leads com dados do vendedor atribuído
- `leads_summary` — Contagem de leads por status
- `sales_by_seller` — Vendas agrupadas por vendedor

---

## 📎 Referência: Identidade Visual

```css
--primary: #3be1c9;
--primary-foreground: #242328;
--background: #ffffff;
--foreground: #0f172a;
--dark: #1e293b;
--gradient-primary: linear-gradient(135deg, #3be1c9, #3be1c9);
--gradient-hero: linear-gradient(135deg, #242328, #1E1E1E);
--font-family: 'Inter', sans-serif;
```

### Status Colors:
- `new` → Azul (#3b82f6)
- `contacted` → Amarelo (#eab308)
- `negotiating` → Laranja (#f97316)
- `converted` → Verde (#22c55e)
- `lost` → Vermelho (#ef4444)
