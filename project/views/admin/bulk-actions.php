<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Ensure user is admin
if (!function_exists('isAdmin') || !isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Verify it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get POST data
$action = isset($_POST['action']) ? $_POST['action'] : '';
$ticket_ids = isset($_POST['ticket_ids']) ? $_POST['ticket_ids'] : [];
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

// Validate input
if (empty($action) || empty($ticket_ids) || !is_array($ticket_ids)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    $conn->beginTransaction();

    switch ($action) {
        case 'update_status':
            $new_status = isset($_POST['status']) ? $_POST['status'] : '';
            if (!in_array($new_status, ['pending', 'in_progress', 'under_review', 'resolved', 'closed'])) {
                throw new Exception('Invalid status');
            }

            $updateStmt = $conn->prepare("
                UPDATE tickets 
                SET 
                    status = ?,
                    updated_at = NOW(),
                    updated_by = ?,
                    admin_comment = CASE WHEN ? != '' THEN ? ELSE admin_comment END
                WHERE id IN (" . str_repeat('?,', count($ticket_ids) - 1) . "?)
            ");

            $params = array_merge(
                [$new_status, $_SESSION['user_id'], $comment, $comment],
                $ticket_ids
            );
            $updateStmt->execute($params);

            // Add to audit log
            foreach ($ticket_ids as $ticket_id) {
                $auditStmt = $conn->prepare("
                    INSERT INTO audit_log (ticket_id, user_id, action, details, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $auditStmt->execute([
                    $ticket_id,
                    $_SESSION['user_id'],
                    'bulk_status_update',
                    json_encode(['new_status' => $new_status, 'comment' => $comment])
                ]);
            }
            break;

        case 'assign_priority':
            $new_priority = isset($_POST['priority']) ? $_POST['priority'] : '';
            if (!in_array($new_priority, ['low', 'medium', 'high'])) {
                throw new Exception('Invalid priority');
            }

            $updateStmt = $conn->prepare("
                UPDATE tickets 
                SET 
                    priority = ?,
                    updated_at = NOW(),
                    updated_by = ?,
                    admin_comment = CASE WHEN ? != '' THEN ? ELSE admin_comment END
                WHERE id IN (" . str_repeat('?,', count($ticket_ids) - 1) . "?)
            ");

            $params = array_merge(
                [$new_priority, $_SESSION['user_id'], $comment, $comment],
                $ticket_ids
            );
            $updateStmt->execute($params);

            // Add to audit log
            foreach ($ticket_ids as $ticket_id) {
                $auditStmt = $conn->prepare("
                    INSERT INTO audit_log (ticket_id, user_id, action, details, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $auditStmt->execute([
                    $ticket_id,
                    $_SESSION['user_id'],
                    'bulk_priority_update',
                    json_encode(['new_priority' => $new_priority, 'comment' => $comment])
                ]);
            }
            break;

        case 'assign_agency':
            $agency_id = isset($_POST['agency_id']) ? intval($_POST['agency_id']) : 0;
            if ($agency_id <= 0) {
                throw new Exception('Invalid agency ID');
            }

            // Verify agency exists
            $agencyCheck = $conn->prepare("SELECT id FROM agencies WHERE id = ?");
            $agencyCheck->execute([$agency_id]);
            if (!$agencyCheck->fetch()) {
                throw new Exception('Agency not found');
            }

            $updateStmt = $conn->prepare("
                UPDATE tickets 
                SET 
                    agency_id = ?,
                    updated_at = NOW(),
                    updated_by = ?,
                    admin_comment = CASE WHEN ? != '' THEN ? ELSE admin_comment END
                WHERE id IN (" . str_repeat('?,', count($ticket_ids) - 1) . "?)
            ");

            $params = array_merge(
                [$agency_id, $_SESSION['user_id'], $comment, $comment],
                $ticket_ids
            );
            $updateStmt->execute($params);

            // Add to audit log
            foreach ($ticket_ids as $ticket_id) {
                $auditStmt = $conn->prepare("
                    INSERT INTO audit_log (ticket_id, user_id, action, details, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $auditStmt->execute([
                    $ticket_id,
                    $_SESSION['user_id'],
                    'bulk_agency_update',
                    json_encode(['new_agency_id' => $agency_id, 'comment' => $comment])
                ]);
            }
            break;

        default:
            throw new Exception('Invalid action');
    }

    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Bulk action completed successfully',
        'affected_tickets' => count($ticket_ids)
    ]);

} catch (Exception $e) {
    $conn->rollBack();
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => $e->getMessage()]);
} catch (PDOException $e) {
    $conn->rollBack();
    header('HTTP/1.1 500 Internal Server Error');
    error_log("Bulk action error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
} 