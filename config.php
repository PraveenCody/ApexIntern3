<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '3307');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'blog');

// Create database connection
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session
session_start();

// Set common header HTML with Bootstrap
function renderHeader($title = 'Blog App') {
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>'.htmlspecialchars($title).'</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
        <style>
            body { padding-top: 56px; background-color: #f8f9fa; }
            .card { transition: transform 0.2s; }
            .card:hover { transform: translateY(-5px); }
            .navbar-brand { font-weight: 600; }
            .post-content { white-space: pre-line; }
        </style>
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
            <div class="container">
                <a class="navbar-brand" href="index.php">BlogApp</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">';
    
    if (isset($_SESSION['user_id'])) {
        echo '<ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="create.php"><i class="bi bi-plus-circle"></i> New Post</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="nav-link">Welcome, '.htmlspecialchars($_SESSION['username']).'</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </li>
                </ul>';
    } else {
        echo '<ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right"></i> Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php"><i class="bi bi-person-plus"></i> Register</a>
                    </li>
                </ul>';
    }
    
    echo '</div>
            </div>
        </nav>
        <div class="container mt-4">';
}

// Set common footer HTML
function renderFooter() {
    echo '</div>
        <footer class="bg-light text-center py-4 mt-5">
            <div class="container">
                <p class="mb-0">© '.date('Y').' BlogApp. All rights reserved.</p>
            </div>
        </footer>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>';
}
?>