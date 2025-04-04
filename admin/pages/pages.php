<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Page.php';

$database = new Database();
$db = $database->getConnection();
$page = new Page($db);

$pages = $page->getAll();
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Pages</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scanPagesModal">
            <i class="fas fa-sync"></i> Scan Pages
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-items-center mb-0">
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>Filename</th>
                            <th>Last Modified</th>
                            <th>Modified By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($pages as $page): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($page['title']); ?></td>
                            <td><?php echo htmlspecialchars($page['filename']); ?></td>
                            <td><?php echo $page['last_modified']; ?></td>
                            <td><?php echo htmlspecialchars($page['modified_by_user']); ?></td>
                            <td>
                                <a href="edit.php?id=<?php echo $page['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button class="btn btn-sm btn-danger delete-page" data-id="<?php echo $page['id']; ?>">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Scan Pages Modal -->
<div class="modal fade" id="scanPagesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Scan Pages</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>This will scan your website directory for HTML pages and add them to the CMS. Existing pages will be updated.</p>
                <div id="scan-progress" style="display: none;">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="start-scan">Start Scan</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle page deletion
    $('.delete-page').click(function() {
        if (confirm('Are you sure you want to delete this page?')) {
            const pageId = $(this).data('id');
            $.post('api/pages.php', {
                action: 'delete',
                id: pageId
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error deleting page: ' + response.message);
                }
            });
        }
    });

    // Handle page scanning
    $('#start-scan').click(function() {
        $('#scan-progress').show();
        $(this).prop('disabled', true);

        $.post('api/pages.php', {
            action: 'scan'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error scanning pages: ' + response.message);
            }
        }).always(function() {
            $('#scan-progress').hide();
            $('#start-scan').prop('disabled', false);
        });
    });
});
</script>
