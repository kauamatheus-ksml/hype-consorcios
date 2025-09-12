<?php
/**
 * Classe para gerenciamento de logs de auditoria
 * Hype Consórcios CRM
 */

class AuditLogger {
    private $conn;
    
    public function __construct($connection = null) {
        if ($connection) {
            $this->conn = $connection;
        } else {
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }
    
    /**
     * Registrar uma ação de auditoria
     */
    public function log($userId, $action, $description, $options = []) {
        try {
            // Verificar se a tabela existe, senão criar
            $this->ensureTableExists();
            
            $stmt = $this->conn->prepare("
                INSERT INTO audit_logs (
                    user_id, action, table_name, record_id, 
                    old_values, new_values, description, 
                    ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $userId,
                $action,
                $options['table_name'] ?? null,
                $options['record_id'] ?? null,
                isset($options['old_values']) ? json_encode($options['old_values']) : null,
                isset($options['new_values']) ? json_encode($options['new_values']) : null,
                $description,
                $this->getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            return $result;
            
        } catch (Exception $e) {
            // Log error but don't throw to avoid breaking main functionality
            error_log("Erro no AuditLogger: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar atualização de perfil
     */
    public function logProfileUpdate($userId, $oldData, $newData, $changes) {
        $description = "Perfil atualizado: " . implode(', ', $changes);
        
        return $this->log($userId, 'PROFILE_UPDATE', $description, [
            'table_name' => 'users',
            'record_id' => $userId,
            'old_values' => $this->sanitizeData($oldData),
            'new_values' => $this->sanitizeData($newData)
        ]);
    }
    
    /**
     * Registrar tentativa de login
     */
    public function logLogin($userId, $username, $success = true) {
        $action = $success ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED';
        $description = $success ? "Login bem-sucedido para {$username}" : "Tentativa de login falhada para {$username}";
        
        return $this->log($userId, $action, $description);
    }
    
    /**
     * Registrar logout
     */
    public function logLogout($userId, $username) {
        return $this->log($userId, 'LOGOUT', "Logout realizado para {$username}");
    }
    
    /**
     * Registrar mudança de senha
     */
    public function logPasswordChange($userId, $username) {
        return $this->log($userId, 'PASSWORD_CHANGE', "Senha alterada para {$username}", [
            'table_name' => 'users',
            'record_id' => $userId
        ]);
    }
    
    /**
     * Buscar logs de um usuário
     */
    public function getUserLogs($userId, $limit = 50, $offset = 0) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM audit_logs 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$userId, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro ao buscar logs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Buscar logs recentes do sistema
     */
    public function getRecentLogs($limit = 100, $offset = 0) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    al.*,
                    u.username,
                    u.full_name
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                ORDER BY al.created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro ao buscar logs recentes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Limpar logs antigos (manter apenas os últimos X dias)
     */
    public function cleanOldLogs($daysToKeep = 90) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM audit_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$daysToKeep]);
            return $stmt->rowCount();
            
        } catch (Exception $e) {
            error_log("Erro ao limpar logs antigos: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter IP do cliente
     */
    private function getClientIP() {
        // Verificar vários headers para detectar o IP real
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                   'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, 
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Sanitizar dados removendo informações sensíveis
     */
    private function sanitizeData($data) {
        if (!is_array($data)) {
            return $data;
        }
        
        $sensitiveFields = ['password', 'password_hash', 'current_password', 'new_password', 'confirm_password'];
        $sanitized = $data;
        
        foreach ($sensitiveFields as $field) {
            if (isset($sanitized[$field])) {
                $sanitized[$field] = '[REDACTED]';
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Garantir que a tabela de auditoria existe
     */
    private function ensureTableExists() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS audit_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                action VARCHAR(100) NOT NULL,
                table_name VARCHAR(50),
                record_id INT,
                old_values JSON,
                new_values JSON,
                description TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                INDEX idx_user_id (user_id),
                INDEX idx_action (action),
                INDEX idx_table_record (table_name, record_id),
                INDEX idx_created_at (created_at)
            )";
            
            $this->conn->exec($sql);
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao criar tabela de auditoria: " . $e->getMessage());
            return false;
        }
    }
}
?>