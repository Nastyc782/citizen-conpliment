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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['response'])) {
    $response = trim($_POST['response']);
    if (!empty($response)) {
        // Add the response
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

        // Redirect back to ticket view
        header('Location: ' . BASE_PATH . 'admin/ticket/' . $ticket_id);
        exit;
    }
}

// Fetch ticket details
$stmt = $conn->prepare("
    SELECT 
        t.*,
        u.name as created_by_name,
        a.name as agency_name
    FROM tickets t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN agencies a ON t.agency_id = a.id
    WHERE t.id = ?
");

$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header('Location: ' . BASE_PATH . 'admin/tickets');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Respond to Ticket #<?php echo $ticket['id']; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>assets/css/style.css">
    <style>
        .response-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        .ticket-summary {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 5px;
            margin-bottom: 2rem;
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
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        textarea {
            width: 100%;
            min-height: 200px;
            padding: 0.5rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: #6c757d;
            text-decoration: none;
        }
        .back-link:hover {
            color: #343a40;
        }
        .status-badge {
            display: inline-block;
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
    </style>
</head>
<body>
    <div class="response-container">
        <a href="<?php echo BASE_PATH; ?>admin/ticket/<?php echo $ticket['id']; ?>" class="back-link">← Back to Ticket</a>
        
        <div class="ticket-summary">
            <h2>Responding to Ticket #<?php echo htmlspecialchars($ticket['id']); ?></h2>
            <h3><?php echo htmlspecialchars($ticket['title']); ?></h3>
            <p>
                <strong>Status:</strong>
                <span class="status-badge status-<?php echo strtolower($ticket['status']); ?>">
                    <?php echo ucfirst(htmlspecialchars($ticket['status'])); ?>
                </span>
            </p>
            <p><strong>Submitted by:</strong> <?php echo htmlspecialchars($ticket['created_by_name']); ?></p>
            <p><strong>Agency:</strong> <?php echo htmlspecialchars($ticket['agency_name']); ?></p>
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