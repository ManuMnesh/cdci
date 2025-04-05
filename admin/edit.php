<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Page.php';

// Check if user is logged in
$database = new Database();
$db = $database->getConnection();
$user = new User($db);

if (!isset($_SESSION['user_token']) || !$user->validateSession($_SESSION['user_token'])) {
    header("Location: login.php");
    exit;
}

$page = new Page($db);
$page_data = null;
$content_blocks = [];

if (isset($_GET['id'])) {
    $page_data = $page->getById($_GET['id']);
    if ($page_data) {
        $content_blocks = $page->getContentBlocks($page_data['id']);
    }
}

if (!$page_data) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Page - CDCI CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .content-block {
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
        }
        .preview-frame {
            width: 100%;
            height: 600px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>Editing: <?php echo htmlspecialchars($page_data['title']); ?></h2>
                    <div>
                        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
                        <button class="btn btn-primary" id="save-changes">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Content Blocks</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($content_blocks as $block): ?>
                        <div class="content-block" data-block-id="<?php echo $block['id']; ?>">
                            <h6>Block: <?php echo htmlspecialchars($block['selector']); ?></h6>
                            <?php if ($block['type'] === 'text'): ?>
                            <textarea class="form-control content-editor" data-type="text"><?php echo htmlspecialchars($block['content']); ?></textarea>
                            <?php else: ?>
                            <div class="mb-3">
                                <img src="<?php echo htmlspecialchars($block['content']); ?>" class="img-fluid mb-2">
                                <input type="file" class="form-control" accept="image/*">
                                <input type="hidden" class="content-editor" data-type="image" value="<?php echo htmlspecialchars($block['content']); ?>">
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Preview</h5>
                    </div>
                    <div class="card-body">
                        <iframe src="<?php echo htmlspecialchars($page_data['filename']); ?>" class="preview-frame"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize rich text editor for text blocks
            $('.content-editor[data-type="text"]').summernote({
                height: 200,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link']],
                    ['view', ['fullscreen', 'codeview']]
                ]
            });

            // Handle image uploads
            $('input[type="file"]').change(function(e) {
                const file = e.target.files[0];
                const block = $(this).closest('.content-block');
                const formData = new FormData();
                formData.append('image', file);
                formData.append('block_id', block.data('block-id'));

                // Show loading state
                const imgElement = block.find('img');
                const originalSrc = imgElement.attr('src');
                imgElement.css('opacity', '0.5');

                $.ajax({
                    url: 'api/upload.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Update the image preview only
                            imgElement.attr('src', response.url);
                            block.find('.content-editor[data-type="image"]').val(response.url);
                            imgElement.css('opacity', '1');
                        } else {
                            alert('Error uploading image: ' + response.message);
                            imgElement.attr('src', originalSrc);
                            imgElement.css('opacity', '1');
                        }
                    },
                    error: function(xhr) {
                        alert('Error uploading image. Please try again.');
                        imgElement.attr('src', originalSrc);
                        imgElement.css('opacity', '1');
                        if (xhr.status === 401) {
                            window.location.href = 'login.php';
                        }
                    }
                });
            });

            // Save changes when clicking save button
            $('#save-changes').click(function() {
                const savePromises = [];
                
                // Show loading message
                const $saveBtn = $('#save-changes');
                const originalText = $saveBtn.text();
                $saveBtn.text('Saving changes...').prop('disabled', true);
                
                // Save text content
                $('.content-editor[data-type="text"]').each(function() {
                    const block = $(this).closest('.content-block');
                    const blockId = block.data('block-id');
                    const content = $(this).val();
                    
                    const promise = $.ajax({
                        url: 'api/save_content.php',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            blockId: blockId,
                            content: content
                        })
                    });
                    
                    savePromises.push(promise);
                });
                
                // Save image content
                $('.content-editor[data-type="image"]').each(function() {
                    const block = $(this).closest('.content-block');
                    const blockId = block.data('block-id');
                    const content = $(this).val();
                    
                    if (content) {
                        const promise = $.ajax({
                            url: 'api/save_content.php',
                            type: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({
                                blockId: blockId,
                                content: content
                            })
                        });
                        
                        savePromises.push(promise);
                    }
                });
                
                // Wait for all saves to complete
                Promise.all(savePromises)
                    .then(function() {
                        alert('Changes saved! The page will update in a few moments.');
                        // Reload the preview after a short delay to allow changes to be processed
                        setTimeout(function() {
                            $('.preview-frame')[0].contentWindow.location.reload();
                        }, 1500);
                    })
                    .catch(function(error) {
                        if (error.status === 401) {
                            window.location.href = 'login.php';
                        }
                    })
                    .finally(function() {
                        setTimeout(function() {
                            $saveBtn.text(originalText).prop('disabled', false);
                        }, 1000);
                    });
            });
        });
    </script>
</body>
</html>
