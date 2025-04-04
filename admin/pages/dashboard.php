<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Page.php';

$database = new Database();
$db = $database->getConnection();
$page = new Page($db);

$pages = $page->getAll();
?>

<div class="container-fluid py-4">
    <h2 class="mb-4">Dashboard</h2>
    
    <div class="row">
        <div class="col-xl-3 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-9">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-capitalize font-weight-bold">Total Pages</p>
                                <h5 class="font-weight-bolder mb-0">
                                    <?php echo count($pages); ?>
                                </h5>
                            </div>
                        </div>
                        <div class="col-3 text-end">
                            <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                <i class="fas fa-file text-lg opacity-10" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recently Modified Pages</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>Page</th>
                                    <th>Last Modified</th>
                                    <th>Modified By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach(array_slice($pages, 0, 5) as $page): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($page['title']); ?></td>
                                    <td><?php echo $page['last_modified']; ?></td>
                                    <td><?php echo htmlspecialchars($page['modified_by_user']); ?></td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $page['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
