<?php
$sourceFile = __DIR__ . '/u383946504_hypeconsorcio.sql';
$destFile = __DIR__ . '/supabase_migration.sql';

$schema = <<<SQL
SET session_replication_role = 'replica';

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role VARCHAR(50) DEFAULT 'viewer' CHECK (role IN ('admin','manager','seller','viewer')),
    status VARCHAR(50) DEFAULT 'active' CHECK (status IN ('active','inactive')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    last_login TIMESTAMP WITH TIME ZONE,
    created_by INT REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE system_settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT,
    description VARCHAR(200),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_by INT REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE site_config (
    id SERIAL PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT,
    config_type VARCHAR(50) DEFAULT 'text' CHECK (config_type IN ('text','textarea','image','number','boolean')),
    section VARCHAR(50) NOT NULL,
    display_name VARCHAR(200) NOT NULL,
    description TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE leads (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    vehicle_interest VARCHAR(200),
    has_down_payment VARCHAR(10) CHECK (has_down_payment IN ('yes','no')),
    down_payment_value DECIMAL(10,2),
    source_page VARCHAR(100) DEFAULT 'index',
    ip_address VARCHAR(45),
    user_agent TEXT,
    status VARCHAR(50) DEFAULT 'new' CHECK (status IN ('new','contacted','negotiating','converted','lost')),
    priority VARCHAR(50) DEFAULT 'medium' CHECK (priority IN ('low','medium','high','urgent')),
    notes TEXT,
    assigned_to INT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    contacted_at TIMESTAMP WITH TIME ZONE
);

CREATE TABLE faqs (
    id SERIAL PRIMARY KEY,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    display_order INT DEFAULT 0,
    is_active SMALLINT DEFAULT 1,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE lead_interactions (
    id SERIAL PRIMARY KEY,
    lead_id INT NOT NULL REFERENCES leads(id) ON DELETE CASCADE,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    interaction_type VARCHAR(50) NOT NULL CHECK (interaction_type IN ('call','whatsapp','email','meeting','note','status_change')),
    description TEXT,
    result VARCHAR(50) CHECK (result IN ('positive','neutral','negative','no_answer')),
    next_contact_date DATE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE sales (
    id SERIAL PRIMARY KEY,
    lead_id INT NOT NULL REFERENCES leads(id) ON DELETE CASCADE,
    seller_id INT NOT NULL REFERENCES users(id),
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
    status VARCHAR(50) DEFAULT 'pending' CHECK (status IN ('pending','confirmed','cancelled')),
    sale_date TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    created_by INT REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE seller_commission_settings (
    id SERIAL PRIMARY KEY,
    seller_id INT NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    commission_percentage DECIMAL(5,2) DEFAULT 1.50,
    commission_installments INT DEFAULT 5,
    min_sale_value DECIMAL(12,2) DEFAULT 0.00,
    max_sale_value DECIMAL(12,2),
    bonus_percentage DECIMAL(5,2) DEFAULT 0.00,
    bonus_threshold DECIMAL(12,2),
    is_active SMALLINT DEFAULT 1,
    notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    created_by INT REFERENCES users(id) ON DELETE SET NULL,
    updated_by INT REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE user_sessions (
    id VARCHAR(64) PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    expires_at TIMESTAMP WITH TIME ZONE NOT NULL,
    last_activity TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSONB,
    new_values JSONB,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE VIEW leads_detailed AS 
SELECT l.id, l.name, l.email, l.phone, l.vehicle_interest, l.has_down_payment, l.down_payment_value, l.source_page, l.ip_address, l.user_agent, l.status, l.priority, l.notes, l.assigned_to, l.created_at, l.updated_at, l.contacted_at, u.full_name AS assigned_to_name, u.username AS assigned_to_username, 
(SELECT COUNT(*) FROM lead_interactions li WHERE li.lead_id = l.id) AS interactions_count, 
(SELECT MAX(li.created_at) FROM lead_interactions li WHERE li.lead_id = l.id) AS last_interaction, 
(SELECT s.id FROM sales s WHERE s.lead_id = l.id LIMIT 1) AS sale_id 
FROM leads l 
LEFT JOIN users u ON l.assigned_to = u.id;

CREATE VIEW leads_summary AS 
SELECT status, 
COUNT(*) AS total, 
COUNT(CASE WHEN created_at >= CURRENT_TIMESTAMP - INTERVAL '30 days' THEN 1 END) AS last_30_days, 
COUNT(CASE WHEN created_at >= CURRENT_TIMESTAMP - INTERVAL '7 days' THEN 1 END) AS last_7_days 
FROM leads 
GROUP BY status;

CREATE VIEW sales_by_seller AS 
SELECT u.id AS seller_id, 
u.full_name AS seller_name, 
COUNT(s.id) AS total_sales, 
SUM(s.sale_value) AS total_value, 
AVG(s.sale_value) AS avg_sale_value, 
SUM(s.commission_value) AS total_commission, 
COUNT(CASE WHEN s.sale_date >= CURRENT_TIMESTAMP - INTERVAL '30 days' THEN 1 END) AS sales_last_30_days 
FROM users u 
LEFT JOIN sales s ON u.id = s.seller_id AND s.status = 'confirmed' 
WHERE u.role IN ('seller','manager','admin') 
GROUP BY u.id, u.full_name;

SQL;

file_put_contents($destFile, $schema . "\n\n");

$contents = file_get_contents($sourceFile);
$lines = explode("\n", $contents);
$ins = '';
$inInsert = false;
foreach ($lines as $line) {
    if (strpos($line, 'INSERT INTO') === 0 || $inInsert) {
        $inInsert = true;
        // remove backticks
        $l = str_replace('`', '', $line);
        $l = str_replace("\'", "''", $l);
        // remove escaped double quotes inside JSON
        $l = str_replace('\"', '"', $l);
        $ins .= $l . "\n";
        
        // Stop if we hit a semicolon at the end
        if (substr(trim($l), -1) === ';') {
            $inInsert = false;
            $ins .= "\n";
        }
    }
}

file_put_contents($destFile, $ins, FILE_APPEND);

// Also reset sequences!
$seq_reset = <<<SQL
SELECT setval('users_id_seq', (SELECT MAX(id) FROM users));
SELECT setval('system_settings_id_seq', (SELECT MAX(id) FROM system_settings));
SELECT setval('site_config_id_seq', (SELECT MAX(id) FROM site_config));
SELECT setval('leads_id_seq', (SELECT MAX(id) FROM leads));
SELECT setval('faqs_id_seq', (SELECT MAX(id) FROM faqs));
SELECT setval('lead_interactions_id_seq', (SELECT MAX(id) FROM lead_interactions));
SELECT setval('sales_id_seq', (SELECT MAX(id) FROM sales));
SELECT setval('seller_commission_settings_id_seq', (SELECT MAX(id) FROM seller_commission_settings));
SELECT setval('audit_logs_id_seq', (SELECT MAX(id) FROM audit_logs));
SQL;

file_put_contents($destFile, "\n\n" . $seq_reset, FILE_APPEND);
echo "Generated supabase_migration.sql successfully.\n";
