<?php
/**
 * Configuração do Banco de Dados - Hostinger MySQL
 * Hype Consórcios
 */

// Configurar timezone do PHP para Brasil
date_default_timezone_set('America/Sao_Paulo');

class Database {
    private $host = 'aws-0-us-west-2.pooler.supabase.com';
    private $db_name = 'postgres';
    private $username = 'postgres.pnffxphwgqrtwmlrxwky';
    private $password = 'Idiasiin@kaos_';
    private $port = '5432';
    private $conn;

    /**
     * Conecta ao banco de dados PostgreSQL (Supabase)
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;

        // Allow environment variables for production (Vercel)
        $db_host = getenv('DB_HOST') ?: $this->host;
        $db_name = getenv('DB_NAME') ?: $this->db_name;
        $db_user = getenv('DB_USER') ?: $this->username;
        $db_pass = getenv('DB_PASS') ?: $this->password;
        $db_port = getenv('DB_PORT') ?: $this->port;

        try {
            $dsn = "pgsql:host=" . $db_host . ";port=" . $db_port . ";dbname=" . $db_name;
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ];

            $this->conn = new PDO($dsn, $db_user, $db_pass, $options);
            
            // Configurar fuso horário para Brasil
            $this->conn->exec("SET timezone TO 'America/Sao_Paulo'");
            
        } catch(PDOException $e) {
            echo "Erro na conexão: " . $e->getMessage();
            return null;
        }

        return $this->conn;
    }

    /**
     * Testa a conexão com o banco
     * @return array
     */
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            
            if ($conn) {
                // Testa uma query simples
                $stmt = $conn->query("SELECT 1 as test");
                $result = $stmt->fetch();
                
                return [
                    'success' => true,
                    'message' => 'Conexão estabelecida com sucesso!',
                    'server_info' => $conn->getAttribute(PDO::ATTR_SERVER_VERSION),
                    'database' => $this->db_name,
                    'host' => $this->host
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Não foi possível estabelecer conexão'
                ];
            }
            
        } catch(PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Fecha a conexão
     */
    public function closeConnection() {
        $this->conn = null;
    }
}
?>