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


INSERT INTO audit_logs VALUES
(2,10,'PROFILE_UPDATE','users',10,'{"id":10,"username":"Gerente Luan","email":"gerentevendas@hypeconsorcios.com.br","password_hash":"[REDACTED]","full_name":"Luan Robson","role":"manager","status":"active","created_at":"2025-09-16 15:33:29","updated_at":"2025-09-18 10:53:16","last_login":"2025-09-18 10:53:16","created_by":null}','{"id":10,"username":"Gerente Luan","email":"gerentevendas@hypeconsorcios.com.br","password_hash":"[REDACTED]","full_name":"Luan Robson de Jesus","role":"manager","status":"active","created_at":"2025-09-16 15:33:29","updated_at":"2025-09-18 10:53:16","last_login":"2025-09-18 10:53:16","created_by":null,"current_password":"[REDACTED]","new_password":"[REDACTED]","confirm_password":"[REDACTED]"}','Perfil atualizado: Nome: ''Luan Robson'' → ''Luan Robson de Jesus''','2804:5b40:aa08:7500:c133:8b77:1240:3ced','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6.1 Safari/605.1.15','2025-09-18 14:14:00');

INSERT INTO faqs VALUES
(1,'Como funciona o consórcio de veículos?','O consórcio é um sistema de autofinanciamento onde um grupo de pessoas se une para adquirir bens. Mensalmente, cada participante paga uma parcela e alguns são contemplados por sorteio ou lance.',4,1,'2025-09-18 00:37:23','2025-09-18 00:45:26'),
(2,'Quais são as vantagens do consórcio?','As principais vantagens são: sem juros, parcelas menores, sem consulta ao SPC/Serasa, possibilidade de usar FGTS, e você pode ser contemplado a qualquer momento.',2,1,'2025-09-18 00:37:23','2025-09-18 00:51:46'),
(3,'Posso usar o FGTS para pagamento?','Sim! Você pode usar o FGTS tanto para dar lance quanto para amortizar parcelas do seu consórcio, seguindo as regras da Caixa Econômica Federal.',1,1,'2025-09-18 00:37:23','2025-09-18 00:51:46'),
(4,'Como funciona a contemplação?','A contemplação pode acontecer por sorteio mensal (gratuito) ou por lance (oferta de valor). Quanto maior o lance, maiores as chances de contemplação.',3,1,'2025-09-18 00:37:23','2025-09-18 00:51:37');

INSERT INTO lead_interactions VALUES
(19,21,10,'note','Lead convertido em venda - Valor: R$ 80.000,00',NULL,NULL,'2025-09-28 15:26:46'),
(20,22,10,'note','Lead convertido em venda - Valor: R$ 55.000,00',NULL,NULL,'2025-09-28 15:34:05'),
(21,23,10,'note','Lead convertido em venda - Valor: R$ 80.000,00',NULL,NULL,'2025-09-28 15:42:10');

