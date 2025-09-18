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