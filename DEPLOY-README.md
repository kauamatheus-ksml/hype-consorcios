# 🚀 Guia de Deploy - Hype Consórcios

## ⚠️ IMPORTANTE: Proteção das Configurações do Cliente

Este projeto possui um **sistema de configurações editáveis pelo cliente** através do painel administrativo. Para evitar que essas configurações sejam perdidas durante deploys automáticos via webhook, siga **obrigatoriamente** este processo.

---

## 🔄 Processo de Deploy Seguro

### 📋 **ANTES de fazer commit/push:**

1. **Fazer backup das configurações atuais:**
   ```bash
   # Via browser
   https://seusite.com/subsystem/backup_configs.php

   # Via linha de comando (se tiver acesso SSH)
   cd /caminho/do/projeto
   php subsystem/backup_configs.php
   ```

2. **Verificar arquivos protegidos:**
   - ✅ Configurações do banco: `subsystem/config/database.php`
   - ✅ Uploads dos clientes: `assets/images/admin/`
   - ✅ Vídeos dos clientes: `assets/videos/admin/`
   - ✅ Backups: `backups/configs/`

### 🚀 **APÓS o deploy automático:**

1. **Executar hook de verificação:**
   ```bash
   https://seusite.com/deploy-hook.php
   ```

2. **Se necessário, restaurar configurações:**
   ```bash
   https://seusite.com/subsystem/restore_configs.php
   ```

---

## 📁 Estrutura de Arquivos Protegidos

```
projeto/
├── .gitignore                     # ✅ Configurado para proteger uploads
├── subsystem/
│   ├── config/database.php       # ❌ NÃO versionar (dados sensíveis)
│   ├── backup_configs.php        # 📦 Script de backup
│   └── restore_configs.php       # 🔄 Script de restauração
├── assets/
│   ├── images/admin/             # ❌ NÃO versionar (uploads do cliente)
│   └── videos/admin/             # ❌ NÃO versionar (uploads do cliente)
├── backups/                      # ❌ NÃO versionar (backups locais)
└── deploy-hook.php               # 🤖 Hook automático pós-deploy
```

---

## 🔧 Configuração do Webhook

### No servidor de hospedagem:

1. **Configurar webhook para executar após git pull:**
   ```bash
   #!/bin/bash
   cd /caminho/do/projeto
   git pull origin main
   php deploy-hook.php
   ```

2. **Ou configurar no painel da hospedagem:**
   - URL do webhook: `https://seusite.com/deploy-hook.php`
   - Executar após deploy automático

---

## 📋 Checklist de Deploy

### ✅ **Antes do Commit:**
- [ ] Backup das configurações criado
- [ ] Arquivo `.gitignore` configurado
- [ ] Dados sensíveis removidos do código
- [ ] Uploads dos clientes preservados

### ✅ **Após o Deploy:**
- [ ] Hook de deploy executado
- [ ] Configurações verificadas no painel
- [ ] Imagens dos clientes intactas
- [ ] Site funcionando normalmente

---

## 🛠️ Scripts Disponíveis

| Script | Função | URL |
|--------|---------|-----|
| `backup_configs.php` | Criar backup das configurações | `/subsystem/backup_configs.php` |
| `restore_configs.php` | Restaurar configurações | `/subsystem/restore_configs.php` |
| `deploy-hook.php` | Hook automático pós-deploy | `/deploy-hook.php` |
| `site-config.php` | Painel de configurações | `/subsystem/site-config.php` |

---

## 🚨 Situações de Emergência

### **Se as configurações foram perdidas:**

1. **Acessar painel de restauração:**
   ```
   https://seusite.com/subsystem/restore_configs.php
   ```

2. **Selecionar backup mais recente e restaurar**

3. **Verificar se tudo voltou ao normal**

### **Se não há backups disponíveis:**

1. **Recriar configurações padrão:**
   ```
   https://seusite.com/subsystem/create_site_config_table.php
   ```

2. **Cliente precisará reconfigurar tudo pelo painel**

---

## 🎯 Responsabilidades

### **👨‍💻 Programador:**
- ✅ Fazer backup antes de qualquer alteração
- ✅ Nunca commitar arquivos de upload
- ✅ Testar deploy em ambiente de desenvolvimento
- ✅ Documentar mudanças que afetem configurações

### **👤 Cliente:**
- ✅ Editar conteúdo apenas pelo painel administrativo
- ✅ Avisar sobre mudanças importantes antes de deploys
- ✅ Manter backups regulares das configurações

---

## 📞 Contato para Suporte

Em caso de problemas com deploy ou perda de configurações:

1. **Verificar logs:** `deploy.log`
2. **Executar diagnóstico:** `debug-config.php`
3. **Contatar suporte técnico** com informações dos logs

---

## 🔐 Segurança

### **Dados protegidos:**
- Configurações do banco de dados
- Uploads dos clientes (imagens/vídeos)
- Backups das configurações
- Sessions e cookies

### **Nunca versionar:**
- Senhas e chaves de API
- Arquivos de upload dos usuários
- Logs e caches
- Dados pessoais dos clientes

---

*Última atualização: $(date)*
*Versão: 1.0*