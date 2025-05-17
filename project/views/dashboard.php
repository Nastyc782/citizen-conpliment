<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Ensure user is logged in
requireLogin();

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : 'all';

// Build the query
$query = "SELECT t.*, a.name as agency_name, 
          (SELECT COUNT(*) FROM responses r WHERE r.ticket_id = t.id) as response_count
          FROM tickets t 
          LEFT JOIN agencies a ON t.agency_id = a.id 
          WHERE t.user_id = ?";
$params = [$_SESSION['user_id']];

if ($status_filter) {
    $query .= " AND t.status = ?";
    $params[] = $status_filter;
}

if ($priority_filter) {
    $query .= " AND t.priority = ?";
    $params[] = $priority_filter;
}

if ($search_query) {
    $query .= " AND (t.title LIKE ? OR t.description LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

if ($date_range !== 'all') {
    switch ($date_range) {
        case 'today':
            $query .= " AND DATE(t.created_at) = CURDATE()";
            break;
        case 'week':
            $query .= " AND t.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $query .= " AND t.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
    }
}

$query .= " ORDER BY t.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get ticket statistics
$total_tickets = count($tickets);
$pending_tickets = count(array_filter($tickets, fn($t) => $t['status'] === 'pending'));
$in_progress_tickets = count(array_filter($tickets, fn($t) => $t['status'] === 'in_progress'));
$resolved_tickets = count(array_filter($tickets, fn($t) => $t['status'] === 'resolved'));
$high_priority = count(array_filter($tickets, fn($t) => $t['priority'] === 'high'));
$medium_priority = count(array_filter($tickets, fn($t) => $t['priority'] === 'medium'));
$low_priority = count(array_filter($tickets, fn($t) => $t['priority'] === 'low'));

// Get recent activity
$activity_query = "
    SELECT 
        'response' as type,
        r.created_at as date,
        t.title as ticket_title,
        t.id as ticket_id,
        u.name as user_name
    FROM responses r
    JOIN tickets t ON r.ticket_id = t.id
    JOIN users u ON r.user_id = u.id
    WHERE t.user_id = ?
    UNION
    SELECT 
        'status_change' as type,
        t.updated_at as date,
        t.title as ticket_title,
        t.id as ticket_id,
        u.name as user_name
    FROM tickets t
    JOIN users u ON t.updated_by = u.id
    WHERE t.user_id = ? AND t.updated_by IS NOT NULL
    ORDER BY date DESC
    LIMIT 5";

$stmt = $conn->prepare($activity_query);
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!--- Include Chart.js for visualizations --->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar">
            <div class="d-flex flex-column">
                <a href="<?php echo $base_path; ?>dashboard" class="active">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="<?php echo $base_path; ?>submit-ticket">
                    <i class="fas fa-plus-circle me-2"></i> New Ticket
                </a>
                <a href="<?php echo $base_path; ?>my-tickets">
                    <i class="fas fa-list me-2"></i> My Tickets
                </a>
                <hr>
                <h6 class="sidebar-heading">Filters</h6>
                <form method="GET" class="filter-form">
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Priority</label>
                        <select name="priority" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Date Range</label>
                        <select name="date_range" class="form-select form-select-sm">
                            <option value="all" <?php echo $date_range === 'all' ? 'selected' : ''; ?>>All Time</option>
                            <option value="today" <?php echo $date_range === 'today' ? 'selected' : ''; ?>>Today</option>
                            <option value="week" <?php echo $date_range === 'week' ? 'selected' : ''; ?>>This Week</option>
                            <option value="month" <?php echo $date_range === 'month' ? 'selected' : ''; ?>>This Month</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">Apply Filters</button>
                </form>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 main-content">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
                    <div class="d-flex gap-2">
                        <div class="search-box">
                            <form method="GET" class="d-flex">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search tickets..." 
                                       value="<?php echo htmlspecialchars($search_query); ?>">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                        <a href="<?php echo $base_path; ?>submit-ticket" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Submit New Ticket
                        </a>
                    </div>
                </div>

                <!-- Ticket Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Tickets</h5>
                                <h2 class="card-text"><?php echo $total_tickets; ?></h2>
                                <div class="progress bg-white bg-opacity-25">
                                    <div class="progress-bar bg-white" style="width: 100%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Pending</h5>
                                <h2 class="card-text"><?php echo $pending_tickets; ?></h2>
                                <div class="progress bg-white bg-opacity-25">
                                    <div class="progress-bar bg-white" 
                                         style="width: <?php echo $total_tickets ? ($pending_tickets/$total_tickets*100) : 0; ?>%">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">In Progress</h5>
                                <h2 class="card-text"><?php echo $in_progress_tickets; ?></h2>
                                <div class="progress bg-white bg-opacity-25">
                                    <div class="progress-bar bg-white" 
                                         style="width: <?php echo $total_tickets ? ($in_progress_tickets/$total_tickets*100) : 0; ?>%">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Resolved</h5>
                                <h2 class="card-text"><?php echo $resolved_tickets; ?></h2>
                                <div class="progress bg-white bg-opacity-25">
                                    <div class="progress-bar bg-white" 
                                         style="width: <?php echo $total_tickets ? ($resolved_tickets/$total_tickets*100) : 0; ?>%">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <!-- Priority Distribution Chart -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Priority Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="priorityChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Recent Activity</h5>
                            </div>
                            <div class="card-body">
                                <div class="activity-timeline">
                                    <?php foreach ($recent_activity as $activity): ?>
                                        <div class="activity-item">
                                            <div class="activity-content">
                                                <div class="activity-icon <?php echo $activity['type'] === 'response' ? 'bg-info' : 'bg-warning'; ?>">
                                                    <i class="fas <?php echo $activity['type'] === 'response' ? 'fa-comment' : 'fa-sync'; ?>"></i>
                                                </div>
                                                <div class="activity-details">
                                                    <p class="mb-1">
                                                        <?php if ($activity['type'] === 'response'): ?>
                                                            New response on ticket
                                                        <?php else: ?>
                                                            Status updated for ticket
                                                        <?php endif; ?>
                                                        <a href="<?php echo $base_path; ?>view-ticket?id=<?php echo $activity['ticket_id']; ?>">
                                                            <?php echo htmlspecialchars($activity['ticket_title']); ?>
                                                        </a>
                                                    </p>
                                                    <small class="text-muted">
                                                        by <?php echo htmlspecialchars($activity['user_name']); ?> • 
                                                        <?php echo formatDate($activity['date']); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (empty($recent_activity)): ?>
                                        <p class="text-muted text-center my-3">No recent activity</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Tickets -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Tickets</h5>
                        <span class="text-muted">Showing <?php echo count($tickets); ?> tickets</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tickets)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                                <p class="lead">No tickets found</p>
                                <a href="<?php echo $base_path; ?>submit-ticket" class="btn btn-primary">
                                    Submit Your First Ticket
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Agency</th>
                                            <th>Status</th>
                                            <th>Priority</th>
                                            <th>Responses</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tickets as $ticket): ?>
                                            <tr>
                                                <td>#<?php echo $ticket['id']; ?></td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <a href="<?php echo $base_path; ?>view-ticket?id=<?php echo $ticket['id']; ?>" 
                                                           class="text-decoration-none">
                                                            <?php echo htmlspecialchars($ticket['title']); ?>
                                                        </a>
                                                        <small class="text-muted">
                                                            <?php echo substr(htmlspecialchars($ticket['description']), 0, 50) . '...'; ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($ticket['agency_name']); ?></td>
                                                <td><?php echo getStatusBadge($ticket['status']); ?></td>
                                                <td><?php echo getPriorityBadge($ticket['priority']); ?></td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo $ticket['response_count']; ?> responses
                                                    </span>
                                                </td>
                                                <td><?php echo formatDate($ticket['created_at']); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="<?php echo $base_path; ?>view-ticket?id=<?php echo $ticket['id']; ?>" 
                                                           class="btn btn-sm btn-info" 
                                                           data-bs-toggle="tooltip" 
                                                           title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($ticket['status'] !== 'resolved'): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-success"
                                                                    onclick="markResolved(<?php echo $ticket['id']; ?>)"
                                                                    data-bs-toggle="tooltip"
                                                                    title="Mark as Resolved">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize priority distribution chart
