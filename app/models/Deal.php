<?php
// app/models/Deal.php
class Deal {
    private $conn;
    private $table = 'deals';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create($data) {
        $query = "INSERT INTO " . $this->table . "
                SET deal_code = :deal_code,
                    customer_id = :customer_id,
                    deal_name = :deal_name,
                    description = :description,
                    deal_value = :deal_value,
                    funnel_category = :funnel_category,
                    deal_status = :deal_status,
                    probability = :probability,
                    expected_close = :expected_close,
                    quote_date = :quote_date,
                    deal_type = :deal_type,
                    requirements = :requirements,
                    competitors = :competitors,
                    owner_id = :owner_id";
        
        $stmt = $this->conn->prepare($query);
        
        // Generate deal code
        $data['deal_code'] = Helpers::generateCode('DEAL');
        
        $stmt->bindParam(':deal_code', $data['deal_code']);
        $stmt->bindParam(':customer_id', $data['customer_id']);
        $stmt->bindParam(':deal_name', $data['deal_name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':deal_value', $data['deal_value']);
        $stmt->bindParam(':funnel_category', $data['funnel_category']);
        $stmt->bindParam(':deal_status', $data['deal_status']);
        $stmt->bindParam(':probability', $data['probability']);
        $stmt->bindParam(':expected_close', $data['expected_close']);
        $stmt->bindParam(':quote_date', $data['quote_date']);
        $stmt->bindParam(':deal_type', $data['deal_type']);
        $stmt->bindParam(':requirements', $data['requirements']);
        $stmt->bindParam(':competitors', $data['competitors']);
        $stmt->bindParam(':owner_id', $data['owner_id']);
        
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    public function read($id = null) {
        if($id) {
            $query = "SELECT d.*, 
                     c.company_name, c.contact_person, c.email as customer_email,
                     u.first_name as owner_first, u.last_name as owner_last
                     FROM " . $this->table . " d
                     LEFT JOIN customers c ON d.customer_id = c.id
                     LEFT JOIN users u ON d.owner_id = u.id
                     WHERE d.id = ? LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $query = "SELECT d.*, 
                     c.company_name, c.contact_person,
                     u.first_name as owner_first, u.last_name as owner_last
                     FROM " . $this->table . " d
                     LEFT JOIN customers c ON d.customer_id = c.id
                     LEFT JOIN users u ON d.owner_id = u.id
                     ORDER BY d.expected_close ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    public function getDealsByFunnel($funnel, $owner_id = null) {
        $query = "SELECT d.*, c.company_name, c.contact_person
                 FROM " . $this->table . " d
                 LEFT JOIN customers c ON d.customer_id = c.id
                 WHERE d.funnel_category = ? 
                 AND d.deal_status NOT IN ('closed_won', 'closed_lost')";
        if($owner_id) {
            $query .= " AND d.owner_id = ?";
        }
        $query .= " ORDER BY d.expected_close ASC";
        $stmt = $this->conn->prepare($query);
        if($owner_id) {
            $stmt->bindParam(1, $funnel);
            $stmt->bindParam(2, $owner_id);
        } else {
            $stmt->bindParam(1, $funnel);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateFunnel($deal_id, $funnel_category) {
        $query = "UPDATE " . $this->table . " 
                 SET funnel_category = ?
                 WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $funnel_category);
        $stmt->bindParam(2, $deal_id);
        return $stmt->execute();
    }
    
    public function closeDeal($deal_id, $status, $closed_date = null) {
        if(!$closed_date) $closed_date = date('Y-m-d');
        
        $query = "UPDATE " . $this->table . " 
                 SET deal_status = ?,
                     closed_date = ?
                 WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->bindParam(2, $closed_date);
        $stmt->bindParam(3, $deal_id);
        return $stmt->execute();
    }
    
    public function getDashboardStats($user_id = null) {
        $stats = [];
        
        // Total deals
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        if($user_id) {
            $query .= " WHERE owner_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id);
        } else {
            $stmt = $this->conn->prepare($query);
        }
        $stmt->execute();
        $stats['total_deals'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Won deals
        $query = "SELECT COUNT(*) as won, SUM(deal_value) as won_value 
                 FROM " . $this->table . " 
                 WHERE deal_status = 'closed_won'";
        if($user_id) $query .= " AND owner_id = ?";
        $stmt = $this->conn->prepare($query);
        if($user_id) $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $won = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['deals_won'] = $won['won'];
        $stats['won_value'] = $won['won_value'] ?? 0;
        
        // Lost deals
        $query = "SELECT COUNT(*) as lost FROM " . $this->table . " 
                 WHERE deal_status = 'closed_lost'";
        if($user_id) $query .= " AND owner_id = ?";
        $stmt = $this->conn->prepare($query);
        if($user_id) $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $stats['deals_lost'] = $stmt->fetch(PDO::FETCH_ASSOC)['lost'];
        
        // Pipeline value
        $query = "SELECT SUM(deal_value * probability / 100) as pipeline 
                 FROM " . $this->table . " 
                 WHERE deal_status NOT IN ('closed_won', 'closed_lost')";
        if($user_id) $query .= " AND owner_id = ?";
        $stmt = $this->conn->prepare($query);
        if($user_id) $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $stats['pipeline_value'] = $stmt->fetch(PDO::FETCH_ASSOC)['pipeline'] ?? 0;
        
        // Funnel distribution
        $funnels = ['yellow', 'pink', 'green', 'blue'];
        foreach($funnels as $funnel) {
            $query = "SELECT COUNT(*) as count FROM " . $this->table . " 
                     WHERE funnel_category = ? 
                     AND deal_status NOT IN ('closed_won', 'closed_lost')";
            if($user_id) $query .= " AND owner_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $funnel);
            if($user_id) $stmt->bindParam(2, $user_id);
            $stmt->execute();
            $stats['funnel_' . $funnel] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        }
        
        return $stats;
    }
}
?>