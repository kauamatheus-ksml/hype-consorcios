# 📊 Regras de Visibilidade das Estatísticas no Perfil

## 🔐 Controle de Acesso por Role

### **👑 ADMIN**
- **Vê:** Estatísticas **GLOBAIS** de todo o sistema
- **Leads:** Todos os leads do sistema
- **Vendas:** Todas as vendas (qualquer vendedor)
- **Valores:** Valores totais de todo o sistema

### **🎯 MANAGER** 
- **Vê:** Estatísticas **GLOBAIS** (mesmo que admin)
- **Leads:** Todos os leads do sistema
- **Vendas:** Todas as vendas (qualquer vendedor)
- **Valores:** Valores totais de todo o sistema

### **💼 SELLER (Vendedor)**
- **Vê:** Apenas suas **PRÓPRIAS** estatísticas
- **Leads:** Somente leads atribuídos a ele
- **Vendas:** Somente suas próprias vendas
- **Valores:** Apenas valores de suas vendas

### **👁️ VIEWER (Visualizador)**
- **Vê:** Apenas estatísticas **LIMITADAS**
- **Leads:** Somente leads atribuídos a ele
- **Vendas:** **NÃO VÊ** estatísticas de vendas
- **Valores:** **NÃO VÊ** valores monetários

## 📋 Estatísticas Exibidas

| Estatística | Admin | Manager | Seller | Viewer |
|-------------|-------|---------|---------|--------|
| Total de Leads | ✅ Todos | ✅ Todos | ✅ Próprios | ✅ Próprios |
| Leads Convertidos | ✅ Todos | ✅ Todos | ✅ Próprios | ✅ Próprios |
| Total de Vendas | ✅ Todas | ✅ Todas | ✅ Próprias | ❌ Não vê |
| Valor Total | ✅ Global | ✅ Global | ✅ Próprio | ❌ Não vê |
| Vendas Este Mês | ✅ Todas | ✅ Todas | ✅ Próprias | ❌ Não vê |
| Valor Mensal | ✅ Global | ✅ Global | ✅ Próprio | ❌ Não vê |

## 🎯 Exemplo de Dados por Role

### Se você é **ADMIN/MANAGER**:
```
50 Total de Leads (todos do sistema)
12 Leads Convertidos (todos do sistema)  
8 Total de Vendas (de todos os vendedores)
R$ 450.000,00 Valor Total (todas as vendas)
3 Vendas Este Mês (de todos os vendedores)
R$ 180.000,00 Valor Mensal (todas as vendas)
```

### Se você é **SELLER**:
```
8 Total de Leads (apenas seus)
3 Leads Convertidos (apenas seus)
2 Total de Vendas (apenas suas)
R$ 85.000,00 Valor Total (apenas suas vendas)
1 Vendas Este Mês (apenas suas)
R$ 45.000,00 Valor Mensal (apenas suas vendas)
```

### Se você é **VIEWER**:
```
5 Total de Leads (apenas atribuídos a você)
1 Leads Convertidos (apenas seus)
[NÃO VÊ estatísticas de vendas/valores]
```

## 🔍 Implementação Técnica

A lógica está implementada em `profile.php` linha 64-174:
- Switch statement baseado no `$userData['role']`
- Queries SQL diferentes para cada role
- Controle de exibição no HTML via `isset($stats['campo'])`

## ⚡ Performance

- **Admin/Manager:** 6 queries SQL (dados globais)
- **Seller:** 6 queries SQL (dados próprios)
- **Viewer:** 2 queries SQL (apenas leads)

---
*Documentação gerada automaticamente - Hype Consórcios CRM*