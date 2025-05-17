<?php
require_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="jumbotron">
        <h1 class="display-4">Welcome to Citizen Engagement Portal</h1>
        <p class="lead">A platform for citizens to report issues and engage with public services.</p>
        <hr class="my-4">
        <?php if (!isset($_SESSION['user_id'])): ?>
            <p>Please login or register to submit and track your complaints.</p>
            <div class="mt-4">
                <a href="<?php echo $base_path; ?>login" class="btn btn-primary btn-lg mr-3">Login</a>
                <a href="<?php echo $base_path; ?>register" class="btn btn-success btn-lg">Register</a>
            </div>
        <?php else: ?>
            <p>Welcome back! You can submit new complaints or check the status of your existing ones.</p>
            <div class="mt-4">
                <a href="<?php echo $base_path; ?>submit-ticket" class="btn btn-primary btn-lg mr-3">Submit New Complaint</a>
                <a href="<?php echo $base_path; ?>dashboard" class="btn btn-info btn-lg">View Dashboard</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="row mt-5">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-file-alt"></i> Submit Complaints</h5>
                    <p class="card-text">Easily submit your complaints and track their progress through our system.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-clock"></i> Real-time Updates</h5>
                    <p class="card-text">Get real-time updates on the status of your complaints and official responses.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-chart-line"></i> Track Progress</h5>
                    <p class="card-text">Monitor the progress of your complaints from submission to resolution.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 