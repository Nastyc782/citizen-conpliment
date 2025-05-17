<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '/project/');
}

// Ensure the user is authenticated and is an admin
if (!function_exists('isAdmin') || !isAdmin()) {
    header('Location: ' . BASE_PATH . 'login');
    exit;
}

// Get ticket ID from URL
$ticket_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$ticket_id) {
    header('Location: ' . BASE_PATH . 'admin/tickets');
    exit;
}

// Fetch ticket details with joins
$stmt = $conn->prepare("
    SELECT 
        t.*,
        u.name as created_by_name,
        a.name as agency_name,
        ru.name as responder_name,
        c.name as category_name
    FROM tickets t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN agencies a ON t.agency_id = a.id
    LEFT JOIN users ru ON t.updated_by = ru.id
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.id = ?
");

$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header('Location: ' . BASE_PATH . 'admin/tickets');
    exit;
}

// Fetch responses
$responseStmt = $conn->prepare("
    SELECT r.*, u.name as responder_name
    FROM responses r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.ticket_id = ?
    ORDER BY r.created_at DESC
");
$responseStmt->execute([$ticket_id]);
$responses = $responseStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - View Ticket #<?php echo $ticket['id']; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>assets/css/style.css">
    <style>
        .ticket-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }
        .ticket-header {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 5px;
            margin-bottom: 2rem;
        }
        .ticket-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .meta-item {
            background: #fff;
            padding: 1rem;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .meta-label {
            color: #6c757d;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .ticket-content {
            background: #fff;
            padding: 1.5rem;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            margin-bottom: 2rem;
        }
        .response-list {
            margin-top: 2rem;
        }
        .response-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        .response-meta {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        .btn-approve { background: #28a745; color: white; }
        .btn-reject { background: #dc3545; color: white; }
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .priority-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-left: 1rem;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-in_progress { background: #cce5ff; color: #004085; }
        .status-under_review { background: #e2e3e5; color: #383d41; }
        .status-resolved { background: #d4edda; color: #155724; }
        .status-closed { background: #d3d3d3; color: #383d41; }
        .status-approved { background: #28a745; color: white; }
        .status-rejected { background: #dc3545; color: white; }
        .priority-high { background: #f8d7da; color: #721c24; }
        .priority-medium { background: #fff3cd; color: #856404; }
        .priority-low { background: #d4edda; color: #155724; }
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: #6c757d;
            text-decoration: none;
        }
        .back-link:hover {
            color: #343a40;
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <a href="<?php echo BASE_PATH; ?>admin/tickets" class="back-link">← Back to Tickets</a>
        
        <div class="ticket-header">
            <h1>Ticket #<?php echo htmlspecialchars($ticket['id']); ?></h1>
            <h2><?php echo htmlspecialchars($ticket['title']); ?></h2>
            
            <div class="ticket-meta">
                <div class="meta-item">
                    <div class="meta-label">Status</div>
                    <span class="status-badge status-<?php echo strtolower($ticket['status']); ?>">
                        <?php echo ucfirst(htmlspecialchars($ticket['status'])); ?>
                    </span>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Priority</div>
                    <span class="priority-badge priority-<?php echo strtolower($ticket['priority']); ?>">
                        <?php echo ucfirst(htmlspecialchars($ticket['priority'])); ?>
                    </span>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Category</div>
                    <?php echo htmlspecialchars($ticket['category_name']); ?>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Agency</div>
                    <?php echo htmlspecialchars($ticket['agency_name']); ?>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Submitted By</div>
                    <?php echo htmlspecialchars($ticket['created_by_name']); ?>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Created</div>
                    <?php echo date('Y-m-d H:i', strtotime($ticket['created_at'])); ?>
                </div>
                <?php if ($ticket['updated_at']): ?>
                <div class="meta-item">
                    <div class="meta-label">Last Updated</div>
                    <?php echo date('Y-m-d H:i', strtotime($ticket['updated_at'])); ?>
                    <?php if ($ticket['responder_name']): ?>
                        <div>by <?php echo htmlspecialchars($ticket['responder_name']); ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($ticket['status'] !== 'approved' && $ticket['status'] !== 'rejected'): ?>
            <div class="action-buttons">
                <button onclick="showActionModal('approve')" class="btn btn-approve">Approve Ticket</button>
                <button onclick="showActionModal('reject')" class="btn btn-reject">Reject Ticket</button>
            </div>
            <?php endif; ?>
        </div>

        <div class="ticket-content">
            <h3>Description</h3>
            <div class="ticket-description">
                <?php echo nl2br(htmlspecialchars($ticket['description'])); ?>
            </div>
        </div>

        <div class="response-list">
            <h3>Responses</h3>
            <?php if ($responses): ?>
                <?php foreach ($responses as $response): ?>
                    <div class="response-item">
                        <div class="response-meta">
                            <strong><?php echo htmlspecialchars($response['responder_name']); ?></strong>
                            responded on
                            <?php echo date('Y-m-d H:i', strtotime($response['created_at'])); ?>
                        </div>
                        <div class="response-content">
                            <?php echo nl2br(htmlspecialchars($response['content'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No responses yet.</p>
            <?php endif; ?>
        </div>

        <div class="action-buttons">
            <a href="<?php echo BASE_PATH; ?>admin/ticket/<?php echo $ticket['id']; ?>/response" class="btn">Add Response</a>
        </div>
    </div>

    <!-- Action Modal -->
    <div id="actionModal" class="action-modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Confirm Action</h3>
            </div>
            <form method="POST" action="<?php echo BASE_PATH; ?>admin/tickets">
                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
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
        function showActionModal(action) {
            const modal = document.getElementById('actionModal');
            const title = document.getElementById('modalTitle');
            const submitBtn = document.getElementById('modalSubmit');
            const actionInput = document.getElementById('modalAction');
            
            title.textContent = action === 'approve' ? 'Approve Ticket' : 'Reject Ticket';
            submitBtn.textContent = action === 'approve' ? 'Approve' : 'Reject';
            submitBtn.className = action === 'approve' ? 'btn btn-approve' : 'btn btn-reject';
            
            actionInput.value = action;
            modal.style.display = 'flex';
        }
        
        function hideActionModal() {
            document.getElementById('actionModal').style.display = 'none';
        }
    </script>
</body>
</html> 