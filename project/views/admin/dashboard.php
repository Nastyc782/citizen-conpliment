<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Ensure user is admin
requireAdmin();

// Get statistics
try {
    // Total tickets
    $stmt = $conn->query("SELECT COUNT(*) as total FROM tickets");
    $totalTickets = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Tickets by status
    $stmt = $conn->query("
        SELECT status, COUNT(*) as count 
        FROM tickets 
        GROUP BY status
    ");
    $ticketsByStatus = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Recent tickets
    $stmt = $conn->query("
        SELECT t.*, u.name as user_name, a.name as agency_name, c.name as category_name
        FROM tickets t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN agencies a ON t.agency_id = a.id
        LEFT JOIN categories c ON t.category_id = c.id
        ORDER BY t.created_at DESC
        LIMIT 5
    ");
    $recentTickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Agency statistics
    $stmt = $conn->query("
        SELECT a.name, COUNT(t.id) as ticket_count
        FROM agencies a
        LEFT JOIN tickets t ON a.id = t.agency_id
        GROUP BY a.id, a.name
        ORDER BY ticket_count DESC
        LIMIT 5
    ");
    $agencyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
}
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-user-shield"></i> Admin Dashboard</h2>
        <div>
            <a href="<?php echo $base_path; ?>admin/agencies" class="btn btn-primary me-2">
                <i class="fas fa-building"></i> Manage Agencies
            </a>
            <a href="<?php echo $base_path; ?>admin/tickets" class="btn btn-info">
                <i class="fas fa-ticket-alt"></i> View All Tickets
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Tickets</h5>
                    <h2 class="card-text"><?php echo $totalTickets; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Pending</h5>
                    <h2 class="card-text"><?php echo $ticketsByStatus['pending'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">In Progress</h5>
                    <h2 class="card-text"><?php echo $ticketsByStatus['in_progress'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Resolved</h5>
                    <h2 class="card-text"><?php echo $ticketsByStatus['resolved'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Tickets -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clock"></i> Recent Tickets</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>User</th>
                                    <th>Agency</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTickets as $ticket): ?>
                                    <tr>
                                        <td>#<?php echo $ticket['id']; ?></td>
                                        <td><?php echo htmlspecialchars($ticket['title']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['agency_name']); ?></td>
                                        <td><?php echo getStatusBadge($ticket['status']); ?></td>
                                        <td><?php echo formatDate($ticket['created_at']); ?></td>
                                        <td>
                                            <a href="<?php echo $base_path; ?>view-ticket?id=<?php echo $ticket['id']; ?>" 
                                               class="btn btn-sm btn-info" 
                                               data-bs-toggle="tooltip" 
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Agency Statistics -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Agency Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($agencyStats as $stat): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($stat['name']); ?>
                                <span class="badge bg-primary rounded-pill">
                                    <?php echo $stat['ticket_count']; ?> tickets
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 