<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Set JSON content type
header('Content-Type: application/json');

// Ensure user is logged in
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get agency ID from query string
$agency_id = isset($_GET['agency_id']) ? intval($_GET['agency_id']) : 0;

if (!$agency_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Agency ID is required']);
    exit;
}

try {
    // Get categories for the specified agency
    $stmt = $conn->prepare("
        SELECT id, name, description 
        FROM categories 
        WHERE agency_id = ? 
        ORDER BY name ASC
    ");
    
    $stmt->execute([$agency_id]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($categories);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    error_log("Error fetching categories: " . $e->getMessage());
} 