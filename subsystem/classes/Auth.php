<?php
/**
 * Sistema de Autenticação
 * Hype Consórcios CRM
 */

require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Autentica usuário
     */
    public function login($username, $password, $remember = false) {
        try {
            // Buscar usuário
            $stmt = $this->conn->prepare("
                SELECT id, username, email, password_hash, full_name, role, status 
                FROM users 
                WHERE (username = ? OR email = ?) AND status = 'active'
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($password, $user['password_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Usuário ou senha incorretos'
                ];
            }
            
            // Criar sessão
            $sessionId = $this->createSession($user['id'], $remember);
            
            // Atualizar último login
            $this->updateLastLogin($user['id']);
            
            return [
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role']
                ],
                'session_id' => $sessionId
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Criar nova sessão
     */
    private function createSession($userId, $remember = false) {
        $sessionId = bin2hex(random_bytes(32));
        $expiresAt = $remember ? 
            date('Y-m-d H:i:s', strtotime('+30 days')) : 
            date('Y-m-d H:i:s', strtotime('+8 hours'));
        
        $stmt = $this->conn->prepare("
            INSERT INTO user_sessions (id, user_id, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $sessionId,
            $userId,
            $this->getClientIP(),
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $expiresAt
        ]);
        
        return $sessionId;
    }
    
    /**
     * Validar sessão
     */
    public function validateSession($sessionId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT s.*, u.id as user_id, u.username, u.email, u.full_name, u.role, u.status
                FROM user_sessions s
                JOIN users u ON s.user_id = u.id
                WHERE s.id = ? AND s.expires_at > NOW() AND u.status = 'active'
            ");
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch();
            
            if (!$session) {
                return [
                    'success' => false,
                    'message' => 'Sessão inválida ou expirada'
                ];
            }
            
            // Atualizar última atividade
            $this->updateSessionActivity($sessionId);
            
            return [
                'success' => true,
                'user' => [
                    'id' => $session['user_id'],
                    'username' => $session['username'],
                    'email' => $session['email'],
                    'full_name' => $session['full_name'],
                    'role' => $session['role']
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao validar sessão: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Logout - Remover sessão
     */
    public function logout($sessionId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM user_sessions WHERE id = ?");
            $stmt->execute([$sessionId]);
            
            return [
                'success' => true,
                'message' => 'Logout realizado com sucesso'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro no logout: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Criar novo usuário
     */
    public function createUser($data, $createdBy = null) {
        try {
            // Validações
            if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
                return [
                    'success' => false,
                    'message' => 'Username, email e senha são obrigatórios'
                ];
            }
            
            // Verificar se usuário já existe
            $stmt = $this->conn->prepare("
                SELECT id FROM users WHERE username = ? OR email = ?
            ");
            $stmt->execute([$data['username'], $data['email']]);
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Usuário ou email já existem'
                ];
            }
            
            // Criar usuário
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("
                INSERT INTO users (username, email, password_hash, full_name, role, status, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['username'],
                $data['email'],
                $passwordHash,
                $data['full_name'] ?? $data['username'],
                $data['role'] ?? 'viewer',
                $data['status'] ?? 'active',
                $createdBy
            ]);
            
            $userId = $this->conn->lastInsertId();
            
            return [
                'success' => true,
                'message' => 'Usuário criado com sucesso',
                'user_id' => $userId
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao criar usuário: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Alterar senha
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Verificar senha atual
            $stmt = $this->conn->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Senha atual incorreta'
                ];
            }
            
            // Atualizar senha
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$newHash, $userId]);
            
            // Invalidar todas as sessões do usuário
            $this->conn->prepare("DELETE FROM user_sessions WHERE user_id = ?")->execute([$userId]);
            
            return [
                'success' => true,
                'message' => 'Senha alterada com sucesso'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao alterar senha: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar permissões
     */
    public function hasPermission($userRole, $requiredRole) {
        $roleHierarchy = [
            'viewer' => 1,
            'seller' => 2,
            'manager' => 3,
            'admin' => 4
        ];
        
        $userLevel = $roleHierarchy[$userRole] ?? 0;
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;
        
        return $userLevel >= $requiredLevel;
    }
    
    /**
     * Listar usuários
     */
    public function getUsers($filters = []) {
        try {
            $sql = "
                SELECT u.id, u.username, u.email, u.full_name, u.role, u.status, 
                       u.created_at, u.last_login, creator.full_name as created_by_name
                FROM users u
                LEFT JOIN users creator ON u.created_by = creator.id
                WHERE 1=1
            ";
            $params = [];
            
            if (!empty($filters['role'])) {
                $sql .= " AND u.role = ?";
                $params[] = $filters['role'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND u.status = ?";
                $params[] = $filters['status'];
            }
            
            $sql .= " ORDER BY u.created_at DESC";
            
            if (!empty($filters['limit'])) {
                $sql .= " LIMIT " . intval($filters['limit']);
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'users' => $stmt->fetchAll()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao listar usuários: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Atualizar último login
     */
    private function updateLastLogin($userId) {
        $stmt = $this->conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }
    
    /**
     * Atualizar atividade da sessão
     */
    private function updateSessionActivity($sessionId) {
        $stmt = $this->conn->prepare("UPDATE user_sessions SET last_activity = NOW() WHERE id = ?");
        $stmt->execute([$sessionId]);
    }
    
    /**
     * Obter IP do cliente
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Limpar sessões expiradas
     */
    public function cleanupExpiredSessions() {
        try {
            $stmt = $this->conn->prepare("DELETE FROM user_sessions WHERE expires_at < NOW()");
            $deleted = $stmt->execute();
            $count = $stmt->rowCount();
            
            return [
                'success' => true,
                'message' => "Limpeza concluída: {$count} sessões removidas"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro na limpeza: ' . $e->getMessage()
            ];
        }
    }
}
?>