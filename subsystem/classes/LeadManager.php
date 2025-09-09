<?php
/**
 * Gerenciador de Leads
 * Hype Consórcios CRM
 */

require_once __DIR__ . '/../config/database.php';

class LeadManager {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Criar novo lead
     */
    public function createLead($data) {
        try {
            // Verificar se já existe lead com mesmo telefone recentemente (últimas 24h)
            $stmt = $this->conn->prepare("
                SELECT id, created_at FROM leads 
                WHERE phone = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->execute([$data['phone']]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Atualizar lead existente ao invés de criar duplicado
                return $this->updateLead($existing['id'], $data);
            }
            
            // Criar novo lead
            $stmt = $this->conn->prepare("
                INSERT INTO leads (
                    name, email, phone, vehicle_interest, has_down_payment, 
                    down_payment_value, source_page, ip_address, user_agent, 
                    status, priority
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['vehicle_interest'],
                $data['has_down_payment'],
                $data['down_payment_value'],
                $data['source_page'],
                $data['ip_address'],
                $data['user_agent'],
                'new',
                $data['priority'] ?? 'medium'
            ]);
            
            $leadId = $this->conn->lastInsertId();
            
            // Registrar interação inicial
            $this->addInteraction($leadId, null, 'note', 'Lead capturado via formulário do site');
            
            return [
                'success' => true,
                'message' => 'Lead criado com sucesso',
                'lead_id' => $leadId
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao criar lead: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Atualizar lead existente
     */
    public function updateLead($leadId, $data, $userId = null) {
        try {
            $updateFields = [];
            $params = [];
            
            $allowedFields = [
                'name', 'email', 'phone', 'vehicle_interest', 
                'has_down_payment', 'down_payment_value', 'status', 
                'priority', 'notes', 'assigned_to'
            ];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updateFields[] = "$field = ?";
                    $params[] = $value;
                }
            }
            
            if (empty($updateFields)) {
                return [
                    'success' => false,
                    'message' => 'Nenhum campo válido para atualizar'
                ];
            }
            
            $params[] = $leadId;
            
            $sql = "UPDATE leads SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            // Registrar interação se houver usuário
            if ($userId && isset($data['notes'])) {
                $this->addInteraction($leadId, $userId, 'note', $data['notes']);
            }
            
            return [
                'success' => true,
                'message' => 'Lead atualizado com sucesso'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao atualizar lead: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Listar leads com filtros
     */
    public function getLeads($filters = [], $page = 1, $limit = 50) {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "
                SELECT 
                    l.*,
                    u.full_name as assigned_to_name,
                    (SELECT COUNT(*) FROM lead_interactions li WHERE li.lead_id = l.id) as interactions_count,
                    (SELECT MAX(li.created_at) FROM lead_interactions li WHERE li.lead_id = l.id) as last_interaction,
                    (SELECT s.id FROM sales s WHERE s.lead_id = l.id LIMIT 1) as sale_id
                FROM leads l
                LEFT JOIN users u ON l.assigned_to = u.id
                WHERE 1=1
            ";
            
            $params = [];
            $countParams = [];
            
            // Filtros
            if (!empty($filters['status'])) {
                $sql .= " AND l.status = ?";
                $params[] = $filters['status'];
                $countParams[] = $filters['status'];
            }
            
            if (!empty($filters['priority'])) {
                $sql .= " AND l.priority = ?";
                $params[] = $filters['priority'];
                $countParams[] = $filters['priority'];
            }
            
            if (!empty($filters['assigned_to'])) {
                $sql .= " AND l.assigned_to = ?";
                $params[] = $filters['assigned_to'];
                $countParams[] = $filters['assigned_to'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND l.created_at >= ?";
                $params[] = $filters['date_from'];
                $countParams[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND l.created_at <= ?";
                $params[] = $filters['date_to'] . ' 23:59:59';
                $countParams[] = $filters['date_to'] . ' 23:59:59';
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (l.name LIKE ? OR l.phone LIKE ? OR l.email LIKE ? OR l.vehicle_interest LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
                $countParams = array_merge($countParams, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }
            
            // Contar total
            $countSql = str_replace('SELECT l.*, u.full_name as assigned_to_name, (SELECT COUNT(*) FROM lead_interactions li WHERE li.lead_id = l.id) as interactions_count, (SELECT MAX(li.created_at) FROM lead_interactions li WHERE li.lead_id = l.id) as last_interaction, (SELECT s.id FROM sales s WHERE s.lead_id = l.id LIMIT 1) as sale_id FROM leads l LEFT JOIN users u ON l.assigned_to = u.id', 'SELECT COUNT(*) as total FROM leads l LEFT JOIN users u ON l.assigned_to = u.id', $sql);
            
            $countStmt = $this->conn->prepare($countSql);
            $countStmt->execute($countParams);
            $total = $countStmt->fetch()['total'];
            
            // Buscar leads
            $sql .= " ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $leads = $stmt->fetchAll();
            
            return [
                'success' => true,
                'leads' => $leads,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao buscar leads: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obter lead por ID
     */
    public function getLeadById($leadId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    l.*,
                    u.full_name as assigned_to_name,
                    u.username as assigned_to_username
                FROM leads l
                LEFT JOIN users u ON l.assigned_to = u.id
                WHERE l.id = ?
            ");
            $stmt->execute([$leadId]);
            $lead = $stmt->fetch();
            
            if (!$lead) {
                return [
                    'success' => false,
                    'message' => 'Lead não encontrado'
                ];
            }
            
            // Buscar interações
            $lead['interactions'] = $this->getLeadInteractions($leadId);
            
            return [
                'success' => true,
                'lead' => $lead
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao buscar lead: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Adicionar interação
     */
    public function addInteraction($leadId, $userId, $type, $description, $result = null, $nextContactDate = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO lead_interactions (lead_id, user_id, interaction_type, description, result, next_contact_date)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $leadId,
                $userId,
                $type,
                $description,
                $result,
                $nextContactDate
            ]);
            
            // Atualizar status do lead se necessário
            if (in_array($type, ['call', 'whatsapp', 'email'])) {
                $this->updateLeadStatus($leadId, 'contacted');
            }
            
            return [
                'success' => true,
                'message' => 'Interação registrada com sucesso'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao registrar interação: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Buscar interações do lead
     */
    public function getLeadInteractions($leadId) {
        $stmt = $this->conn->prepare("
            SELECT li.*, u.full_name as user_name
            FROM lead_interactions li
            LEFT JOIN users u ON li.user_id = u.id
            WHERE li.lead_id = ?
            ORDER BY li.created_at DESC
        ");
        $stmt->execute([$leadId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Atribuir lead a usuário
     */
    public function assignLead($leadId, $userId, $assignedBy = null) {
        try {
            $stmt = $this->conn->prepare("UPDATE leads SET assigned_to = ? WHERE id = ?");
            $stmt->execute([$userId, $leadId]);
            
            // Registrar interação
            $user = $this->getUserName($userId);
            $assignedByName = $assignedBy ? $this->getUserName($assignedBy) : 'Sistema';
            
            $this->addInteraction(
                $leadId, 
                $assignedBy, 
                'note', 
                "Lead atribuído para {$user} por {$assignedByName}"
            );
            
            return [
                'success' => true,
                'message' => 'Lead atribuído com sucesso'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao atribuir lead: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Atualizar status do lead
     */
    private function updateLeadStatus($leadId, $newStatus) {
        $stmt = $this->conn->prepare("UPDATE leads SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $leadId]);
    }
    
    /**
     * Obter nome do usuário
     */
    private function getUserName($userId) {
        $stmt = $this->conn->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user ? $user['full_name'] : 'Usuário';
    }
    
    /**
     * Estatísticas de leads
     */
    public function getStats($filters = []) {
        try {
            $sql = "SELECT status, COUNT(*) as count FROM leads WHERE 1=1";
            $params = [];
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND created_at >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND created_at <= ?";
                $params[] = $filters['date_to'] . ' 23:59:59';
            }
            
            $sql .= " GROUP BY status";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $statusStats = $stmt->fetchAll();
            
            // Leads por período
            $periodSql = "
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as count
                FROM leads 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date
            ";
            
            $stmt = $this->conn->prepare($periodSql);
            $stmt->execute();
            $periodStats = $stmt->fetchAll();
            
            return [
                'success' => true,
                'stats' => [
                    'by_status' => $statusStats,
                    'by_period' => $periodStats
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao obter estatísticas: ' . $e->getMessage()
            ];
        }
    }
}
?>