INSERT INTO leads VALUES
(12,'Valton ferraz','ferrazvalton27@gmail.com','81996509224','Byd tan','no',NULL,'index',NULL,NULL,'new','medium',NULL,NULL,'2025-09-19 12:37:34','2025-09-19 15:28:46',NULL),
(19,'Thayllon angelo Silva Mota','thayllomangelo@hotmail.com','91983198661','BYD king','no',NULL,'index','2804:214:9808:f6bb:1868:55bb:f183:491e','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','new','medium',NULL,NULL,'2025-09-27 03:43:43','2025-09-27 03:43:43',NULL),
(20,'Thiago Porfirio','tiagutino.porfirio@gmail.com','1195356933','Polo','no',NULL,'index','2804:14c:17c:447b:6c38:1c0:5f01:b27c','Mozilla/5.0 (iPhone; CPU iPhone OS 18_1_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.1.1 Mobile/15E148 Safari/604.1','new','medium',NULL,NULL,'2025-09-27 12:33:16','2025-09-27 12:33:16',NULL),
(21,'Victor','victorferraz739@gmail.com','(13)99665-6563',NULL,NULL,NULL,'index',NULL,NULL,'converted','medium',NULL,NULL,'2025-09-28 15:26:46','2025-09-28 15:26:46',NULL),
(22,'Joao Gabriel Martinelli','maertinellijoao893@gmail.com','(47) 98819-6293',NULL,NULL,NULL,'index',NULL,NULL,'converted','medium',NULL,NULL,'2025-09-28 15:34:05','2025-09-28 15:34:05',NULL),
(23,'KEVIN KAYAN TRAVASSOS ZESUINO','kevinkayan853@gmail.com','(47) 99924-0461',NULL,NULL,NULL,'index',NULL,NULL,'converted','medium',NULL,NULL,'2025-09-28 15:42:10','2025-09-28 15:42:10',NULL),
(24,'Anibal Galvao','odontoanibal@bol.com.br','22999168356','Dolphin plus','yes',50.00,'index','2804:4a28:836:bb00:f424:8707:c7fa:43af','Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1','new','medium',NULL,NULL,'2025-09-29 01:42:42','2025-09-29 01:42:42',NULL),
(25,'tainan eduardo','eduardotainan2@gmail.com','47999446434','jetta','no',NULL,'leves','2804:30c:1e44:f800:102a:8a7d:a638:6357','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','new','medium',NULL,NULL,'2025-10-04 13:25:13','2025-10-04 13:25:13',NULL),
(26,'Thayllon angelo Silva Mota','thayllomangelo@hotmail.com','91983198661','BYD king','no',NULL,'index','2804:214:9803:d3f3:186c:4f2b:e1e0:ffac','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36','new','medium',NULL,NULL,'2025-10-08 17:45:47','2025-10-08 17:45:47',NULL),
(27,'Marcelo Guilherme da Silva','marcelo_projetista@hotmail.com','11976203875','Nivus','no',NULL,'index','186.235.59.172','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36','new','medium',NULL,NULL,'2025-10-27 16:36:25','2025-10-27 16:36:25',NULL),
(28,'PAULO RICARDO FRANCA DO CARMO','paulo_ricardo66@hotmail.com','95981059725','BYD King','no',NULL,'index','2804:214:859d:5c6e:1:0:555c:c1c8','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36','new','medium',NULL,NULL,'2025-11-06 23:37:27','2025-11-06 23:37:27',NULL),
(29,'Fabiano M. Carvalho','fabiomix33@gmail.com','41988564728','BYD','no',NULL,'index','200.203.145.233','Mozilla/5.0 (iPhone; CPU iPhone OS 18_7_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.7 Mobile/15E148 Safari/604.1','new','medium',NULL,NULL,'2025-11-07 09:09:04','2025-11-07 09:09:04',NULL),
(30,'PAULO ROGERIO DE JESUS RODRIGUES','paulo__rogerio28@hotmail.com','13996389288','Honda, Nissan','yes',20.00,'index','177.73.140.113','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36','new','medium',NULL,NULL,'2025-11-07 17:37:16','2025-11-07 17:37:16',NULL),
(31,'ANDRESSA G ROSARIO','andresssaevi@outlook.com','41998050874','Byd dolphin','no',NULL,'index','170.84.239.72','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36','new','medium',NULL,NULL,'2025-11-10 21:51:54','2025-11-10 21:51:54',NULL),
(32,'Diogo Fernandes da Silva','negudiogo@gmail.com','84991026413','Byd Dolphin','no',NULL,'index','200.149.112.131','Mozilla/5.0 (iPhone; CPU iPhone OS 26_1_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/142.0.7444.128 Mobile/15E148 Safari/604.1','new','medium',NULL,NULL,'2025-11-10 22:09:11','2025-11-10 22:09:11',NULL),
(33,'CAIO GABRIEL SANTOS BRITO','caio-seven@Outlook.com','11988049693','Byd','no',NULL,'index','143.0.190.72','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36','new','medium',NULL,NULL,'2025-11-11 18:16:35','2025-11-11 18:16:35',NULL),
(34,'Curioso','curioso@hotmail.com','11988999999','Byd king','no',NULL,'index','2804:214:8024:61:90cd:4d05:9dbf:11f6','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36','new','medium',NULL,NULL,'2025-11-12 02:25:57','2025-11-12 02:25:57',NULL),
(35,'Rômulo Benvenuti Antun','romuloantun74@gmail.com','21997010499','Byd dolphin plus','yes',40.00,'index','2804:388:411e:d55d:1:0:7344:6342','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/29.0 Chrome/136.0.0.0 Mobile Safari/537.36','new','medium',NULL,NULL,'2025-11-12 14:08:52','2025-11-12 14:08:52',NULL),
(36,'Pablo Plácido Barboza','pablo.placido17@gmail.com','63992190668','BYD','no',NULL,'index','2804:389:a12a:43a6:53a9:2d8b:9d88:b485','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36','new','medium',NULL,NULL,'2025-11-14 21:58:59','2025-11-14 21:58:59',NULL),
(37,'Lila Da Costa','dacostalila182@gmail.com','59891724351','Volvagem polo','no',NULL,'index','177.22.60.15','Mozilla/5.0 (Linux; Android 15; Redmi 12 Build/AP3A.240905.015.A2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.118 Mobile Safari/537.36 XiaoMi/MiuiBrowser/14.45.0-gn','new','medium',NULL,NULL,'2025-11-21 23:46:43','2025-11-21 23:46:43',NULL),
(38,'EVERALDO DA SILVA','evegordo2422@gmail.com','49998313702','Honda Civic','no',NULL,'index','2804:30c:1f5f:c001:d06d:1a7c:3127:c3d0','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36','new','medium',NULL,NULL,'2025-11-24 00:48:32','2025-11-24 00:48:32',NULL),
(39,'Antônio Maria Silveira Soares','antoniomssoares88@gmail.com','48991751868','Onix','no',NULL,'index','2804:7c0:14ac:be00:7928:b4fc:7578:d91e','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36','new','medium',NULL,NULL,'2025-11-24 15:42:40','2025-11-24 15:42:40',NULL),
(40,'CLAUDIO CARVALHO DA SILVA','claudiocarvalho36344@gmail.com','21993191592','Byd King','yes',30.00,'index','2804:7dd8:e044:9100:c06f:fa6d:14b0:1676','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36','new','medium',NULL,NULL,'2026-01-13 18:43:09','2026-01-13 18:43:09',NULL),
(41,'Sammy wesley barreto ferreira','sammybarreto28@icloud.com','84998984902','Audi A4','no',NULL,'premium','2804:14d:be9a:80f9:d5bc:2ef3:8a5f:f9fc','Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.1 Mobile/15E148 Safari/604.1','new','medium',NULL,NULL,'2026-01-14 15:00:30','2026-01-14 15:00:30',NULL),
(42,'Francisco José de Araújo','araujofranze@hotmail.com','85988067259','Byd','yes',30.00,'index','2804:18:7852:f5b:188a:bcc0:f524:1bea','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36','new','medium',NULL,NULL,'2026-01-15 00:17:46','2026-01-15 00:17:46',NULL),
(43,'Conceicao','conceicao03@hotmail.com','99981650200','Byd','yes',50.00,'index','200.106.133.44','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36','new','medium',NULL,NULL,'2026-01-16 17:08:13','2026-01-16 17:08:13',NULL),
(44,'marco aurelio Aurélio','marco.1.aurelio@hotmail.com','43999121461','Fluence','yes',15.00,'index','2804:5010:fd15:9a00:8f4e:d4eb:e64:a108','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/29.0 Chrome/136.0.0.0 Mobile Safari/537.36','new','medium',NULL,NULL,'2026-01-16 18:18:22','2026-01-16 18:18:22',NULL),
(45,'Jonathan henrique dos santos corrêa','jonathan.zika201462@gmail.com','17992103074','Byd mini','no',NULL,'index','2804:389:c298:afa3:4120:f1fe:f92d:96d6','Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1','new','medium',NULL,NULL,'2026-01-16 18:33:02','2026-01-16 18:33:02',NULL);

