<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Ensure user is logged in
requireLogin();

// Get user's tickets
$tickets = getTicketsByUserId($_SESSION['user_id']);
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-list"></i> My Tickets</h4>
                    <a href="<?php echo $base_path; ?>submit-ticket" class="btn btn-light">
                        <i class="fas fa-plus-circle"></i> Submit New Ticket
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($tickets)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                            <p class="lead">You haven't submitted any tickets yet</p>
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
                                        <th>Created</th>
                                        <th>Last Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tickets as $ticket): ?>
                                        <tr>
                                            <td>#<?php echo $ticket['id']; ?></td>
                                            <td><?php echo htmlspecialchars($ticket['title']); ?></td>
                                            <td><?php echo htmlspecialchars($ticket['agency_name']); ?></td>
                                            <td><?php echo getStatusBadge($ticket['status']); ?></td>
                                            <td><?php echo getPriorityBadge($ticket['priority']); ?></td>
                                            <td><?php echo formatDate($ticket['created_at']); ?></td>
                                            <td>
                                                <?php if ($ticket['updated_at']): ?>
                                                    <?php echo formatDate($ticket['updated_at']); ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 