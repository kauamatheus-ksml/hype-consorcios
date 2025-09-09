<?php
/**
 * Gerenciador de Vendas
 * Hype Consórcios CRM
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/LeadManager.php';

class SalesManager {
    private $db;
    private $conn;
    private $leadManager;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $this->leadManager = new LeadManager();
    }
    
    /**
     * Converter lead em venda
     */
    public function convertLead($leadId, $saleData, $createdBy = null) {
        try {
            $this->conn->beginTransaction();
            
            // Verificar se lead existe e não foi convertido
            $stmt = $this->conn->prepare("
                SELECT l.*, (SELECT COUNT(*) FROM sales WHERE lead_id = l.id) as has_sale
                FROM leads l WHERE l.id = ?
            ");
            $stmt->execute([$leadId]);
            $lead = $stmt->fetch();
            
            if (!$lead) {
                throw new Exception('Lead não encontrado');
            }
            
            if ($lead['has_sale'] > 0) {
                throw new Exception('Este lead já foi convertido em venda');
            }
            
            // Validar dados obrigatórios
            $required = ['seller_id', 'sale_value', 'vehicle_sold'];
            foreach ($required as $field) {
                if (empty($saleData[$field])) {
                    throw new Exception("Campo obrigatório: {$field}");
                }
            }
            
            // Calcular comissão se informada
            $commissionValue = 0;
            if (!empty($saleData['commission_percentage']) && $saleData['sale_value']) {
                $commissionValue = ($saleData['sale_value'] * $saleData['commission_percentage']) / 100;
            }
            
            // Criar registro de venda
            $stmt = $this->conn->prepare("
                INSERT INTO sales (
                    lead_id, seller_id, sale_value, commission_percentage, 
                    commission_value, vehicle_sold, payment_type, down_payment,
                    financing_months, monthly_payment, contract_number, notes,
                    status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $leadId,
                $saleData['seller_id'],
                $saleData['sale_value'],
                $saleData['commission_percentage'] ?? 0,
                $commissionValue,
                $saleData['vehicle_sold'],
                $saleData['payment_type'] ?? 'consorcio',
                $saleData['down_payment'] ?? 0,
                $saleData['financing_months'] ?? null,
                $saleData['monthly_payment'] ?? null,
                $saleData['contract_number'] ?? null,
                $saleData['notes'] ?? null,
                $saleData['status'] ?? 'pending',
                $createdBy
            ]);
            
            $saleId = $this->conn->lastInsertId();
            
            // Atualizar status do lead para "converted"
            $this->conn->prepare("UPDATE leads SET status = 'converted' WHERE id = ?")
                      ->execute([$leadId]);
            
            // Registrar interação no lead
            $sellerName = $this->getUserName($saleData['seller_id']);
            $this->leadManager->addInteraction(
                $leadId, 
                $createdBy,
                'note', 
                "Lead convertido em venda pelo vendedor: {$sellerName}. Valor: R$ " . number_format($saleData['sale_value'], 2, ',', '.')
            );
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => 'Lead convertido em venda com sucesso',
                'sale_id' => $saleId
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false,
                'message' => 'Erro ao converter lead: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Listar vendas
     */
    public function getSales($filters = [], $page = 1, $limit = 50) {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "
                SELECT 
                    s.*,
                    l.name as lead_name,
                    l.phone as lead_phone,
                    l.email as lead_email,
                    seller.full_name as seller_name,
                    creator.full_name as created_by_name
                FROM sales s
                JOIN leads l ON s.lead_id = l.id
                JOIN users seller ON s.seller_id = seller.id
                LEFT JOIN users creator ON s.created_by = creator.id
                WHERE 1=1
            ";
            
            $params = [];
            $countParams = [];
            
            // Filtros
            if (!empty($filters['status'])) {
                $sql .= " AND s.status = ?";
                $params[] = $filters['status'];
                $countParams[] = $filters['status'];
            }
            
            if (!empty($filters['seller_id'])) {
                $sql .= " AND s.seller_id = ?";
                $params[] = $filters['seller_id'];
                $countParams[] = $filters['seller_id'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND s.sale_date >= ?";
                $params[] = $filters['date_from'];
                $countParams[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND s.sale_date <= ?";
                $params[] = $filters['date_to'] . ' 23:59:59';
                $countParams[] = $filters['date_to'] . ' 23:59:59';
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (l.name LIKE ? OR l.phone LIKE ? OR s.vehicle_sold LIKE ? OR s.contract_number LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
                $countParams = array_merge($countParams, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }
            
            // Contar total
            $countSql = str_replace(
                'SELECT s.*, l.name as lead_name, l.phone as lead_phone, l.email as lead_email, seller.full_name as seller_name, creator.full_name as created_by_name FROM sales s JOIN leads l ON s.lead_id = l.id JOIN users seller ON s.seller_id = seller.id LEFT JOIN users creator ON s.created_by = creator.id',
                'SELECT COUNT(*) as total FROM sales s JOIN leads l ON s.lead_id = l.id JOIN users seller ON s.seller_id = seller.id LEFT JOIN users creator ON s.created_by = creator.id',
                $sql
            );
            
            $countStmt = $this->conn->prepare($countSql);
            $countStmt->execute($countParams);
            $total = $countStmt->fetch()['total'];
            
            // Buscar vendas
            $sql .= " ORDER BY s.sale_date DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $sales = $stmt->fetchAll();
            
            return [
                'success' => true,
                'sales' => $sales,
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
                'message' => 'Erro ao buscar vendas: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obter venda por ID
     */
    public function getSaleById($saleId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    s.*,
                    l.name as lead_name,
                    l.phone as lead_phone,
                    l.email as lead_email,
                    l.vehicle_interest as original_interest,
                    seller.full_name as seller_name,
                    seller.username as seller_username,
                    creator.full_name as created_by_name
                FROM sales s
                JOIN leads l ON s.lead_id = l.id
                JOIN users seller ON s.seller_id = seller.id
                LEFT JOIN users creator ON s.created_by = creator.id
                WHERE s.id = ?
            ");
            $stmt->execute([$saleId]);
            $sale = $stmt->fetch();
            
            if (!$sale) {
                return [
                    'success' => false,
                    'message' => 'Venda não encontrada'
                ];
            }
            
            return [
                'success' => true,
                'sale' => $sale
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao buscar venda: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Atualizar venda
     */
    public function updateSale($saleId, $data, $updatedBy = null) {
        try {
            $updateFields = [];
            $params = [];
            
            $allowedFields = [
                'sale_value', 'commission_percentage', 'vehicle_sold', 
                'payment_type', 'down_payment', 'financing_months',
                'monthly_payment', 'contract_number', 'notes', 'status'
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
            
            // Recalcular comissão se necessário
            if (isset($data['sale_value']) || isset($data['commission_percentage'])) {
                // Buscar dados atuais
                $currentStmt = $this->conn->prepare("SELECT sale_value, commission_percentage FROM sales WHERE id = ?");
                $currentStmt->execute([$saleId]);
                $current = $currentStmt->fetch();
                
                $newSaleValue = $data['sale_value'] ?? $current['sale_value'];
                $newCommissionPercentage = $data['commission_percentage'] ?? $current['commission_percentage'];
                
                $newCommissionValue = ($newSaleValue * $newCommissionPercentage) / 100;
                
                $updateFields[] = "commission_value = ?";
                $params[] = $newCommissionValue;
            }
            
            $params[] = $saleId;
            
            $sql = "UPDATE sales SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            // Registrar no histórico se houve mudança de status
            if (isset($data['status']) && $updatedBy) {
                $this->addSaleHistory($saleId, $updatedBy, "Status alterado para: {$data['status']}");
            }
            
            return [
                'success' => true,
                'message' => 'Venda atualizada com sucesso'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao atualizar venda: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Cancelar venda
     */
    public function cancelSale($saleId, $reason, $cancelledBy) {
        try {
            $this->conn->beginTransaction();
            
            // Atualizar status da venda
            $stmt = $this->conn->prepare("
                UPDATE sales 
                SET status = 'cancelled', notes = CONCAT(IFNULL(notes, ''), '\n\nCANCELADA: ', ?) 
                WHERE id = ?
            ");
            $stmt->execute([$reason, $saleId]);
            
            // Buscar lead_id
            $stmt = $this->conn->prepare("SELECT lead_id FROM sales WHERE id = ?");
            $stmt->execute([$saleId]);
            $sale = $stmt->fetch();
            
            if ($sale) {
                // Voltar status do lead para "negotiating"
                $this->conn->prepare("UPDATE leads SET status = 'negotiating' WHERE id = ?")
                          ->execute([$sale['lead_id']]);
                
                // Registrar interação
                $this->leadManager->addInteraction(
                    $sale['lead_id'],
                    $cancelledBy,
                    'note',
                    "Venda cancelada. Motivo: {$reason}"
                );
            }
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => 'Venda cancelada com sucesso'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false,
                'message' => 'Erro ao cancelar venda: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Relatório de vendas por vendedor
     */
    public function getSalesReport($filters = []) {
        try {
            $sql = "
                SELECT 
                    u.id as seller_id,
                    u.full_name as seller_name,
                    COUNT(s.id) as total_sales,
                    SUM(CASE WHEN s.status = 'confirmed' THEN s.sale_value ELSE 0 END) as confirmed_value,
                    SUM(CASE WHEN s.status = 'confirmed' THEN s.commission_value ELSE 0 END) as total_commission,
                    AVG(CASE WHEN s.status = 'confirmed' THEN s.sale_value ELSE NULL END) as avg_sale_value,
                    COUNT(CASE WHEN s.status = 'confirmed' THEN 1 END) as confirmed_sales,
                    COUNT(CASE WHEN s.status = 'pending' THEN 1 END) as pending_sales,
                    COUNT(CASE WHEN s.status = 'cancelled' THEN 1 END) as cancelled_sales
                FROM users u
                LEFT JOIN sales s ON u.id = s.seller_id
            ";
            
            $params = [];
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND s.sale_date >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND s.sale_date <= ?";
                $params[] = $filters['date_to'] . ' 23:59:59';
            }
            
            $sql .= " WHERE u.role IN ('seller', 'manager', 'admin') GROUP BY u.id, u.full_name ORDER BY confirmed_value DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'report' => $stmt->fetchAll()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao gerar relatório: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Dashboard de vendas
     */
    public function getDashboardStats($period = 30) {
        try {
            // Vendas por status
            $stmt = $this->conn->prepare("
                SELECT 
                    status,
                    COUNT(*) as count,
                    SUM(sale_value) as total_value
                FROM sales 
                WHERE sale_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY status
            ");
            $stmt->execute([$period]);
            $statusStats = $stmt->fetchAll();
            
            // Top vendedores
            $stmt = $this->conn->prepare("
                SELECT 
                    u.full_name,
                    COUNT(s.id) as sales_count,
                    SUM(s.sale_value) as total_value
                FROM sales s
                JOIN users u ON s.seller_id = u.id
                WHERE s.sale_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  AND s.status = 'confirmed'
                GROUP BY u.id, u.full_name
                ORDER BY total_value DESC
                LIMIT 5
            ");
            $stmt->execute([$period]);
            $topSellers = $stmt->fetchAll();
            
            // Vendas por dia (últimos 30 dias)
            $stmt = $this->conn->prepare("
                SELECT 
                    DATE(sale_date) as date,
                    COUNT(*) as sales_count,
                    SUM(sale_value) as total_value
                FROM sales 
                WHERE sale_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(sale_date)
                ORDER BY date
            ");
            $stmt->execute();
            $dailyStats = $stmt->fetchAll();
            
            return [
                'success' => true,
                'stats' => [
                    'by_status' => $statusStats,
                    'top_sellers' => $topSellers,
                    'daily_stats' => $dailyStats
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao obter estatísticas: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Adicionar histórico à venda
     */
    private function addSaleHistory($saleId, $userId, $description) {
        // Para simplificar, vamos adicionar como interação no lead
        $stmt = $this->conn->prepare("SELECT lead_id FROM sales WHERE id = ?");
        $stmt->execute([$saleId]);
        $sale = $stmt->fetch();
        
        if ($sale) {
            $this->leadManager->addInteraction($sale['lead_id'], $userId, 'note', $description);
        }
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
}
?>