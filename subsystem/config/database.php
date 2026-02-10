<?php
/**
 * Configuração do Banco de Dados - Hostinger MySQL
 * Hype Consórcios
 */

// Configurar timezone do PHP para Brasil
date_default_timezone_set('America/Sao_Paulo');

class Database {
    private $host = '192.168.1.20';
    private $db_name = 'u383946504_hypeconsorcio';
    private $username = 'admin';
    private $password = 'Aaku_2004@';
    private $conn;

    /**
     * Conecta ao banco de dados MySQL
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            // Configurar fuso horário para -3 horas (Brasil)
            $this->conn->exec("SET time_zone = '-03:00'");
            
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