INSERT INTO sales VALUES
(12,21,10,80000.00,2.00,1600.00,5,240.00,'Honda civic 2019','consorcio',0.00,0,0.00,'40222350','','confirmed','2025-09-28 03:00:00','2025-09-28 15:26:46','2025-09-28 15:35:38',NULL),
(13,22,10,55000.00,2.00,1100.00,5,165.00,'Lancer 2018','consorcio',0.00,0,0.00,'40219607','','confirmed','2025-03-19 03:00:00','2025-09-28 15:34:05','2025-09-28 15:36:33',NULL),
(14,23,10,80000.00,2.00,1600.00,5,240.00,'POLO 2018','consorcio',0.00,0,0.00,'','','confirmed','2025-05-06 03:00:00','2025-09-28 15:42:10','2025-09-28 15:42:29',NULL);

INSERT INTO seller_commission_settings VALUES
(1,10,1.50,5,0.00,NULL,0.00,NULL,1,NULL,'2025-09-18 20:57:14','2025-09-18 20:57:14',11,NULL),
(3,13,1.50,5,0.00,NULL,0.00,NULL,1,NULL,'2025-09-18 20:57:14','2025-09-18 20:57:14',11,NULL),
(4,14,1.50,5,0.00,NULL,0.00,NULL,1,NULL,'2025-09-18 20:57:14','2025-09-18 20:57:14',11,NULL),
(8,11,1.80,5,0.00,NULL,0.00,NULL,1,'','2025-09-18 20:57:53','2026-01-14 19:39:20',11,11),
(16,17,1.50,5,0.00,NULL,0.00,NULL,1,NULL,'2025-11-06 14:38:30','2025-11-06 14:38:30',17,NULL);

