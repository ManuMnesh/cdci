<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/Page.php';

// Get the requested page
$requested_page = $_GET['page'] ?? 'sample.html';

// Initialize database and page object
$database = new Database();
$db = $database->getConnection();
$page = new Page($db);

// Security check: only allow .html files from the root directory
if (!preg_match('/^[a-zA-Z0-9_-]+\.html$/', $requested_page)) {
    header("HTTP/1.0 404 Not Found");
    echo "Page not found";
    exit;
}

// Check if file exists
if (!file_exists(__DIR__ . '/' . $requested_page)) {
    header("HTTP/1.0 404 Not Found");
    echo "Page not found";
    exit;
}

// Get the page from database
$stmt = $db->prepare("SELECT id FROM pages WHERE filename = ?");
$stmt->execute([$requested_page]);
$page_data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($page_data) {
    // Get content blocks
    $content_blocks = $page->getContentBlocks($page_data['id']);
    
    // Read the HTML file
    $html_content = file_get_contents(__DIR__ . '/' . $requested_page);
    
    // Apply content blocks
    $final_html = $page->applyContentBlocks($html_content, $content_blocks);
    
    // Fix any absolute paths that might have been saved
    $final_html = preg_replace('/(src=[\'"])\/?(?:cdci\/)?uploads\//', '$1./uploads/', $final_html);
    
    // Output the modified HTML
    echo $final_html;
} else {
    // If page is not in database, show original content
    readfile(__DIR__ . '/' . $requested_page);
}
?>
