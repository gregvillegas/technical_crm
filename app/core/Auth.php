<?php
// app/core/Auth.php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

class Auth {
    public static function login($username, $password) {
        $db = new Database();
        $conn = $db->getConnection();
        
        $query = "SELECT * FROM users WHERE username = :username OR email = :email";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $username);
        $stmt->execute();
        
        if($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['full_name'] = $row['first_name'] . ' ' . $row['last_name'];
                $_SESSION['quota_profit'] = isset($row['quota_profit']) ? (float)$row['quota_profit'] : 250000.00;
                return true;
            }
        }
        return false;
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function logout() {
        session_destroy();
        header("Location: /login");
        exit();
    }
    
    public static function requireLogin() {
        if(!self::isLoggedIn()) {
            header("Location: /login");
            exit();
        }
    }
    
    public static function hasRole($role) {
        return isset($_SESSION['role']) && $_SESSION['role'] == $role;
    }
    
    public static function getUserID() {
        return $_SESSION['user_id'] ?? null;
    }
}
?>