# Sistema CRM - Hype Consórcios

Sistema completo de gerenciamento de leads e vendas para a Hype Consórcios.

## 🚀 Funcionalidades Implementadas

### ✅ **Backend Completo**
- **Sistema de autenticação** com sessões seguras
- **Captura automática de leads** do formulário do site
- **Gerenciamento completo de leads** com status e interações
- **Sistema de vendas** com conversão de leads
- **Controle de permissões** por níveis de usuário
- **APIs REST** para todas as operações
- **Fuso horário -3h** (Brasil) configurado automaticamente

### ✅ **Estrutura do Banco de Dados**
- **users** - Usuários do sistema (admin, manager, seller, viewer)
- **leads** - Leads capturados do site
- **sales** - Vendas convertidas
- **lead_interactions** - Histórico de interações
- **user_sessions** - Controle de sessões
- **system_settings** - Configurações do sistema

### ✅ **Recursos Avançados**
- **WhatsApp integrado** - Links diretos para contato
- **Relatórios de vendas** por vendedor
- **Dashboard com estatísticas**
- **Sistema de atribuição** de leads
- **Histórico completo** de interações
- **Controle de conversões** lead → venda

## 📁 **Estrutura dos Arquivos**

```
subsystem/
├── config/
│   └── database.php              # Configuração do banco
├── classes/
│   ├── Auth.php                  # Sistema de autenticação
│   ├── LeadManager.php           # Gerenciamento de leads
│   └── SalesManager.php          # Gerenciamento de vendas
├── api/
│   ├── capture_lead.php          # Captura leads do formulário
│   ├── auth.php                  # API de autenticação
│   ├── leads.php                 # API de gerenciamento de leads
│   └── sales.php                 # API de gerenciamento de vendas
├── database_setup.sql            # Estrutura do banco de dados
├── install_database.php         # Instalador do banco
├── test_connection.php           # Teste de conexão
└── README.md                     # Esta documentação
```

## 🔧 **Como Instalar**

### 1. **Configurar Banco de Dados**
```bash
# Acesse: http://seu-site.com/subsystem/install_database.php
# Clique em "Instalar/Atualizar Banco de Dados"
```

### 2. **Verificar Conexão**
```bash
# Acesse: http://seu-site.com/subsystem/test_connection.php
# Verifique se mostra "Conexão Estabelecida com Sucesso"
```

### 3. **Login Inicial**
- **Usuário:** admin
- **Email:** admin@hypeconsorcios.com.br  
- **Senha:** password *(altere imediatamente!)*

## 📊 **Níveis de Usuário e Permissões**

### 🔴 **Admin**
- Acesso total ao sistema
- Criar/editar usuários
- Ver todos os leads e vendas
- Relatórios completos
- Configurações do sistema

### 🟡 **Manager**
- Gerenciar leads de todos os vendedores
- Converter leads em vendas
- Atribuir leads para vendedores
- Ver relatórios de performance
- Criar usuários vendedores

### 🟢 **Seller** 
- Ver apenas seus próprios leads
- Adicionar interações aos leads
- Converter seus leads em vendas
- Ver suas próprias estatísticas

### 🔵 **Viewer**
- Apenas visualizar dados
- Sem permissão de edição

## 🌐 **APIs Disponíveis**

### **Autenticação** (`/api/auth.php`)
```javascript
// Login
POST /api/auth.php?action=login
{
    "username": "admin",
    "password": "password",
    "remember": true
}

// Validar sessão
GET /api/auth.php?action=validate

// Criar usuário
POST /api/auth.php?action=create_user
{
    "username": "vendedor1",
    "email": "vendedor@email.com",
    "password": "senha123",
    "full_name": "Nome do Vendedor",
    "role": "seller"
}
```

### **Leads** (`/api/leads.php`)
```javascript
// Listar leads
GET /api/leads.php?action=list&status=new&page=1

// Ver lead específico
GET /api/leads.php?action=get&id=123

// Atualizar lead
POST /api/leads.php?action=update
{
    "id": 123,
    "status": "contacted",
    "notes": "Cliente interessado"
}

// Atribuir lead
POST /api/leads.php?action=assign
{
    "lead_id": 123,
    "user_id": 456
}

// URL do WhatsApp
GET /api/leads.php?action=whatsapp_url&id=123
```

### **Vendas** (`/api/sales.php`)
```javascript
// Converter lead em venda
POST /api/sales.php?action=convert
{
    "lead_id": 123,
    "seller_id": 456,
    "sale_value": 50000.00,
    "commission_percentage": 3.5,
    "vehicle_sold": "Volkswagen Polo 2024",
    "contract_number": "VW2024001"
}

// Listar vendas
GET /api/sales.php?action=list&status=confirmed

// Relatório de vendas
GET /api/sales.php?action=report&date_from=2024-01-01
```

### **Captura de Leads** (`/api/capture_lead.php`)
```javascript
// Capturar lead do formulário
POST /api/capture_lead.php
{
    "name": "João Silva",
    "phone": "(47) 99999-9999",
    "email": "joao@email.com",
    "vehicle": "Volkswagen Polo",
    "hasDownPayment": "yes",
    "downPayment": "10000.00",
    "source": "index"
}
```

## 📱 **Integração WhatsApp**

O sistema gera automaticamente links do WhatsApp com:
- **Mensagem personalizada** com dados do lead
- **Informações do veículo** de interesse
- **Dados de contato** formatados
- **Status da entrada** disponível
- **Rastreamento** de quando o link foi gerado

