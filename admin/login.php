<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CDCI CMS - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            background-color: #f5f5f5;
        }
        .form-signin {
            width: 100%;
            max-width: 330px;
            padding: 15px;
            margin: auto;
        }
        .form-signin .form-floating:focus-within {
            z-index: 2;
        }
        .form-signin input[type="text"] {
            margin-bottom: -1px;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0;
        }
        .form-signin input[type="password"] {
            margin-bottom: 10px;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }
    </style>
</head>
<body class="text-center">
    <main class="form-signin">
        <form id="login-form">
            <h1 class="h3 mb-3 fw-normal">CDCI CMS</h1>
            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                <label for="username">Username</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>
            <div class="alert alert-danger" style="display: none;" id="error-message"></div>
            <button class="w-100 btn btn-lg btn-primary" type="submit">Sign in</button>
        </form>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#login-form').submit(function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: 'auth.php',
                    type: 'POST',
                    data: {
                        action: 'login',
                        username: $('#username').val(),
                        password: $('#password').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.href = 'index.php';
                        }
                    },
                    error: function(xhr) {
                        const response = JSON.parse(xhr.responseText);
                        $('#error-message').text(response.message).show();
                    }
                });
            });
        });
    </script>
</body>
</html>
