<?php
// app/models/Customer.php
class Customer {
    private $conn;
    private $table = 'customers';
    
    public $id;
    public $customer_code;
    public $company_name;
    public $contact_person;
    public $email;
    public $phone;
    public $industry;
    public $address;
    public $city;
    public $state;
    public $country;
    public $tech_stack;
    public $current_solutions;
    public $pain_points;
    public $budget_range;
    public $lead_source;
    public $customer_status;
    public $assigned_to;
    public $notes;
    public $last_contact;
    public $next_followup;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                SET customer_code = :customer_code,
                    company_name = :company_name,
                    contact_person = :contact_person,
                    email = :email,
                    phone = :phone,
                    industry = :industry,
                    address = :address,
                    city = :city,
                    state = :state,
                    country = :country,
                    tech_stack = :tech_stack,
                    current_solutions = :current_solutions,
                    pain_points = :pain_points,
                    budget_range = :budget_range,
                    lead_source = :lead_source,
                    customer_status = :customer_status,
                    assigned_to = :assigned_to,
                    notes = :notes,
                    last_contact = :last_contact,
                    next_followup = :next_followup";
        
        $stmt = $this->conn->prepare($query);
        
        // Generate customer code
        $this->customer_code = Helpers::generateCode('CUST');

        // Normalize optional fields: empty strings to NULL where appropriate
        $tech_stack = ($this->tech_stack === '' || $this->tech_stack === null) ? null : $this->tech_stack;
        $current_solutions = ($this->current_solutions === '' || $this->current_solutions === null) ? null : $this->current_solutions;
        $pain_points = ($this->pain_points === '' || $this->pain_points === null) ? null : $this->pain_points;
        $notes = ($this->notes === '' || $this->notes === null) ? null : $this->notes;
        $next_followup = ($this->next_followup === '' || $this->next_followup === null) ? null : $this->next_followup;
        $last_contact = ($this->last_contact === '' || $this->last_contact === null) ? null : $this->last_contact;
        
        $stmt->bindParam(':customer_code', $this->customer_code);
        $stmt->bindParam(':company_name', $this->company_name);
        $stmt->bindParam(':contact_person', $this->contact_person);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':industry', $this->industry);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':city', $this->city);
        $stmt->bindParam(':state', $this->state);
        $stmt->bindParam(':country', $this->country);
        $stmt->bindValue(':tech_stack', $tech_stack, $tech_stack === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':current_solutions', $current_solutions, $current_solutions === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':pain_points', $pain_points, $pain_points === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(':budget_range', $this->budget_range);
        $stmt->bindParam(':lead_source', $this->lead_source);
        $stmt->bindParam(':customer_status', $this->customer_status);
        $stmt->bindValue(':assigned_to', (int)$this->assigned_to, PDO::PARAM_INT);
        $stmt->bindValue(':notes', $notes, $notes === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':last_contact', $last_contact, $last_contact === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':next_followup', $next_followup, $next_followup === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    public function read($id = null, $assigned_user_id = null) {
        if($id) {
            $query = "SELECT c.*, u.first_name as assigned_first, u.last_name as assigned_last 
                     FROM " . $this->table . " c
                     LEFT JOIN users u ON c.assigned_to = u.id
                     WHERE c.id = ? LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $query = "SELECT c.*, u.first_name as assigned_first, u.last_name as assigned_last 
                     FROM " . $this->table . " c
                     LEFT JOIN users u ON c.assigned_to = u.id";
            if($assigned_user_id) {
                $query .= " WHERE c.assigned_to = ?";
            }
            $query .= " ORDER BY c.created_at DESC";
            $stmt = $this->conn->prepare($query);
            if($assigned_user_id) {
                $stmt->bindParam(1, $assigned_user_id);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    public function update() {
        $query = "UPDATE " . $this->table . "
                SET company_name = :company_name,
                    contact_person = :contact_person,
                    email = :email,
                    phone = :phone,
                    industry = :industry,
                    address = :address,
                    city = :city,
                    state = :state,
                    country = :country,
                    tech_stack = :tech_stack,
                    current_solutions = :current_solutions,
                    pain_points = :pain_points,
                    budget_range = :budget_range,
                    lead_source = :lead_source,
                    customer_status = :customer_status,
                    assigned_to = :assigned_to,
                    notes = :notes,
                    last_contact = :last_contact,
                    next_followup = :next_followup
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':company_name', $this->company_name);
        $stmt->bindParam(':contact_person', $this->contact_person);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':industry', $this->industry);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':city', $this->city);
        $stmt->bindParam(':state', $this->state);
        $stmt->bindParam(':country', $this->country);
        $stmt->bindParam(':tech_stack', $this->tech_stack);
        $stmt->bindParam(':current_solutions', $this->current_solutions);
        $stmt->bindParam(':pain_points', $this->pain_points);
        $stmt->bindParam(':budget_range', $this->budget_range);
        $stmt->bindParam(':lead_source', $this->lead_source);
        $stmt->bindParam(':customer_status', $this->customer_status);
        $stmt->bindParam(':assigned_to', $this->assigned_to);
        $stmt->bindParam(':notes', $this->notes);
        $stmt->bindParam(':last_contact', $this->last_contact);
        $stmt->bindParam(':next_followup', $this->next_followup);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        return $stmt->execute();
    }

    public function transferAssignedTo($id, $newUserId) {
        $query = "UPDATE " . $this->table . " SET assigned_to = :assigned_to WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':assigned_to', $newUserId);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    public function getCustomersByStatus($status) {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE customer_status = ? 
                 ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function search($keyword, $assigned_user_id = null) {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE (company_name LIKE ? 
                 OR contact_person LIKE ? 
                 OR email LIKE ?)";
        if($assigned_user_id) {
            $query .= " AND assigned_to = ?";
        }
        $query .= " ORDER BY company_name";
        $stmt = $this->conn->prepare($query);
        $keyword = "%$keyword%";
        if($assigned_user_id) {
            $stmt->bindParam(1, $keyword);
            $stmt->bindParam(2, $keyword);
            $stmt->bindParam(3, $keyword);
            $stmt->bindParam(4, $assigned_user_id);
        } else {
            $stmt->bindParam(1, $keyword);
            $stmt->bindParam(2, $keyword);
            $stmt->bindParam(3, $keyword);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>