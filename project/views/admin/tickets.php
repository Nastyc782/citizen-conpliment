<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '/project/');
}

// Ensure the user is authenticated and is an admin
if (!function_exists('isAdmin') || !isAdmin()) {
    header('Location: ' . BASE_PATH . 'login');
    exit;
}

// Handle status updates and approvals
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ticket_id'])) {
        if (isset($_POST['action'])) {
            // Handle approve/reject actions
            $action = $_POST['action'];
            $ticket_id = $_POST['ticket_id'];
            $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
            
            if ($action === 'approve' || $action === 'reject') {
                // Update ticket status
                $status = ($action === 'approve') ? 'approved' : 'rejected';
                $updateStmt = $conn->prepare("
                    UPDATE tickets SET 
                    status = ?, 
                    admin_comment = ?,
                    updated_at = NOW(),
                    updated_by = ?,
                    admin_action_at = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([$status, $comment, $_SESSION['user_id'], $ticket_id]);

                // Add response for the action
                $responseStmt = $conn->prepare("
                    INSERT INTO responses (ticket_id, user_id, content, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $actionMessage = ($action === 'approve') ? 
                    "Ticket approved by admin." : 
                    "Ticket rejected by admin.";
                if ($comment) {
                    $actionMessage .= " Comment: " . $comment;
                }
                $responseStmt->execute([$ticket_id, $_SESSION['user_id'], $actionMessage]);
            }
        } elseif (isset($_POST['status'])) {
            // Handle regular status updates
            $validStatuses = ['pending', 'in_progress', 'under_review', 'resolved', 'closed', 'approved', 'rejected'];
            if (in_array($_POST['status'], $validStatuses)) {
                $updateStmt = $conn->prepare("
                    UPDATE tickets SET 
                    status = ?, 
                    updated_at = NOW(),
                    updated_by = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([$_POST['status'], $_SESSION['user_id'], $_POST['ticket_id']]);
            }
        }
        header('Location: ' . BASE_PATH . 'admin/tickets');
        exit;
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$agency_filter = isset($_GET['agency']) ? $_GET['agency'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';

// Build the query with filters
$query = "SELECT t.*, u.name as submitter_name, a.name as agency_name,
          ru.name as responder_name,
          t.admin_comment,
          t.admin_action_at
          FROM tickets t 
          LEFT JOIN users u ON t.user_id = u.id 
          LEFT JOIN agencies a ON t.agency_id = a.id
          LEFT JOIN users ru ON t.updated_by = ru.id
          WHERE 1=1";
$params = [];

if ($status_filter) {
    $query .= " AND t.status = ?";
    $params[] = $status_filter;
}
if ($agency_filter) {
    $query .= " AND t.agency_id = ?";
    $params[] = $agency_filter;
}
if ($priority_filter) {
    $query .= " AND t.priority = ?";
    $params[] = $priority_filter;
}

$query .= " ORDER BY 
           CASE 
               WHEN t.priority = 'high' THEN 1
               WHEN t.priority = 'medium' THEN 2
               WHEN t.priority = 'low' THEN 3
           END,
           t.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get agencies for filter
$agencyStmt = $pdo->query("SELECT id, name FROM agencies ORDER BY name");
$agencies = $agencyStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Government Admin - Ticket Management</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>assets/css/style.css">
    <style>
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .filters {
            background: #fff;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-in_progress { background: #cce5ff; color: #004085; }
        .status-under_review { background: #e2e3e5; color: #383d41; }
        .status-resolved { background: #d4edda; color: #155724; }
        .status-closed { background: #d3d3d3; color: #383d41; }
        .priority-high { background: #f8d7da; color: #721c24; }
        .priority-medium { background: #fff3cd; color: #856404; }
        .priority-low { background: #d4edda; color: #155724; }
        .ticket-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-small {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .ticket-meta {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .btn-approve {
            background: #28a745;
            color: white;
        }
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        .status-approved { background: #28a745; color: white; }
        .status-rejected { background: #dc3545; color: white; }
        .admin-comment {
            font-style: italic;
            color: #666;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        .action-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 5px;
            width: 100%;
            max-width: 500px;
        }
        .modal-header {
            margin-bottom: 1rem;
        }
        .modal-footer {
            margin-top: 1rem;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <h1>Government Ticket Management System</h1>
            <div class="header-actions">
                <span class="ticket-count">Total Active Tickets: <?php echo count($tickets); ?></span>
            </div>
        </div>

        <div class="filters">
            <form method="GET" action="">
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <div>
                        <label for="status">Status:</label>
                        <select name="status" id="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="under_review" <?php echo $status_filter === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                            <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                    <div>
                        <label for="agency">Agency:</label>
                        <select name="agency" id="agency">
                            <option value="">All Agencies</option>
                            <?php foreach ($agencies as $agency): ?>
                                <option value="<?php echo $agency['id']; ?>" <?php echo $agency_filter == $agency['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($agency['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="priority">Priority:</label>
                        <select name="priority" id="priority">
                            <option value="">All Priorities</option>
                            <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                        </select>
                    </div>
                    <button type="submit" class="btn">Apply Filters</button>
                </div>
            </form>
        </div>
        
        <div class="tickets-list">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Subject</th>
                        <th>Priority</th>
                        <th>Agency</th>
                        <th>Status</th>
                        <th>Submitted By</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($ticket['id']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($ticket['subject']); ?>
                            <div class="ticket-meta">
                                Created: <?php echo date('Y-m-d H:i', strtotime($ticket['created_at'])); ?>
                                <?php if ($ticket['admin_comment']): ?>
                                    <div class="admin-comment">
                                        Admin Comment: <?php echo htmlspecialchars($ticket['admin_comment']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge priority-<?php echo strtolower($ticket['priority']); ?>">
                                <?php echo ucfirst(htmlspecialchars($ticket['priority'])); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($ticket['agency_name']); ?></td>
                        <td>
                            <?php if ($ticket['status'] === 'approved' || $ticket['status'] === 'rejected'): ?>
                                <span class="status-badge status-<?php echo $ticket['status']; ?>">
                                    <?php echo ucfirst($ticket['status']); ?>
                                </span>
                            <?php else: ?>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                    <select name="status" onchange="this.form.submit()" class="status-select">
                                        <option value="pending" <?php echo $ticket['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="under_review" <?php echo $ticket['status'] === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                                        <option value="resolved" <?php echo $ticket['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                        <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($ticket['submitter_name']); ?></td>
                        <td>
                            <?php if ($ticket['updated_at']): ?>
                                <?php echo date('Y-m-d H:i', strtotime($ticket['updated_at'])); ?>
                                <?php if ($ticket['responder_name']): ?>
                                    <div class="ticket-meta">by <?php echo htmlspecialchars($ticket['responder_name']); ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="ticket-actions">
                            <a href="<?php echo BASE_PATH; ?>admin/ticket/<?php echo $ticket['id']; ?>" class="btn btn-small">View Details</a>
                            <?php if ($ticket['status'] !== 'approved' && $ticket['status'] !== 'rejected'): ?>
                                <button onclick="showActionModal(<?php echo $ticket['id']; ?>, 'approve')" class="btn btn-small btn-approve">Approve</button>
                                <button onclick="showActionModal(<?php echo $ticket['id']; ?>, 'reject')" class="btn btn-small btn-reject">Reject</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Action Modal -->
    <div id="actionModal" class="action-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Confirm Action</h3>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="ticket_id" id="modalTicketId">
                <input type="hidden" name="action" id="modalAction">
                
                <div class="form-group">
                    <label for="comment">Comment (Optional):</label>
                    <textarea name="comment" id="comment" rows="4" style="width: 100%;"></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="hideActionModal()" class="btn">Cancel</button>
                    <button type="submit" class="btn" id="modalSubmit">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showActionModal(ticketId, action) {
            const modal = document.getElementById('actionModal');
            const title = document.getElementById('modalTitle');
            const submitBtn = document.getElementById('modalSubmit');
            const actionInput = document.getElementById('modalAction');
            const ticketInput = document.getElementById('modalTicketId');
            
            title.textContent = action === 'approve' ? 'Approve Ticket' : 'Reject Ticket';
            submitBtn.textContent = action === 'approve' ? 'Approve' : 'Reject';
            submitBtn.className = action === 'approve' ? 'btn btn-approve' : 'btn btn-reject';
            
            actionInput.value = action;
            ticketInput.value = ticketId;
            
            modal.style.display = 'flex';
        }
        
        function hideActionModal() {
            document.getElementById('actionModal').style.display = 'none';
        }
    </script>
</body>
</html> 