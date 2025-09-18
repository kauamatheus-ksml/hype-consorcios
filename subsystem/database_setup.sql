-- ====================================
-- ESTRUTURA DO BANCO DE DADOS
-- Sistema de CRM - Hype Consórcios
-- ====================================

-- Tabela de usuários do sistema
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'manager', 'seller', 'viewer') DEFAULT 'viewer',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    created_by INT,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabela de leads capturados
CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    vehicle_interest VARCHAR(200),
    has_down_payment ENUM('yes', 'no'),
    down_payment_value DECIMAL(10,2) NULL,
    source_page VARCHAR(100) DEFAULT 'index',
    ip_address VARCHAR(45),
    user_agent TEXT,
    status ENUM('new', 'contacted', 'negotiating', 'converted', 'lost') DEFAULT 'new',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    notes TEXT,
    assigned_to INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    contacted_at TIMESTAMP NULL,
    INDEX idx_name (name),
    INDEX idx_phone (phone),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at),
    INDEX idx_assigned_to (assigned_to),
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabela de vendas/conversões
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    seller_id INT NOT NULL,
    sale_value DECIMAL(12,2),
    commission_percentage DECIMAL(5,2) DEFAULT 1.50,
    commission_value DECIMAL(10,2),
    commission_installments INT DEFAULT 5,
    monthly_commission DECIMAL(10,2),
    vehicle_sold VARCHAR(200),
    payment_type VARCHAR(50),
    down_payment DECIMAL(10,2),
    financing_months INT,
    monthly_payment DECIMAL(10,2),
    contract_number VARCHAR(50),
    notes TEXT,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    INDEX idx_lead_id (lead_id),
    INDEX idx_seller_id (seller_id),
    INDEX idx_status (status),
    INDEX idx_sale_date (sale_date),
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabela de interações/histórico
CREATE TABLE IF NOT EXISTS lead_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    user_id INT NOT NULL,
    interaction_type ENUM('call', 'whatsapp', 'email', 'meeting', 'note', 'status_change') NOT NULL,
    description TEXT,
    result ENUM('positive', 'neutral', 'negative', 'no_answer') NULL,
    next_contact_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lead_id (lead_id),
    INDEX idx_user_id (user_id),
    INDEX idx_interaction_type (interaction_type),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de sessões de usuário
CREATE TABLE IF NOT EXISTS user_sessions (
    id VARCHAR(64) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de configurações do sistema
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    description VARCHAR(200),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    INDEX idx_setting_key (setting_key),
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ====================================
-- DADOS INICIAIS
-- ====================================

-- Usuário administrador padrão (senha: password)
INSERT INTO users (username, email, password_hash, full_name, role, status) 
VALUES ('admin', 'admin@hypeconsorcios.com.br', '$2y$10$8K1p/wjAZsCR4WOQfMVrdOb0SJmQOi5DPF.D6/VQVKhSFFLQOJFrm', 'Administrador', 'admin', 'active')
ON DUPLICATE KEY UPDATE username = username;

-- Configurações iniciais do sistema
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('site_name', 'Hype Consórcios', 'Nome do site'),
('whatsapp_default', '5547996862997', 'WhatsApp padrão'),
('timezone', 'America/Sao_Paulo', 'Fuso horário do sistema'),
('lead_retention_days', '365', 'Dias para manter leads no sistema'),
('session_timeout', '28800', 'Timeout de sessão em segundos (8 horas)')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- ====================================
-- VIEWS ÚTEIS
-- ====================================

-- View para relatório de leads por status
CREATE OR REPLACE VIEW leads_summary AS
SELECT 
    status,
    COUNT(*) as total,
    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as last_30_days,
    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as last_7_days
FROM leads 
GROUP BY status;

-- View para relatório de vendas por vendedor
CREATE OR REPLACE VIEW sales_by_seller AS
SELECT 
    u.id as seller_id,
    u.full_name as seller_name,
    COUNT(s.id) as total_sales,
    SUM(s.sale_value) as total_value,
    AVG(s.sale_value) as avg_sale_value,
    SUM(s.commission_value) as total_commission,
    COUNT(CASE WHEN s.sale_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as sales_last_30_days
FROM users u
LEFT JOIN sales s ON u.id = s.seller_id AND s.status = 'confirmed'
WHERE u.role IN ('seller', 'manager', 'admin')
GROUP BY u.id, u.full_name;

-- View para leads com informações do vendedor responsável
CREATE OR REPLACE VIEW leads_detailed AS
SELECT 
    l.*,
    u.full_name as assigned_to_name,
    u.username as assigned_to_username,
    (SELECT COUNT(*) FROM lead_interactions li WHERE li.lead_id = l.id) as interactions_count,
    (SELECT MAX(li.created_at) FROM lead_interactions li WHERE li.lead_id = l.id) as last_interaction,
    (SELECT s.id FROM sales s WHERE s.lead_id = l.id LIMIT 1) as sale_id
FROM leads l
LEFT JOIN users u ON l.assigned_to = u.id;