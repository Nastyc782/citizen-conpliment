<?php
// Initialize session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize user input
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Get user role
 */
function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Get user ID
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get user name
 */
function getUserName() {
    return $_SESSION['user_name'] ?? null;
}

/**
 * Redirect if not authenticated
 */
function requireLogin() {
    if (!isAuthenticated()) {
        header('Location: /project/login');
        exit;
    }
}

/**
 * Redirect if not admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /project/dashboard');
        exit;
    }
}

/**
 * Format date
 */
function formatDate($date) {
    return date('M j, Y g:i A', strtotime($date));
}

/**
 * Get ticket status badge
 */
function getStatusBadge($status) {
    $badges = [
        'pending' => 'warning',
        'in_progress' => 'info',
        'resolved' => 'success',
        'closed' => 'secondary'
    ];
    $badge = $badges[$status] ?? 'primary';
    return '<span class="badge bg-' . $badge . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
}

/**
 * Get priority badge
 */
function getPriorityBadge($priority) {
    $badges = [
        'low' => 'success',
        'medium' => 'warning',
        'high' => 'danger'
    ];
    $badge = $badges[$priority] ?? 'primary';
    return '<span class="badge bg-' . $badge . '">' . ucfirst($priority) . '</span>';
}

function getUserById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getTicketsByUserId($userId) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT t.*, a.name as agency_name 
        FROM tickets t 
        LEFT JOIN agencies a ON t.agency_id = a.id 
        WHERE t.user_id = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllTickets() {
    global $conn;
    $stmt = $conn->prepare("
        SELECT t.*, u.name as user_name, a.name as agency_name 
        FROM tickets t 
        LEFT JOIN users u ON t.user_id = u.id 
        LEFT JOIN agencies a ON t.agency_id = a.id 
        ORDER BY t.created_at DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAgencies() {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM agencies");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createTicket($data) {
    global $conn;
    $stmt = $conn->prepare("
        INSERT INTO tickets (subject, message, category, agency_id, user_id, priority, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'submitted')
    ");
    return $stmt->execute([
        $data['subject'],
        $data['message'],
        $data['category'],
        $data['agency_id'],
        $_SESSION['user_id'],
        $data['priority'] ?? 'medium'
    ]);
}

function getTicketById($id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT t.*, u.name as user_name, a.name as agency_name 
        FROM tickets t 
        LEFT JOIN users u ON t.user_id = u.id 
        LEFT JOIN agencies a ON t.agency_id = a.id 
        WHERE t.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function addResponse($ticketId, $message, $isInternal = false) {
    global $conn;
    $stmt = $conn->prepare("
        INSERT INTO responses (ticket_id, user_id, message, is_internal) 
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([
        $ticketId,
        $_SESSION['user_id'],
        $message,
        $isInternal
    ]);
}

function getTicketResponses($ticketId) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT r.*, u.name as responder_name, u.role as responder_role 
        FROM responses r 
        LEFT JOIN users u ON r.user_id = u.id 
        WHERE r.ticket_id = ? 
        ORDER BY r.created_at ASC
    ");
    $stmt->execute([$ticketId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateTicketStatus($ticketId, $status) {
    global $conn;
    $stmt = $conn->prepare("UPDATE tickets SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $ticketId]);
} 