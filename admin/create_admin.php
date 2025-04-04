<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$user->username = 'admin';
$user->password = 'admin123'; // Change this!
$user->email = 'admin@example.com';
$user->role = 'admin';

if ($user->create()) {
    echo "Admin user created successfully!";
} else {
    echo "Failed to create admin user.";
}
?>
