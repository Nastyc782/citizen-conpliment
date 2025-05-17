<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$request = $_SERVER['REQUEST_URI'];
$base_path = '/project/'; // Adjust this based on your XAMPP setup

// Remove query string from request
$request = parse_url($request, PHP_URL_PATH);

// Basic routing
$route = str_replace($base_path, '', $request);
$route = trim($route, '/');

// Extract ticket ID from routes that need it
$ticket_id = null;
if (preg_match('/^admin\/ticket\/(\d+)(\/response)?$/', $route, $matches)) {
    $ticket_id = $matches[1];
    $route = isset($matches[2]) ? 'admin/ticket/response' : 'admin/ticket/view';
    $_GET['id'] = $ticket_id;
}

switch ($route) {
    case '':
    case 'home':
        require 'views/home.php';
        break;
        
    case 'login':
        require 'views/auth/login.php';
        break;
        
    case 'logout':
        require 'views/auth/logout.php';
        break;
        
    case 'register':
        require 'views/auth/register.php';
        break;
        
    case 'dashboard':
        if (!isAuthenticated()) {
            header('Location: ' . $base_path . 'login');
            exit;
        }
        require 'views/dashboard.php';
        break;
        
    case 'my-tickets':
        if (!isAuthenticated()) {
            header('Location: ' . $base_path . 'login');
            exit;
        }
        require 'views/tickets/my-tickets.php';
        break;
        
    case 'submit-ticket':
        if (!isAuthenticated()) {
            header('Location: ' . $base_path . 'login');
            exit;
        }
        require 'views/tickets/submit.php';
        break;
        
    case 'view-ticket':
        if (!isAuthenticated()) {
            header('Location: ' . $base_path . 'login');
            exit;
        }
        require 'views/tickets/view.php';
        break;
        
    case 'admin':
        // Redirect plain /admin to /admin/dashboard for consistency
        header('Location: ' . $base_path . 'admin/dashboard');
        exit;
        
    case 'admin/dashboard':
        if (!isAdmin()) {
            header('Location: ' . $base_path . 'login');
            exit;
        }
        require 'views/admin/dashboard.php';
        break;
        
    case 'admin/tickets':
        if (!isAdmin()) {
            header('Location: ' . $base_path . 'login');
            exit;
        }
        require 'views/admin/tickets.php';
        break;
        
    case 'admin/ticket/view':
        if (!isAdmin()) {
            header('Location: ' . $base_path . 'login');
            exit;
        }
        if (!$ticket_id) {
            header('Location: ' . $base_path . 'admin/tickets');
            exit;
        }
        require 'views/admin/ticket-view.php';
        break;
        
    case 'admin/ticket/response':
        if (!isAdmin()) {
            header('Location: ' . $base_path . 'login');
            exit;
        }
        if (!$ticket_id) {
            header('Location: ' . $base_path . 'admin/tickets');
            exit;
        }
        require 'views/admin/ticket-response.php';
        break;
        
    case 'admin/agencies':
        if (!isAdmin()) {
            header('Location: ' . $base_path . 'login');
            exit;
        }
        require 'views/admin/agencies.php';
        break;
        
    default:
        http_response_code(404);
        require 'views/404.php';
        break;
} 