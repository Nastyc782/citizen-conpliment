<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Ensure user is logged in
requireLogin();

$ticket_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

if (!$ticket_id || !$status) {
    header('Location: ' . $base_path . 'dashboard');
    exit;
}

// Verify the ticket belongs to the user
$stmt = $conn->prepare("SELECT user_id FROM tickets WHERE id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket || $ticket['user_id'] !== $_SESSION['user_id']) {
    header('Location: ' . $base_path . 'dashboard');
    exit;
}

// Update ticket status
$stmt = $conn->prepare("
    UPDATE tickets 
    SET status = ?, 
        updated_at = NOW(), 
        updated_by = ?
    WHERE id = ?
");

try {
    $stmt->execute([$status, $_SESSION['user_id'], $ticket_id]);
    
    // Add a response to record the status change
    $message = "Ticket marked as " . ucfirst($status) . " by user.";
    $stmt = $conn->prepare("
        INSERT INTO responses (ticket_id, user_id, content, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$ticket_id, $_SESSION['user_id'], $message]);
    
    header('Location: ' . $base_path . 'dashboard?status_updated=1');
} catch (PDOException $e) {
    error_log("Error updating ticket status: " . $e->getMessage());
    header('Location: ' . $base_path . 'dashboard?error=1');
}
exit; 