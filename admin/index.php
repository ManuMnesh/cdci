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
    <title>CDCI CMS - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: #343a40; }
        .nav-link { color: #fff; }
        .nav-link:hover { color: #f8f9fa; }
        .content { padding: 20px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#" data-page="dashboard">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-page="pages">
                                <i class="fas fa-file"></i> Pages
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="logout">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="content" id="main-content">
                    <!-- Content will be loaded here -->
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Load dashboard by default
            loadPage('dashboard');

            // Handle navigation
            $('.nav-link[data-page]').click(function(e) {
                e.preventDefault();
                $('.nav-link').removeClass('active');
                $(this).addClass('active');
                loadPage($(this).data('page'));
            });

            // Handle logout
            $('#logout').click(function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'auth.php',
                    type: 'POST',
                    data: { action: 'logout' },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            window.location.href = 'login.php';
                        } else {
                            alert('Error logging out: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Logout error:', status, error);
                        try {
                            const response = JSON.parse(xhr.responseText);
                            alert('Error logging out: ' + (response.message || error));
                        } catch(e) {
                            alert('Error logging out. Please try again.');
                        }
                    }
                });
            });

            function loadPage(page) {
                $.get('pages/' + page + '.php', function(data) {
                    $('#main-content').html(data);
                });
            }
        });
    </script>
</body>
</html>
