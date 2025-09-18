-- Migration script para adicionar campos de comissão
-- Execute apenas se os campos ainda não existirem

-- Adicionar campos de comissão à tabela sales
ALTER TABLE sales
ADD COLUMN IF NOT EXISTS commission_installments INT DEFAULT 5 AFTER commission_value,
ADD COLUMN IF NOT EXISTS monthly_commission DECIMAL(10,2) AFTER commission_installments;

-- Atualizar comissão padrão para 1.5%
ALTER TABLE sales
MODIFY COLUMN commission_percentage DECIMAL(5,2) DEFAULT 1.50;

-- Atualizar registros existentes que não têm comissão configurada
UPDATE sales
SET commission_percentage = 1.50
WHERE commission_percentage = 0.00 OR commission_percentage IS NULL;

UPDATE sales
SET commission_installments = 5
WHERE commission_installments IS NULL;

-- Calcular monthly_commission para registros existentes
UPDATE sales
SET monthly_commission = CASE
    WHEN commission_value IS NOT NULL AND commission_installments > 0
    THEN commission_value / commission_installments
    ELSE NULL
END
WHERE monthly_commission IS NULL;

-- Adicionar configuração padrão de comissão no sistema
INSERT INTO system_settings (setting_key, setting_value, description)
VALUES ('default_commission_rate', '1.5', 'Taxa de comissão padrão do sistema (%)')
ON DUPLICATE KEY UPDATE setting_value = '1.5';

-- Criar índice para otimizar consultas por data para relatórios mensais
CREATE INDEX IF NOT EXISTS idx_sales_date_month ON sales (YEAR(sale_date), MONTH(sale_date));

-- Criar tabela de configurações de comissão por vendedor
CREATE TABLE IF NOT EXISTS seller_commission_settings (
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

-- Criar configurações padrão para vendedores existentes
INSERT INTO seller_commission_settings (seller_id, commission_percentage, commission_installments, created_by)
SELECT
    id as seller_id,
    1.50 as commission_percentage,
    5 as commission_installments,
    (SELECT id FROM users WHERE role = 'admin' LIMIT 1) as created_by
FROM users
WHERE role IN ('seller', 'manager')
AND id NOT IN (SELECT seller_id FROM seller_commission_settings);