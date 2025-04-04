<?php
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Create pages table if it doesn't exist
$pages_table = "CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    modified_by INT,
    last_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_filename (filename)
)";

// Create content_blocks table if it doesn't exist
$blocks_table = "CREATE TABLE IF NOT EXISTS content_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NOT NULL,
    selector VARCHAR(255) NOT NULL,
    content TEXT,
    type ENUM('text', 'image') NOT NULL,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    UNIQUE KEY unique_block (page_id, selector)
)";

try {
    $db->exec($pages_table);
    $db->exec($blocks_table);
    echo "Database tables verified and updated successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
