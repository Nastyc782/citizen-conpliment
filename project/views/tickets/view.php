<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '/project/');
}

// Ensure user is authenticated
if (!isAuthenticated()) {
    header('Location: ' . BASE_PATH . 'login');
    exit;
}

// Get ticket ID from URL
$ticket_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$ticket_id) {
    header('Location: ' . BASE_PATH . 'dashboard');
    exit;
}

// Fetch ticket details with joins
$stmt = $conn->prepare("
    SELECT 
        t.*,
        u.name as created_by_name,
        a.name as agency_name,
        ru.name as responder_name,
        GROUP_CONCAT(DISTINCT r.content ORDER BY r.created_at DESC) as responses,
        GROUP_CONCAT(DISTINCT r.created_at ORDER BY r.created_at DESC) as response_dates,
        GROUP_CONCAT(DISTINCT ru2.name ORDER BY r.created_at DESC) as response_users
    FROM tickets t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN agencies a ON t.agency_id = a.id
    LEFT JOIN users ru ON t.updated_by = ru.id
    LEFT JOIN responses r ON t.id = r.ticket_id
    LEFT JOIN users ru2 ON r.user_id = ru2.id
    WHERE t.id = ?
    GROUP BY t.id
");

$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header('Location: ' . BASE_PATH . 'dashboard');
    exit;
}

// Handle new responses
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['response'])) {
    $response = trim($_POST['response']);
    if (!empty($response)) {
        $stmt = $conn->prepare("
            INSERT INTO responses (ticket_id, user_id, content, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$ticket_id, $_SESSION['user_id'], $response]);

        // Update ticket status if specified
        if (isset($_POST['status']) && !empty($_POST['status'])) {
            $updateStmt = $conn->prepare("
                UPDATE tickets 
                SET status = ?, updated_at = NOW(), updated_by = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$_POST['status'], $_SESSION['user_id'], $ticket_id]);
        }

        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// Convert responses string to array
$responses = $ticket['responses'] ? explode(',', $ticket['responses']) : [];
$response_dates = $ticket['response_dates'] ? explode(',', $ticket['response_dates']) : [];
$response_users = $ticket['response_users'] ? explode(',', $ticket['response_users']) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Ticket #<?php echo $ticket['id']; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>assets/css/style.css">
    <style>
        .ticket-container {
            max-width: 900px;
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
            font-size: 0.9rem;
        }
        .meta-item {
            background: #fff;
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .meta-label {
            color: #6c757d;
            font-weight: 500;
        }
        .ticket-content {
            background: #fff;
            padding: 1.5rem;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            margin-bottom: 2rem;
        }
        .ticket-description {
            white-space: pre-wrap;
            margin-bottom: 1.5rem;
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
        .response-form {
            background: #fff;
            padding: 1.5rem;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        textarea {
            width: 100%;
            min-height: 100px;
            padding: 0.5rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
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
        <a href="<?php echo BASE_PATH; ?>dashboard" class="back-link">← Back to Dashboard</a>
        
        <div class="ticket-header">
            <h1>Ticket #<?php echo htmlspecialchars($ticket['id']); ?></h1>
            <h2><?php echo htmlspecialchars($ticket['subject']); ?></h2>
            
            <div class="ticket-meta">
                <div class="meta-item">
                    <span class="meta-label">Status:</span>
                    <span class="status-badge status-<?php echo strtolower($ticket['status']); ?>">
                        <?php echo ucfirst(htmlspecialchars($ticket['status'])); ?>
                    </span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Priority:</span>
                    <span class="priority-badge priority-<?php echo strtolower($ticket['priority']); ?>">
                        <?php echo ucfirst(htmlspecialchars($ticket['priority'])); ?>
                    </span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Created By:</span>
                    <?php echo htmlspecialchars($ticket['created_by_name']); ?>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Agency:</span>
                    <?php echo htmlspecialchars($ticket['agency_name']); ?>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Created:</span>
                    <?php echo date('Y-m-d H:i', strtotime($ticket['created_at'])); ?>
                </div>
                <?php if ($ticket['updated_at']): ?>
                <div class="meta-item">
                    <span class="meta-label">Last Updated:</span>
                    <?php echo date('Y-m-d H:i', strtotime($ticket['updated_at'])); ?>
                    <?php if ($ticket['responder_name']): ?>
                        by <?php echo htmlspecialchars($ticket['responder_name']); ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="ticket-content">
            <h3>Description</h3>
            <div class="ticket-description">
                <?php echo nl2br(htmlspecialchars($ticket['description'])); ?>
            </div>
        </div>

        <div class="response-list">
            <h3>Responses</h3>
            <?php if (!empty($responses)): ?>
                <?php foreach ($responses as $index => $response): ?>
                    <div class="response-item">
                        <div class="response-meta">
                            <strong><?php echo htmlspecialchars($response_users[$index]); ?></strong>
                            responded on
                            <?php echo date('Y-m-d H:i', strtotime($response_dates[$index])); ?>
                        </div>
                        <div class="response-content">
                            <?php echo nl2br(htmlspecialchars($response)); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No responses yet.</p>
            <?php endif; ?>
        </div>

        <div class="response-form">
            <h3>Add Response</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="response">Your Response:</label>
                    <textarea name="response" id="response" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="status">Update Status:</label>
                    <select name="status" id="status">
                        <option value="">Keep Current Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="under_review">Under Review</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>

                <button type="submit" class="btn">Submit Response</button>
            </form>
        </div>
    </div>
</body>
</html> 