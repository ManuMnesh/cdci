<?php
header("Content-Type: application/json");
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($user->authenticate($username, $password)) {
            $token = $user->createSession();
            if ($token) {
                $_SESSION['user_token'] = $token;
                echo json_encode([
                    "success" => true,
                    "message" => "Login successful",
                    "user" => [
                        "id" => $user->id,
                        "username" => $user->username,
                        "role" => $user->role
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Failed to create session"]);
            }
        } else {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Invalid credentials"]);
        }
        break;

    case 'logout':
        try {
            if (isset($_SESSION['user_token'])) {
                $user->logout();
                session_unset();
                session_destroy();
                echo json_encode(["success" => true, "message" => "Logged out successfully"]);
            } else {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "No active session"]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Error during logout: " . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid action"]);
}
?>
