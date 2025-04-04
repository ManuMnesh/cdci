<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $password;
    public $email;
    public $role;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET username=:username, password=:password, email=:email, role=:role";
        $stmt = $this->conn->prepare($query);

        $this->password = password_hash($this->password, PASSWORD_DEFAULT);

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":role", $this->role);

        return $stmt->execute();
    }

    public function authenticate($username, $password) {
        $query = "SELECT id, username, password, role FROM " . $this->table_name . " WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if(password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->role = $row['role'];
                return true;
            }
        }
        return false;
    }

    public function createSession() {
        // First, cleanup any existing sessions for this user
        $this->cleanupUserSessions();
        
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $session_id = session_id();

        $query = "INSERT INTO sessions (id, user_id, token, expires_at) VALUES (:session_id, :user_id, :token, :expires_at)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":session_id", $session_id);
        $stmt->bindParam(":user_id", $this->id);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":expires_at", $expires);

        if($stmt->execute()) {
            return $token;
        }
        return false;
    }

    /**
     * Logout the current user by removing their session
     * @return bool
     */
    public function logout() {
        if (!isset($_SESSION['user_token'])) {
            return false;
        }

        try {
            $query = "DELETE FROM sessions WHERE token = :token";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":token", $_SESSION['user_token']);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error during logout: " . $e->getMessage());
            throw new Exception("Database error during logout");
        }
    }

    /**
     * Validate a user's session token
     * @param string $token The session token to validate
     * @return bool True if the session is valid, false otherwise
     */
    public function validateSession($token) {
        try {
            $query = "SELECT user_id, expires_at FROM sessions WHERE token = :token";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":token", $token);
            $stmt->execute();

            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (strtotime($row['expires_at']) < time()) {
                    // Session has expired, clean it up
                    $this->logout();
                    return false;
                }
                $this->id = $row['user_id'];
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error validating session: " . $e->getMessage());
            return false;
        }
    }

    public function cleanupUserSessions() {
        if ($this->id) {
            $query = "DELETE FROM sessions WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $this->id);
            $stmt->execute();
        }
    }

    public function cleanupExpiredSessions() {
        $query = "DELETE FROM sessions WHERE expires_at < NOW()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
    }
}
?>
