<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base path for the application
$base_path = '/project/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Engagement Portal</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-weight: bold;
        }
        .nav-link {
            color: rgba(255,255,255,.8) !important;
        }
        .nav-link:hover {
            color: rgba(255,255,255,1) !important;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
            transition: background-color 0.3s;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .sidebar a.active {
            background-color: #0d6efd;
        }
        .main-content {
            padding: 20px;
        }
        .ticket-card {
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .ticket-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $base_path; ?>">
                <i class="fas fa-city me-2"></i> Citizen Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_path; ?>dashboard">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_path; ?>submit-ticket">
                                <i class="fas fa-plus-circle"></i> New Complaint
                            </a>
                        </li>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $base_path; ?>admin">
                                    <i class="fas fa-user-shield"></i> Admin Panel
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <span class="nav-link">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_path; ?>logout">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_path; ?>login">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_path; ?>register">
                                <i class="fas fa-user-plus"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar">
                <div class="d-flex flex-column">
                    <a href="<?php echo $base_path; ?>dashboard" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="<?php echo $base_path; ?>submit-ticket" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'submit-ticket') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-plus-circle me-2"></i> New Ticket
                    </a>
                    <a href="<?php echo $base_path; ?>my-tickets" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'my-tickets') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-list me-2"></i> My Tickets
                    </a>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <hr class="bg-light">
                        <a href="<?php echo $base_path; ?>admin" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'admin') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-user-shield me-2"></i> Admin Panel
                        </a>
                        <a href="<?php echo $base_path; ?>admin/tickets" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'admin/tickets') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-ticket-alt me-2"></i> All Tickets
                        </a>
                        <a href="<?php echo $base_path; ?>admin/agencies" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'admin/agencies') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-building me-2"></i> Manage Agencies
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-10 main-content">
    <?php endif; ?> 