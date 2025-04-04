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

$page = new Page($db);
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'scan':
        try {
            $html_files = glob(__DIR__ . '/../../*.html');
            if (empty($html_files)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No HTML files found in the root directory.'
                ]);
                exit;
            }

            $success = true;
            $message = '';
            $processed = [];

            foreach ($html_files as $file) {
                $filename = basename($file);
                $title = ucfirst(str_replace(['-', '.html'], [' ', ''], $filename));
                
                // Create or update page record
                $page->filename = $filename;
                $page->title = $title;
                $page->modified_by = $user->id;
                
                if ($page->create()) {
                    // Scan HTML content for editable blocks
                    $html_content = file_get_contents($file);
                    if ($html_content === false) {
                        $success = false;
                        $message .= "Failed to read file $filename. ";
                        continue;
                    }

                    $page->scanAndCreateBlocks($page->id, $html_content);
                    $processed[] = $title;
                } else {
                    $success = false;
                    $message .= "Failed to process $filename. ";
                }
            }

            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Successfully processed: ' . implode(', ', $processed) : $message,
                'processed' => $processed,
                'files_found' => count($html_files)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error scanning pages: ' . $e->getMessage()
            ]);
        }
        break;

    case 'update_blocks':
        if (!isset($_POST['page_id']) || !isset($_POST['updates'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }

        $updates = json_decode($_POST['updates'], true);
        if (!is_array($updates)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid updates format']);
            exit;
        }

        $success = true;
        $message = '';

        foreach ($updates as $update) {
            if (!isset($update['id']) || !isset($update['content'])) {
                $success = false;
                $message .= "Invalid update format. ";
                continue;
            }

            if (!$page->updateContentBlock($update['id'], $update['content'])) {
                $success = false;
                $message .= "Failed to update block {$update['id']}. ";
            }
        }

        // Update the page's modified_by field
        if ($success) {
            $page->id = $_POST['page_id'];
            $page->modified_by = $user->id;
            $page->update();
        }

        echo json_encode([
            'success' => $success,
            'message' => $message ?: 'Content blocks updated successfully'
        ]);
        break;

    case 'list':
        $pages = $page->getAll();
        echo json_encode([
            'success' => true,
            'pages' => $pages
        ]);
        break;

    case 'delete':
        if (!isset($_POST['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing page ID']);
            exit;
        }

        $stmt = $db->prepare("DELETE FROM pages WHERE id = ?");
        if ($stmt->execute([$_POST['id']])) {
            // Also delete associated content blocks
            $stmt = $db->prepare("DELETE FROM content_blocks WHERE page_id = ?");
            $stmt->execute([$_POST['id']]);
            echo json_encode(['success' => true, 'message' => 'Page deleted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete page']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
