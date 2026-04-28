# Prompt para GPT: Setup e Infraestrutura do CRM Hype Consórcios

Atue como um Desenvolvedor Front-end/Full-stack Sênior especialista em Next.js (App Router), TypeScript, Tailwind CSS e Supabase.
Sua missão é iniciar a migração de um subsistema (CRM) legado em PHP para uma arquitetura moderna Next.js, mantendo estritamente as regras de negócio e a integração com um banco de dados PostgreSQL existente no Supabase.

Você está executando a **Fase 0 e Fase 1** de um projeto maior.

## Contexto do Projeto
O sistema é o painel administrativo "Hype Consórcios", que gerencia leads, vendas, comissões e configurações de um site.
- **Identidade Visual:** Fundo escuro elegante (`#242328`), detalhes vibrantes em ciano (`#3be1c9`), fonte 'Inter'.
- **Banco de Dados:** O schema já existe no Supabase (tabelas: `users`, `leads`, `sales`, `site_config`, etc.). VOCÊ NÃO DEVE ALTERAR O SCHEMA, apenas conectar e tipar corretamente.

## Suas Tarefas Imediatas

### 1. Setup Inicial do Projeto
Crie o comando `npx create-next-app` ou forneça as instruções exatas para inicializar o projeto Next.js com as seguintes opções:
- App Router: Sim
- TypeScript: Sim
- Tailwind CSS: Sim
- ESLint: Sim
- Diretório `src/`: Opcional (sua preferência, mas defina uma padrão e siga nela).

Liste as dependências essenciais a serem instaladas:
- `@supabase/supabase-js` (Para comunicação com o DB existente)
- `bcryptjs` (O sistema legado usa hashes bcrypt para senhas)
- `jose` (Para gerenciamento de JWT/Sessões seguras)
- `zustand` (Para gerenciamento de estado global no frontend, ex: dados do usuário logado)
- Biblioteca de componentes de sua escolha (ex: `lucide-react` para ícones, possivelmente `shadcn/ui` se quiser acelerar o UI).

Forneça um modelo do arquivo `.env.local` contendo as variáveis necessárias para o Supabase (URL, Anon Key, Service Role Key) e a JWT Secret.

### 2. Infraestrutura Básica (Crie os arquivos)

Gere o código para os seguintes arquivos fundamentais da Fase 1:

**A. Conexão com o Supabase:**
Crie um utilitário `lib/supabase.ts` (ou similar) que configure o cliente Supabase. Lembre-se que o Supabase já tem os dados e devemos usar o Service Role Key no backend/API routes para contornar RLS (Row Level Security), caso RLS não tenha sido configurado para essa nova API, ou crie a lógica apropriada.

**B. Tipagens (Interfaces TypeScript):**
Crie um arquivo `types/index.ts` (ou separe em vários) com as interfaces principais espelhando o banco de dados:
- `User` (id, username, email, full_name, role, status)
- `Lead` (id, name, phone, vehicle_interest, status, priority, assigned_to)
- `Sale` (id, lead_id, seller_id, sale_value, commission_percentage, status)
- Defina os Enums: `UserRole` ('admin', 'manager', 'seller', 'viewer') e `LeadStatus` ('new', 'contacted', 'negotiating', 'converted', 'lost').

**C. Utilitários de Autenticação:**
Crie `lib/auth.ts` contendo as funções base para substituir a classe `Auth.php` antiga:
- Função para validar/hashear senhas (usando `bcryptjs`).
- Lógica base para gerenciamento de sessão baseada em tokens (usando a lib `jose` ou manipulando cookies no App Router com `next/headers`). O legado usava uma tabela `user_sessions` gerando UUIDs. Defina a melhor abordagem Next.js (ex: JWT em HttpOnly Cookie).
- Função `hasPermission(userRole, requiredRole)` (Hierarquia: viewer < seller < manager < admin).

**D. CSS Base:**
Forneça o arquivo `globals.css` que inclui as variáveis de cor principais herdadas do legado:
```css
:root {
  --primary: #3be1c9;
  --primary-foreground: #242328;
  --background: #ffffff;
  --foreground: #0f172a;
  --dark: #1e293b;
  --gradient-primary: linear-gradient(135deg, #3be1c9 0%, #3be1c9 100%);
}
```

## Formato da Resposta Esperada
Forneça os comandos de terminal, instruções claras e os blocos de código completos e prontos para uso. O código deve ser modular, limpo, fortemente tipado e seguir as melhores práticas do Next.js (App Router). Concentre-se nas **Fases 0 e 1** conforme descrito acima. Quando terminar, indique os próximos passos (que seria a criação das APIs de Auth e Dashboard).
