<?php
require_once __DIR__ . '/config/database.php';

class DatabaseInitializer {
    private $conn;
    private $db_name = "cdci_cms";
    private $admin_username;
    private $admin_password;
    private $admin_email;

    public function __construct() {
        try {
            // First connect without database to create it if needed
            $this->conn = new PDO(
                "mysql:host=localhost",
                "root",
                ""
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function initializeDatabase() {
        try {
            // Create database if not exists
            $this->conn->exec("CREATE DATABASE IF NOT EXISTS $this->db_name");
            $this->conn->exec("USE $this->db_name");
            
            echo "Database created successfully<br>\n";
            
            // Read and execute schema.sql
            $schema = file_get_contents(__DIR__ . '/config/schema.sql');
            $this->conn->exec($schema);
            
            echo "Schema imported successfully<br>\n";
            
            return true;
        } catch(PDOException $e) {
            echo "Error creating database: " . $e->getMessage() . "<br>\n";
            return false;
        }
    }

    public function createAdminUser($username, $password, $email) {
        try {
            // Check if admin user already exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                echo "Admin user already exists<br>\n";
                return false;
            }

            // Create admin user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->conn->prepare(
                "INSERT INTO users (username, password, email, role) 
                 VALUES (?, ?, ?, 'admin')"
            );
            
            $stmt->execute([$username, $hashedPassword, $email]);
            
            echo "Admin user created successfully<br>\n";
            
            // Store credentials in a secure file
            $this->storeCredentials($username, $password, $email);
            
            return true;
        } catch(PDOException $e) {
            echo "Error creating admin user: " . $e->getMessage() . "<br>\n";
            return false;
        }
    }

    private function storeCredentials($username, $password, $email) {
        $credentials = [
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Store in a secure location outside web root
        $credentialsFile = __DIR__ . '/config/.admin_credentials';
        file_put_contents($credentialsFile, json_encode($credentials, JSON_PRETTY_PRINT));
        chmod($credentialsFile, 0600); // Read/write for owner only
        
        echo "Credentials stored securely<br>\n";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['initialize'])) {
    $initializer = new DatabaseInitializer();
    
    echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd;'>";
    echo "<h2>Starting database initialization...</h2>";
    
    if ($initializer->initializeDatabase()) {
        $username = $_POST['username'] ?? 'admin';
        $password = $_POST['password'] ?? bin2hex(random_bytes(8));
        $email = $_POST['email'] ?? 'admin@example.com';
        
        if ($initializer->createAdminUser($username, $password, $email)) {
            echo "<div style='background-color: #dff0d8; padding: 15px; border-radius: 4px; margin-top: 20px;'>";
            echo "<h3>Initialization completed successfully!</h3>";
            echo "<p><strong>Admin Credentials:</strong></p>";
            echo "<ul>";
            echo "<li>Username: " . htmlspecialchars($username) . "</li>";
            echo "<li>Password: " . htmlspecialchars($password) . "</li>";
            echo "<li>Email: " . htmlspecialchars($email) . "</li>";
            echo "</ul>";
            echo "<p><strong>Please store these credentials securely!</strong></p>";
            echo "<p><a href='admin/login.php' style='display: inline-block; padding: 10px 20px; background-color: #5cb85c; color: white; text-decoration: none; border-radius: 4px;'>Go to Login Page</a></p>";
            echo "</div>";
        }
    }
    echo "</div>";
} else {
    // Display initialization form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CDCI CMS Initialization</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                max-width: 600px;
                margin: 20px auto;
                padding: 20px;
            }
            .container {
                border: 1px solid #ddd;
                padding: 20px;
                border-radius: 4px;
            }
            .form-group {
                margin-bottom: 15px;
            }
            label {
                display: block;
                margin-bottom: 5px;
            }
            input[type="text"],
            input[type="password"],
            input[type="email"] {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-sizing: border-box;
            }
            button {
                background-color: #007bff;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            button:hover {
                background-color: #0056b3;
            }
            .warning {
                background-color: #fff3cd;
                border: 1px solid #ffeeba;
                padding: 15px;
                border-radius: 4px;
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>CDCI CMS Initialization</h1>
            <div class="warning">
                <strong>Warning:</strong> This script will initialize the database and create an admin user.
                Please make sure you have proper database credentials configured in config/database.php.
            </div>
            <form method="POST">
                <div class="form-group">
                    <label for="username">Admin Username:</label>
                    <input type="text" id="username" name="username" value="admin" required>
                </div>
                <div class="form-group">
                    <label for="password">Admin Password:</label>
                    <input type="password" id="password" name="password" placeholder="Leave empty for random password" >
                </div>
                <div class="form-group">
                    <label for="email">Admin Email:</label>
                    <input type="email" id="email" name="email" value="admin@example.com" required>
                </div>
                <button type="submit" name="initialize">Initialize CMS</button>
            </form>
        </div>
    </body>
    </html>
    <?php
}
?>
