-- ========================================
-- MIGRAÇÃO SIMPLES PARA SISTEMA DE COMISSÕES
-- Execute este SQL no phpMyAdmin
-- ========================================

-- 1. Adicionar colunas na tabela sales (se não existirem)
ALTER TABLE sales
ADD COLUMN commission_installments INT DEFAULT 5 AFTER commission_value,
ADD COLUMN monthly_commission DECIMAL(10,2) AFTER commission_installments;

-- 2. Atualizar comissão padrão
ALTER TABLE sales
MODIFY COLUMN commission_percentage DECIMAL(5,2) DEFAULT 1.50;

-- 3. Corrigir registros existentes
UPDATE sales
SET commission_percentage = 1.50
WHERE commission_percentage = 0.00 OR commission_percentage IS NULL;

UPDATE sales
SET commission_installments = 5
WHERE commission_installments IS NULL;

UPDATE sales
SET monthly_commission = commission_value / commission_installments
WHERE monthly_commission IS NULL
  AND commission_value IS NOT NULL
  AND commission_installments > 0;

-- 4. Criar tabela de configurações por vendedor
CREATE TABLE seller_commission_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    commission_percentage DECIMAL(5,2) DEFAULT 1.50,
    commission_installments INT DEFAULT 5,
    min_sale_value DECIMAL(12,2) DEFAULT 0.00,
    max_sale_value DECIMAL(12,2) NULL,
    bonus_percentage DECIMAL(5,2) DEFAULT 0.00,
    bonus_threshold DECIMAL(12,2) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    UNIQUE KEY unique_seller (seller_id),
    INDEX idx_seller_id (seller_id),
    INDEX idx_is_active (is_active),
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 5. Criar configurações padrão para vendedores existentes
INSERT INTO seller_commission_settings (seller_id, commission_percentage, commission_installments, created_by)
SELECT
    u.id as seller_id,
    1.50 as commission_percentage,
    5 as commission_installments,
    (SELECT id FROM users WHERE role = 'admin' LIMIT 1) as created_by
FROM users u
WHERE u.role IN ('seller', 'manager', 'admin')
  AND u.id NOT IN (SELECT seller_id FROM seller_commission_settings);

-- 6. Adicionar configuração do sistema
INSERT INTO system_settings (setting_key, setting_value, description)
VALUES ('default_commission_rate', '1.5', 'Taxa de comissão padrão do sistema (%)')
ON DUPLICATE KEY UPDATE setting_value = '1.5';

-- 7. Criar índice para relatórios (opcional)
CREATE INDEX idx_sales_date_month ON sales (sale_date);

-- ========================================
-- FINALIZADO!
-- Agora você pode acessar commission_settings.php
-- ========================================