INSERT INTO site_config VALUES
(1,'hero_title_main','Você tem sonhos','text','hero','Título Principal do Hero','Primeira parte do título principal da página','2025-09-17 20:45:25','2025-09-17 22:21:35'),
(2,'hero_title_highlight','nós temos a chave','text','hero','Título Destacado do Hero','Segunda parte do título (com gradiente)','2025-09-17 20:45:25','2025-09-17 22:21:35'),
(3,'hero_subtitle','Com parcelas que você nunca imaginou. Seu carro novo a clique de você!','textarea','hero','Subtítulo do Hero','Texto descritivo abaixo do título','2025-09-17 20:45:25','2025-09-17 22:21:35'),
(4,'hero_video','assets/videos/admin/1758147695_test-drive-hero.mp4','image','hero','Vídeo de Fundo','Upload do vídeo de fundo','2025-09-17 20:45:25','2025-09-17 22:21:35'),
(5,'hero_logo','assets/images/admin/1758147695_logo.png','image','hero','Logo no Hero','Logo flutuante no hero','2025-09-17 20:45:25','2025-09-17 22:21:35'),
(6,'site_title','Hype Consórcios - Você tem sonhos nós temos a chave','text','meta','Título do Site','Título principal da página (meta title)','2025-09-17 20:45:25','2025-09-17 20:45:25'),
(7,'site_description','Realize o sonho do carro novo com o Consórcio Volkswagen. Parceiro autorizado com as melhores condições. Simule agora!','textarea','meta','Descrição do Site','Descrição meta para SEO','2025-09-17 20:45:25','2025-09-17 20:45:25'),
(8,'site_keywords','consórcio de veículos, consórcio volkswagen, consórcio carros, consórcio sem juros, embracon, carta contemplada, consórcio leves premium pesados','textarea','meta','Palavras-chave','Keywords para SEO','2025-09-17 20:45:25','2025-09-17 20:45:25'),
(9,'og_image','https://hypeconsorcios.com.br/assets/images/consorcio-jaragua-do-sul-og.jpg','text','meta','Imagem Open Graph','Imagem para compartilhamento em redes sociais','2025-09-17 20:45:25','2025-09-17 20:45:25'),
(10,'company_name','Hype Consórcios E Investimentos Ltda','text','company','Nome da Empresa','Razão social da empresa','2025-09-17 20:45:25','2025-09-17 20:45:25'),
(11,'company_phone','(47) 99686-2997','text','company','Telefone','Telefone principal da empresa','2025-09-17 20:45:25','2025-09-17 20:45:25'),
(12,'company_whatsapp','5547996862997','text','company','WhatsApp','Número do WhatsApp (formato internacional)','2025-09-17 20:45:25','2025-09-17 20:45:25'),
(13,'company_instagram','hype.consorcios','text','company','Instagram','Usuario do Instagram','2025-09-17 20:45:25','2025-09-17 20:45:25'),
(14,'company_address','Rua José Narloch, 1953','text','company','Endereço','Endereço da empresa','2025-09-17 20:45:25','2025-09-17 20:45:25'),
(15,'company_neighborhood','Bairro Tifa Martins','text','company','Bairro','Bairro da empresa','2025-09-17 20:45:25','2025-09-17 20:45:25'),
(16,'company_city','Jaraguá do Sul','text','company','Cidade','Cidade da empresa','2025-09-17 20:45:25','2025-09-17 20:45:25'),
(17,'company_state','SC','text','company','Estado','Estado da empresa','2025-09-17 20:45:25','2025-09-17 20:45:25'),
(18,'company_cnpj','53.170.406/0001-89','text','company','CNPJ','CNPJ da empresa','2025-09-17 20:45:25','2025-09-17 20:45:25'),
(19,'about_title','Por que escolher a','text','about','Título da Seção Sobre','Primeira parte do título da seção','2025-09-17 20:45:25','2025-09-17 20:45:25'),
(20,'about_title_highlight','Hype Consórcios?','text','about','Título Destacado Sobre','Segunda parte do título (com gradiente)','2025-09-17 20:45:25','2025-09-17 20:45:25'),
(21,'about_subtitle','Na Hype Consórcios, oferecemos uma assessoria exclusiva em todo o processo de contemplação — desde a assinatura do contrato até a entrega do seu veículo.','textarea','about','Subtítulo Sobre','Primeiro parágrafo da seção sobre','2025-09-17 20:45:25','2025-09-17 20:45:25'),
(22,'about_text1','Somos representantes de uma marca consolidada nacionalmente: o Consórcio Volkswagen, administrado pela Embracon, especialista em consórcios e responsável pela entrega de mais de 700 mil bens desde 1960.','textarea','about','Texto Sobre 1','Segundo parágrafo','2025-09-17 20:45:25','2025-09-17 20:45:25'),
(23,'about_text2','Nosso diferencial está no atendimento ágil, transparente e personalizado, sempre focado nas necessidades de cada cliente. Afinal, nosso propósito vai muito além de comercializar consórcios: queremos realizar o seu sonho.','textarea','about','Texto Sobre 2','Terceiro parágrafo','2025-09-17 20:45:25','2025-09-17 20:45:25'),
(24,'cars_title','Descubra nossa','text','cars','Título da Seção Veículos','Primeira parte do título','2025-09-17 20:45:25','2025-09-17 20:52:06'),
(25,'cars_title_highlight','linha completa de crédito veicular','text','cars','Título Destacado Veículos','Segunda parte do título (com gradiente)','2025-09-17 20:45:25','2025-09-17 20:52:06'),
(26,'leves_price','Parcelas a partir de 811,25','text','cars','Preço Veículos Leves','Preço dos veículos leves','2025-09-17 20:45:25','2025-09-17 20:52:06'),
(27,'leves_description','Realize o sonho do seu carro novo ou seminovo (até 10 anos de uso), da marca e modelo que você escolher. Aqui, seu plano cabe no bolso e seu sonho sai do papel!','textarea','cars','Descrição Veículos Leves','Descrição dos veículos leves','2025-09-17 20:45:25','2025-09-17 20:52:06'),
(28,'leves_image','assets/images/polo-blue.jpg','image','cars','Imagem Veículos Leves','Imagem dos veículos leves','2025-09-17 20:45:25','2025-09-17 20:52:06'),
(29,'premium_price','Parcelas a partir de 1.480,00','text','cars','Preço Veículos Premium','Preço dos veículos premium','2025-09-17 20:45:25','2025-09-17 20:52:06'),
(30,'premium_description','Adquira seu carro premium de forma inteligente, sem comprometer seu patrimônio. O veículo dos seus sonhos está mais próximo do que você imagina!','textarea','cars','Descrição Veículos Premium','Descrição dos veículos premium','2025-09-17 20:45:25','2025-09-17 20:52:06'),
(31,'premium_image','assets/images/admin/1758142326_mercedes.jpg','image','cars','Imagem Veículos Premium','Imagem dos veículos premium','2025-09-17 20:45:25','2025-09-17 20:52:06'),
(32,'pesados_price','Parcelas a partir de 2.530,00','text','cars','Preço Veículos Pesados','Preço dos veículos pesados','2025-09-17 20:45:25','2025-09-17 20:52:06'),
(33,'pesados_description','Invista no crescimento do seu negócio com a aquisição de caminhões e carretas novos ou seminovos. Com a carta de crédito para pesados, sua frota ganha mais força para acelerar resultados.','textarea','cars','Descrição Veículos Pesados','Descrição dos veículos pesados','2025-09-17 20:45:25','2025-09-17 20:52:06'),
(34,'pesados_image','assets/images/caminhao.jpg','image','cars','Imagem Veículos Pesados','Imagem dos veículos pesados','2025-09-17 20:45:25','2025-09-17 20:52:06'),
(35,'faq_title','','text','faq','Título FAQ','Título da seção de FAQ','2025-09-17 20:45:25','2025-09-18 00:52:06'),
(36,'faq_subtitle','','text','faq','Subtítulo FAQ','Subtítulo da seção de FAQ','2025-09-17 20:45:25','2025-09-18 00:52:06'),
(37,'location_title','Nossa','text','location','Título Localização','Primeira parte do título','2025-09-17 20:45:25','2025-09-17 20:45:25'),
(38,'location_title_highlight','Localização','text','location','Título Destacado Localização','Segunda parte do título (com gradiente)','2025-09-17 20:45:25','2025-09-17 20:45:25'),
(39,'location_subtitle','Visite nossa sede em Jaraguá do Sul e conheça nossos especialistas pessoalmente! 📍','text','location','Subtítulo Localização','Subtítulo da seção','2025-09-17 20:45:25','2025-09-17 20:45:25'),
(40,'clients_title','Clientes','text','clients','Título Clientes','Primeira parte do título','2025-09-17 20:45:25','2025-09-17 22:45:24'),
(41,'clients_title_highlight','Contemplados','text','clients','Título Destacado Clientes','Segunda parte do título (com gradiente)','2025-09-17 20:45:25','2025-09-17 22:45:24'),
(42,'clients_subtitle','Veja alguns dos nossos clientes que realizaram o sonho do carro novo! 🚗✨','text','clients','Subtítulo Clientes','Subtítulo da seção','2025-09-17 20:45:25','2025-09-17 22:45:24'),
(43,'career_title','Trabalhe com a','text','career','Título Carreira','Primeira parte do título','2025-09-17 20:45:25','2025-09-17 22:27:55'),
(44,'career_title_highlight','Hype Consórcios','text','career','Título Destacado Carreira','Segunda parte do título (com gradiente)','2025-09-17 20:45:25','2025-09-17 22:27:55'),
(45,'career_subtitle','A Hype Consórcios está em constante crescimento e buscamos profissionais que queiram crescer junto com a gente! 🚀','text','career','Subtítulo Carreira','Subtítulo da seção','2025-09-17 20:45:25','2025-09-17 22:27:55'),
(46,'career_image','assets/images/admin/1758148075_CEO.png','image','career','Imagem Carreira','Imagem da seção de trabalhe conosco','2025-09-17 20:45:25','2025-09-17 22:27:55'),
(47,'client_image_1','assets/images/admin/1758149124_01.JPG','image','clients','Imagem Cliente 1','Foto do primeiro cliente contemplado','2025-09-17 22:10:12','2025-09-17 22:45:24'),
(48,'client_image_2','assets/images/admin/1758149124_03.JPG','image','clients','Imagem Cliente 2','Foto do segundo cliente contemplado','2025-09-17 22:10:12','2025-09-17 22:45:24'),
(49,'client_image_3','assets/images/admin/1758149124_04.JPG','image','clients','Imagem Cliente 3','Foto do terceiro cliente contemplado','2025-09-17 22:10:12','2025-09-17 22:45:24'),
(50,'client_image_4','assets/images/admin/1758149124_05.JPG','image','clients','Imagem Cliente 4','Foto do quarto cliente contemplado','2025-09-17 22:10:12','2025-09-17 22:45:24'),
(51,'client_image_5','assets/images/admin/1758149124_06.JPG','image','clients','Imagem Cliente 5','Foto do quinto cliente contemplado','2025-09-17 22:10:12','2025-09-17 22:45:24'),
(52,'client_image_6','assets/images/admin/1758149124_07.JPG','image','clients','Imagem Cliente 6','Foto do sexto cliente contemplado','2025-09-17 22:10:12','2025-09-17 22:45:24'),
(53,'client_image_7','assets/images/admin/1758149124_08.JPG','image','clients','Imagem Cliente 7','Foto do sétimo cliente contemplado','2025-09-17 22:10:12','2025-09-17 22:45:24'),
(54,'client_image_8','assets/images/admin/1758149124_ll.JPG','image','clients','Imagem Cliente 8','Foto do oitavo cliente contemplado','2025-09-17 22:10:12','2025-09-17 22:45:24'),
(55,'client_image_9','assets/images/clientes/cliente-9.jpg','image','clients','Imagem Cliente 9','Foto do nono cliente contemplado','2025-09-17 22:10:12','2025-09-17 22:45:24'),
(56,'client_image_10','assets/images/admin/1758149124_02.JPG','image','clients','Imagem Cliente 10','Foto do décimo cliente contemplado','2025-09-17 22:10:12','2025-09-17 22:45:24');