const ctx = document.getElementById('priorityChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['High', 'Medium', 'Low'],
        datasets: [{
            data: [<?php echo "$high_priority, $medium_priority, $low_priority"; ?>],
            backgroundColor: ['#dc3545', '#ffc107', '#28a745']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Function to mark ticket as resolved
function markResolved(ticketId) {
    if (confirm('Are you sure you want to mark this ticket as resolved?')) {
        window.location.href = `${BASE_PATH}update-ticket-status.php?id=${ticketId}&status=resolved`;
    }
}

// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
});
</script>

<style>
.sidebar {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 20px;
    border-right: 1px solid #dee2e6;
}

.sidebar a {
    color: #495057;
    text-decoration: none;
    padding: 10px;
    display: block;
    border-radius: 5px;
    margin-bottom: 5px;
}

.sidebar a:hover, .sidebar a.active {
    background: #e9ecef;
}

.sidebar-heading {
    color: #6c757d;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 1rem;
}

.activity-timeline {
    position: relative;
    max-height: 400px;
    overflow-y: auto;
}

.activity-item {
    padding: 15px 0;
    border-bottom: 1px solid #dee2e6;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-content {
    display: flex;
    align-items: flex-start;
}

.activity-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    color: white;
}

.activity-details {
    flex: 1;
}

.search-box {
    max-width: 300px;
}

.search-box form {
    gap: 10px;
}

.progress {
    height: 4px;
    margin-top: 10px;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.table th {
    font-weight: 600;
    font-size: 0.9rem;
}

.filter-form label {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}
</style>

<?php require_once 'includes/footer.php'; ?> 