**Exemplo de URL gerada:**
```
https://api.whatsapp.com/send/?phone=5547996862997&text=Olá João! 
Sou Maria da Hype Consórcios.
Vi que você demonstrou interesse em: Volkswagen Polo
Posso te ajudar com mais informações?
```

## 📈 **Status dos Leads**

| Status | Descrição | Cor |
|--------|-----------|-----|
| `new` | Lead recém capturado | 🔵 Azul |
| `contacted` | Primeiro contato realizado | 🟡 Amarelo |
| `negotiating` | Em negociação | 🟠 Laranja |
| `converted` | Convertido em venda | 🟢 Verde |
| `lost` | Lead perdido | 🔴 Vermelho |

## 💰 **Controle de Vendas**

### **Conversão de Leads**
- ✅ Conversão automática de lead → venda
- ✅ Cálculo automático de comissões
- ✅ Controle de status (pending, confirmed, cancelled)
- ✅ Histórico completo de alterações
- ✅ Relatórios por vendedor

### **Informações Salvas**
- Valor da venda
- Veículo vendido
- Tipo de pagamento (consórcio, financiamento, etc)
- Valor da entrada
- Número de parcelas
- Valor da parcela mensal
- Número do contrato
- Percentual e valor da comissão
- Vendedor responsável

## 🔒 **Segurança**

- ✅ **Senhas criptografadas** com password_hash()
- ✅ **Sessões seguras** com tokens aleatórios
- ✅ **Validação de permissões** em todas as operações
- ✅ **Sanitização de dados** de entrada
- ✅ **Logs de auditoria** nas interações
- ✅ **IP tracking** para sessões
- ✅ **Limpeza automática** de sessões expiradas

## 📊 **Relatórios e Estatísticas**

### **Dashboard Disponível**
- 📈 Total de leads por status
- 💰 Vendas por vendedor
- 📅 Performance por período  
- 🎯 Taxa de conversão lead → venda
- 📞 Histórico de interações
- 💵 Valores de comissão

### **Relatórios Gerenciais**
- Leads capturados por período
- Performance de vendedores
- Veículos mais procurados
- Origem dos leads (página)
- Tempo médio de conversão

## 🛠 **Configurações do Sistema**

O sistema salva automaticamente:
- **Fuso horário:** -3 horas (Brasil)
- **WhatsApp padrão:** (47) 99686-2997
- **Retenção de leads:** 365 dias
- **Timeout de sessão:** 8 horas
- **Nome do site:** Hype Consórcios

## 🚨 **Funcionalidades Chave**

### ✅ **Para Administradores**
- Criar novos usuários vendedores
- Ver todos os leads do sistema
- Relatórios completos de performance
- Controle de permissões
- Configurações do sistema

### ✅ **Para Gerentes**
- Atribuir leads para vendedores
- Acompanhar performance da equipe
- Converter leads em vendas
- Relatórios de vendas
- Gerenciar equipe comercial

### ✅ **Para Vendedores**
- Ver apenas seus leads atribuídos
- Adicionar interações (ligações, WhatsApp, reuniões)
- Marcar status dos leads
- Converter leads em vendas
- Botão direto para WhatsApp do cliente
- Ver suas próprias estatísticas

### ✅ **Sistema Automatizado**
- **Captura automática** quando cliente preenche formulário
- **Redirecionamento automático** para WhatsApp após captura
- **Fuso horário -3h** aplicado automaticamente
- **Validação de dados** antes de salvar
- **Prevenção de leads duplicados** (mesmo telefone em 24h)
- **Cálculo automático de comissões**

## 🔄 **Fluxo do Sistema**

1. **Cliente acessa o site** e preenche o formulário
2. **Sistema captura** lead automaticamente (com fuso -3h)
3. **Cliente é redirecionado** para WhatsApp da Hype
4. **Lead fica disponível** no sistema para vendedores
5. **Vendedor vê o lead** e pode fazer contato
6. **Botão do WhatsApp** gera mensagem personalizada
7. **Vendedor acompanha** lead através dos status
8. **Quando fecha venda**, converte lead em venda
9. **Sistema calcula comissão** automaticamente
10. **Relatórios mostram** performance completa

## 📞 **Suporte e Configuração**

### **Dados de Conexão Configurados:**
- **Host:** srv406.hstgr.io
- **Banco:** u383946504_hypeconsorcio  
- **Usuário:** u383946504_hypeconsorcio
- **Senha:** Aaku_2004@

### **URLs de Acesso:**
- **Instalador:** `/subsystem/install_database.php`
- **Teste de Conexão:** `/subsystem/test_connection.php`
- **APIs:** `/subsystem/api/`

---

## ✅ **Sistema Completo e Funcional!**

O backend está 100% implementado com todas as funcionalidades solicitadas:

- ✅ **Criação de usuários** pelo admin
- ✅ **Captura automática de leads** do formulário
- ✅ **Redirecionamento para WhatsApp** após captura
- ✅ **Gestão completa de leads** com status e interações
- ✅ **Botões personalizados de WhatsApp** para contato
- ✅ **Sistema de conversão** lead → venda
- ✅ **Controle de fechamento** com nome do vendedor
- ✅ **Permissões por níveis** de usuário
- ✅ **Fuso horário -3h** configurado
- ✅ **Relatórios e estatísticas** completos

**O sistema está pronto para uso imediato!** 🎉