INSERT INTO system_settings VALUES
(1,'site_name','Hype Consórcios','Nome do site','2025-09-09 14:21:02',NULL),
(2,'whatsapp_default','5547996862997','WhatsApp padrão','2025-09-09 14:21:02',NULL),
(3,'timezone','America/Sao_Paulo','Fuso horário do sistema','2025-09-09 14:21:02',NULL),
(4,'lead_retention_days','365','Dias para manter leads no sistema','2025-09-09 14:21:02',NULL),
(5,'session_timeout','28800','Timeout de sessão em segundos (8 horas)','2025-09-09 14:21:02',NULL),
(11,'default_commission_rate','1.5','Taxa de comissão padrão do sistema (%)','2025-09-18 20:53:27',NULL);

INSERT INTO user_sessions VALUES
('038475c512babad8f717056bdea8dc92dc2bbe5941c7338ccda8f75e22287f8c',17,'2804:30c:307f:6600:d1fe:7e33:9add:3345','Mozilla/5.0 (Linux; Android 12; SmartTV) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.96 Mobile Safari/537.36','2025-10-01 12:37:14','2025-10-01 20:37:14','2025-10-01 13:24:44'),
('082fa797aa504e9f06dd4112c03e284b0b11b0f629fbf48d1c28c4112e2094d0',10,'2804:30c:3148:1600:9d74:a947:7070:5330','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6.1 Safari/605.1.15','2026-01-07 00:44:15','2026-01-07 08:44:15','2026-01-07 01:14:51'),
('08b0b472e839f95ef070c3c926e5d3c777d7cad5d4d21495304859f0087fa830',11,'189.28.197.24','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2025-10-21 17:38:27','2025-10-22 01:38:27','2025-10-21 20:59:12'),
('0b3e92b6fe5e90fa4750de8d9943c9dde429a9f85bd12f0a5304c83560afa45f',11,'179.190.116.89','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2025-09-17 17:51:30','2025-09-18 01:51:30','2025-09-17 23:18:23'),
('18fc358a93d81b1e525989ee7949b20acbe2348497ea579ab0c819d44de31e45',10,'2804:30c:2d78:5a00:f97e:611b:88c5:a998','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','2025-09-17 22:22:41','2025-09-18 06:22:41','2025-09-17 22:47:19'),
('20d6298e1596d74e30885ae99c1e60adaa7f5d250e6c832281d4438ac5ba2b71',11,'2804:30c:2d78:5a00:f97e:611b:88c5:a998','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-19 21:55:54','2025-09-20 05:55:54','2025-09-19 22:00:00'),
('214d0f23241e3b6e135de758a6130eb4da0a4a33b629811e766b639f4b70692a',17,'2804:30c:3002:db00:a884:ad41:72b8:45ae','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36','2025-11-06 14:37:52','2025-11-06 22:37:52','2025-11-06 14:38:30'),
('439e3c73f1c6f5a7881151f97e95eadeb3a884baa5d03706e14cd688928c5498',11,'2804:690:33ce:3000:56a:35cb:5551:bd2e','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0 (Edition std-2)','2025-09-18 00:37:51','2025-09-18 08:37:51','2025-09-18 01:46:32'),
('46bbeb9a7308495f6873d9b9fc690a2495dbfce58f2308f309ba8d71d1c066a6',11,'2804:690:33ce:3000:56a:35cb:5551:bd2e','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0 (Edition std-2)','2025-09-17 22:12:03','2025-09-18 06:12:03','2025-09-17 22:28:33'),
('5128d58ab2291b1b15ed2a3d699068031cbbdcd481d2a09e5142e0b7672bdf7f',11,'2804:690:33ce:3000:bdac:87bd:d082:27e5','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0 (Edition std-2)','2025-09-26 15:05:01','2025-09-26 23:05:01','2025-09-26 15:05:20'),
('537881ba209b9513ba5598a3e9d039f9249b30780369890792e022132fce44d4',13,'2804:30c:2d78:5a00:f97e:611b:88c5:a998','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','2025-09-17 21:49:41','2025-09-18 05:49:41','2025-09-17 21:49:42'),
('539fc7ca228b807413ccdf568a73e498ad673485d4070b92274ff16a6a433c17',11,'2804:30c:3108:1400:ed89:d8c6:15b:10ec','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2026-01-08 16:24:26','2026-01-09 00:24:26','2026-01-08 16:26:20'),
('56fdfffb8f149b3b490609f9b85f1df848e26c1183ba500068dedc678e9ba2ab',11,'2804:214:93f5:ec3c:838:583f:c4a0:fb83','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2025-12-06 17:22:44','2025-12-07 01:22:44','2025-12-06 19:17:03'),
('5b01ec7a677802abb771c3e477c4dc68ce024b949ff75e1b29c5fabff3b78b1b',11,'2804:690:33ce:3000:8a0:54c8:2d25:a832','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0 (Edition std-2)','2025-11-06 14:43:44','2025-11-06 22:43:44','2025-11-06 14:45:24'),
('62c703642c82f946bbfe2fc7d39dae1451b7471e65a8a47062c6ca2e204ff57d',11,'2804:690:33ce:3000:94a1:aea7:1117:f65f','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','2025-09-17 16:07:53','2025-09-18 00:07:53','2025-09-17 17:41:48'),
('647914b04406f5b26fa6072ed4dd1b6cf3ac5f6deeaaea0e7f72848f5882bb80',11,'2804:30c:3018:ea00:596e:7e6a:b1c3:a73d','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2026-01-16 14:07:31','2026-01-16 22:07:31','2026-01-16 19:19:35'),
('6e3847a74d1b75682991fc310fbae951b6e272154bd5182cebe72461d6aa95ff',10,'2804:30c:2d0d:ca00:93e:a2e2:9ff1:8d65','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6.1 Safari/605.1.15','2025-12-02 12:33:00','2025-12-02 20:33:00','2025-12-02 20:28:32'),
('77cc79f36f26608fe74b0cce729991e7283283b4d767cf1a45efe4055e9c8aec',13,'2804:30c:2d78:5a00:f97e:611b:88c5:a998','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','2025-09-17 21:50:40','2025-09-18 05:50:40','2025-09-17 22:22:16'),
('7c535f1c5f89ebc05c2d7bca7fb23d290dc2c94628a30c94f2dfbdfec643fd80',11,'177.74.243.145','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2025-12-08 20:24:33','2025-12-09 04:24:33','2025-12-08 21:00:32'),
('8631caa204329681d10afd4c6c5ad17f0d9687d925672d15c02f54188b5417d3',11,'2804:30c:2e72:fb00:57a:38b3:a5f0:30f3','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2026-01-13 14:03:21','2026-01-13 22:03:21','2026-01-13 19:50:12'),
('86ec03c176240901e14a1e5547914a9938b6ca2062d7cc8fcfbd9441ceef0fa8',11,'2804:30c:2e72:fb00:2968:5a05:bb9b:9b7f','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2026-01-10 13:31:43','2026-01-10 21:31:43','2026-01-10 13:32:16'),
('8d5ecf9a16b49ebd0fcdfc2af07bc09afe17fd1fa2b115949ed7ba87b78705de',11,'186.226.147.214','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2025-09-26 15:07:43','2025-09-26 23:07:43','2025-09-26 15:15:04'),
('90f30b70f343ba106a3ee2564c6f81380386a9db711752f062e9c22f1fd14132',11,'189.127.29.33','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2026-01-14 14:52:03','2026-01-14 22:52:03','2026-01-14 20:33:32'),
('994cba4e067944d5fac742f20096998087fc30e64af0d503bcc53e70c5359586',10,'2804:30c:1e0d:200:90e:7b11:57a4:b8d5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6.1 Safari/605.1.15','2025-09-18 22:41:07','2025-09-19 06:41:07','2025-09-18 23:42:25'),
('9cfc94c91cf43c460fb8905c11ba5a5fb02d4df8921041e658f522b91c1e55ee',11,'2804:30c:2d0d:ca00:258d:827:973e:ab50','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2025-11-29 13:46:27','2025-11-29 21:46:27','2025-11-29 14:57:49'),
('a156ebabb19ee1d6d91d33e0e894f56d7088bf80f7e38e85b740029684fc4869',10,'2804:30c:1e2b:3200:b45e:f545:f188:29e7','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6.1 Safari/605.1.15','2025-09-28 15:17:12','2025-09-28 23:17:12','2025-09-28 15:42:31'),
('b29e0989dffcd0c80d1f255e6e5db1f964566d9ee548c1d5f5d3dcbde48be10e',11,'2804:5b40:aa08:7500:544b:2315:887c:4eaa','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2025-09-22 14:19:17','2025-09-22 22:19:17','2025-09-22 14:23:57'),
('b2e1e08465d9cda91c57239c0f56928ec0988a19daa96ececf1386d815ed8a5c',11,'2804:690:33ce:3000:56a:35cb:5551:bd2e','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0 (Edition std-2)','2025-09-17 23:01:00','2025-09-18 07:01:00','2025-09-17 23:41:51'),
('ba2c2364dcec4a47684936cc91e01c760d2b68eaea2bf22ed25d80f57f273af5',11,'2804:30c:3018:ea00:acd8:e376:f150:d0c8','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2026-01-23 12:15:14','2026-01-23 20:15:14','2026-01-23 12:17:26'),
('bbc0cb38c54558935a7ef4137b70fc1983765be773893e6d85502086a7877f44',11,'2804:690:33ce:3000:56a:35cb:5551:bd2e','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0 (Edition std-2)','2025-09-18 14:06:48','2025-09-18 22:06:48','2025-09-18 20:57:53'),
('bc7e10a4820a5811f318a1ffd6f435e0e6a2a186482bb53b6991bfb55f9105fa',11,'2804:5b40:aa08:7500:85f1:793:492b:9e6a','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2025-09-20 13:23:41','2025-09-20 21:23:41','2025-09-20 13:42:38'),
('bfeda9c69ac34af16b6127b4ac97dd01ad916c911d0b35fe735bc0376a7d42d9',11,'2804:5b40:aa08:7500:e0d4:af49:23a0:2760','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2025-09-18 13:55:16','2025-09-18 21:55:16','2025-09-18 20:53:29'),
('cb1407b982a6980ae5e809387a7d4f2aac24cfe0d4af0dc23c32d727a57b298f',11,'45.173.222.20','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2025-09-24 21:08:22','2025-09-25 05:08:22','2025-09-24 21:09:17'),
('cb7224b0552105eb20b588417b033875a63a8e79af9229721674d4b295b52e49',11,'2804:690:33ce:3000:5192:6c9c:c45c:4796','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0 (Edition std-2)','2025-09-19 15:44:06','2025-09-19 23:44:06','2025-09-19 22:17:53'),
('cb75a8cc634ac393870e312f850cb87f4b7834fba82122e99e428cfd9c3e26f1',11,'2804:30c:307f:6600:58a9:e6a8:5484:3096','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-01 12:34:36','2025-10-01 20:34:36','2025-10-01 17:23:37'),
('d92b1a3955c00377e62238b8705f7b4c0ac5247572bc5e4d7d5a0d69978c34e7',10,'45.173.222.20','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6.1 Safari/605.1.15','2025-09-18 13:53:16','2025-09-18 21:53:16','2025-09-18 14:14:37'),
('ea1ff36d73f2bb4c37dc642a56461803ef30ee8f97583f38ba2280328551dac9',11,'2804:690:33ce:3000:fd48:c1d1:a27d:7f3','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0 (Edition std-2)','2025-09-18 21:06:36','2025-09-19 05:06:36','2025-09-18 22:33:30'),
('eda8840c38f4d389cee3fd985c7aff62e74fe3f0a6f8e40470b5efc246454bd1',11,'2804:690:33ce:3000:ddf1:2dfe:3c3f:ebb','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0 (Edition std-2)','2025-10-06 15:04:15','2025-10-06 23:04:15','2025-10-06 15:12:33'),
('ef36847c0ea440622bab9200b6d428929a7a2a92578e9152a9e1ea61bc764af6',11,'2804:5b40:aa08:7500:2c2c:8785:d18b:872a','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-23 13:18:56','2025-09-23 21:18:56','2025-09-23 19:36:37'),
('fde15b4731528b014510f51081c40a3db5a5c1832cd04a4000666864c2ebb8d7',11,'2804:690:33ce:3000:fd48:c1d1:a27d:7f3','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0 (Edition std-2)','2025-09-19 07:35:55','2025-09-19 15:35:55','2025-09-19 15:28:19');

