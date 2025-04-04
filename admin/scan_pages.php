<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/User.php';

// Check if user is logged in
$database = new Database();
$db = $database->getConnection();
$user = new User($db);

if (!isset($_SESSION['user_token']) || !$user->validateSession($_SESSION['user_token'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Pages - CDCI CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Scan HTML Pages</h4>
                    </div>
                    <div class="card-body">
                        <p>Click the button below to scan all HTML pages for editable content blocks.</p>
                        <button id="scan-button" class="btn btn-primary">Start Scan</button>
                        <div id="result" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#scan-button').click(function() {
                const button = $(this);
                button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Scanning...');
                
                $('#result').html('');

                $.ajax({
                    url: 'api/pages.php',
                    type: 'POST',
                    data: { action: 'scan' },
                    success: function(response) {
                        if (response.success) {
                            $('#result').html(`
                                <div class="alert alert-success">
                                    <h5>Scan Completed Successfully!</h5>
                                    <p>${response.message}</p>
                                    <p>You can now <a href="index.php">return to the dashboard</a> to edit your pages.</p>
                                </div>
                            `);
                        } else {
                            $('#result').html(`
                                <div class="alert alert-danger">
                                    <h5>Scan Failed</h5>
                                    <p>${response.message}</p>
                                </div>
                            `);
                        }
                    },
                    error: function() {
                        $('#result').html(`
                            <div class="alert alert-danger">
                                <h5>Error</h5>
                                <p>An error occurred while scanning pages. Please try again.</p>
                            </div>
                        `);
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Start Scan');
                    }
                });
            });
        });
    </script>
</body>
</html>
