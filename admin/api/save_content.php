<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/User.php';
require_once __DIR__ . '/../../includes/Page.php';

// Check authentication
$database = new Database();
$db = $database->getConnection();
$user = new User($db);

if (!isset($_SESSION['user_token']) || !$user->validateSession($_SESSION['user_token'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['blockId']) || !isset($data['content'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$page = new Page($db);

// Update the content block
if ($page->updateContentBlock($data['blockId'], $data['content'])) {
    // Get the page filename for this block
    $stmt = $db->prepare("
        SELECT p.filename 
        FROM pages p 
        JOIN content_blocks cb ON p.id = cb.page_id 
        WHERE cb.id = ?
    ");
    $stmt->execute([$data['blockId']]);
    $page_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($page_data) {
        // Include and run the bake script
        require_once __DIR__ . '/../bake_changes.php';
        $bake_result = bakeChanges($page_data['filename']);

        echo json_encode([
            'success' => true,
            'message' => 'Content updated and changes baked successfully',
            'baked' => $bake_result
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Content updated but page not found for baking'
        ]);
    }
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update content'
    ]);
}
?>