INSERT INTO users VALUES
(10,'Gerente Luan','gerentevendas@hypeconsorcios.com.br','$2y$10$Ja1kOZkIGxX8c9eeOVhzweg8yv7iMvftDKcKvHdwfMhNomciFOKL6','Luan Robson de Jesus','manager','active','2025-09-16 18:33:29','2026-01-07 00:44:15','2026-01-07 00:44:15',NULL),
(11,'Jaine e Eduardo','admfinanceiro@hypeconsorcios.com.br','$2y$10$Nn0RoT6x8bw7Iu6PUxHWtesXM8MmPHTPEkhUvSbwIDfa4OqxKi.xa','Jaine e Eduardo','admin','active','2025-09-16 18:36:58','2026-01-23 12:15:14','2026-01-23 12:15:14',NULL),
(13,'Matheus Goudinho','matheusgoudinho95@gmail.com','$2y$10$86.BkopYzNHQ1F4GJalTU.h5Y4yqoJWAMxBSR14XaFbbSfJTJe.z2','Matheus Goudinho','seller','active','2025-09-17 21:48:29','2025-09-17 21:50:40','2025-09-17 21:50:40',NULL),
(14,'Rafael Gomes','Rafaelcgomes11@gmail.com','$2y$10$3b9eRRUNgSNsfQKCntk/dOjJv4p7bVzjoxkexFjuMauStp0ICWmW6','Rafael Gomes','seller','active','2025-09-17 22:17:53','2025-09-17 22:17:53',NULL,NULL),
(17,'Exemplo','jainealves077@gmail.com','$2y$10$duPaoIcZSXcRU51MSbvdo.KAQkBTbT8B2Jo38.FibVpG.wc169WWC','Exemplo','seller','active','2025-10-01 12:35:42','2025-11-06 14:37:52','2025-11-06 14:37:52',NULL);



SELECT setval('users_id_seq', (SELECT MAX(id) FROM users));
SELECT setval('system_settings_id_seq', (SELECT MAX(id) FROM system_settings));
SELECT setval('site_config_id_seq', (SELECT MAX(id) FROM site_config));
SELECT setval('leads_id_seq', (SELECT MAX(id) FROM leads));
SELECT setval('faqs_id_seq', (SELECT MAX(id) FROM faqs));
SELECT setval('lead_interactions_id_seq', (SELECT MAX(id) FROM lead_interactions));
SELECT setval('sales_id_seq', (SELECT MAX(id) FROM sales));
SELECT setval('seller_commission_settings_id_seq', (SELECT MAX(id) FROM seller_commission_settings));
SELECT setval('audit_logs_id_seq', (SELECT MAX(id) FROM audit_logs));