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

if (!isset($_FILES['image']) || !isset($_POST['block_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$upload_dir = __DIR__ . '/../../uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$file = $_FILES['image'];
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

if (!in_array($file_extension, $allowed_extensions)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit;
}

$new_filename = uniqid() . '.' . $file_extension;
$target_path = $upload_dir . $new_filename;

if (move_uploaded_file($file['tmp_name'], $target_path)) {
    // Store the image URL in session to be saved later
    if (!isset($_SESSION['pending_images'])) {
        $_SESSION['pending_images'] = [];
    }
    
    $image_url = './uploads/' . $new_filename;
    $_SESSION['pending_images'][$_POST['block_id']] = $image_url;
    
    echo json_encode([
        'success' => true,
        'url' => '../uploads/' . $new_filename,
        'message' => 'Image uploaded successfully. Click Save Changes to apply.'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to upload image'
    ]);
}
?>
