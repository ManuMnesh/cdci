<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Page.php';

// Initialize database and page object
$database = new Database();
$db = $database->getConnection();
$page = new Page($db);

function bakeChanges($filename) {
    global $db, $page;
    
    // Get the page from database
    $stmt = $db->prepare("SELECT id FROM pages WHERE filename = ?");
    $stmt->execute([$filename]);
    $page_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$page_data) {
        return false;
    }
    
    // Get content blocks
    $content_blocks = $page->getContentBlocks($page_data['id']);
    
    // Read the HTML file
    $file_path = __DIR__ . '/../' . $filename;
    $html_content = file_get_contents($file_path);
    
    if (!$html_content) {
        return false;
    }
    
    // Apply content blocks
    $final_html = $page->applyContentBlocks($html_content, $content_blocks);
    
    // Ensure all paths are correct
    $final_html = preg_replace('/(src=[\'"])\/?(?:cdci\/)?uploads\//', '$1./uploads/', $final_html);
    
    // Create a backup of the original file
    copy($file_path, $file_path . '.backup-' . date('Y-m-d-His'));
    
    // Write the changes back to the file
    return file_put_contents($file_path, $final_html) !== false;
}

// Bake changes for sample.html
$result = bakeChanges('sample.html');

if ($result) {
    echo "Changes have been successfully baked into sample.html\n";
    echo "A backup of the original file has been created.\n";
} else {
    echo "Error: Failed to bake changes.\n";
}
?>
