<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '/project/');
}

// Ensure the user is authenticated and is an admin
if (!function_exists('isAdmin') || !isAdmin()) {
    header('Location: ' . BASE_PATH . 'login');
    exit;
}

// Get date range for analytics
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Get overall statistics
$statsQuery = "SELECT 
    COUNT(*) as total_tickets,
    AVG(TIMESTAMPDIFF(HOUR, created_at, CASE 
        WHEN status IN ('resolved', 'closed') THEN updated_at
        ELSE NOW()
    END)) as avg_resolution_time,
    SUM(CASE WHEN status = 'resolved' OR status = 'closed' THEN 1 ELSE 0 END) as resolved_tickets,
    SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority_tickets
FROM tickets
WHERE created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";

$statsStmt = $conn->prepare($statsQuery);
$statsStmt->execute([$date_from, $date_to]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get tickets by status
$statusQuery = "SELECT 
    status,
    COUNT(*) as count,
    COUNT(*) * 100.0 / (SELECT COUNT(*) FROM tickets WHERE created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)) as percentage
FROM tickets
WHERE created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
GROUP BY status";

$statusStmt = $conn->prepare($statusQuery);
$statusStmt->execute([$date_from, $date_to, $date_from, $date_to]);
$statusStats = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

// Get tickets by agency
$agencyQuery = "SELECT 
    a.name as agency_name,
    COUNT(t.id) as ticket_count,
    AVG(TIMESTAMPDIFF(HOUR, t.created_at, CASE 
        WHEN t.status IN ('resolved', 'closed') THEN t.updated_at
        ELSE NOW()
    END)) as avg_resolution_time
FROM agencies a
LEFT JOIN tickets t ON a.id = t.agency_id
    AND t.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
GROUP BY a.id, a.name
ORDER BY ticket_count DESC";

$agencyStmt = $conn->prepare($agencyQuery);
$agencyStmt->execute([$date_from, $date_to]);
$agencyStats = $agencyStmt->fetchAll(PDO::FETCH_ASSOC);

// Get daily ticket counts
$dailyQuery = "SELECT 
    DATE(created_at) as date,
    COUNT(*) as new_tickets,
    SUM(CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END) as resolved_tickets
FROM tickets
WHERE created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
GROUP BY DATE(created_at)
ORDER BY date";

$dailyStmt = $conn->prepare($dailyQuery);
$dailyStmt->execute([$date_from, $date_to]);
$dailyStats = $dailyStmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for charts
$dates = [];
$newTickets = [];
$resolvedTickets = [];
foreach ($dailyStats as $stat) {
    $dates[] = $stat['date'];
    $newTickets[] = $stat['new_tickets'];
    $resolvedTickets[] = $stat['resolved_tickets'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Analytics - Admin Dashboard</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: #6c757d;
            font-size: 1rem;
        }
        .chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .table-container {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
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
        .date-filter {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .export-button {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
        }
        .export-button:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="analytics-container">
        <div class="dashboard-header">
            <h1>Ticket Analytics</h1>
            <div class="date-filter">
                <form method="GET" action="" id="dateForm">
                    <label for="date_from">From:</label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo $date_from; ?>">
                    
                    <label for="date_to">To:</label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo $date_to; ?>">
                    
                    <button type="submit" class="btn btn-primary">Apply</button>
                </form>
                <button onclick="exportReport()" class="export-button">Export Report</button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_tickets']); ?></div>
                <div class="stat-label">Total Tickets</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['resolved_tickets']); ?></div>
                <div class="stat-label">Resolved Tickets</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo round($stats['avg_resolution_time'] / 24, 1); ?></div>
                <div class="stat-label">Avg. Resolution Time (Days)</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['high_priority_tickets']); ?></div>
                <div class="stat-label">High Priority Tickets</div>
            </div>
        </div>

        <div class="chart-container">
            <h2>Ticket Activity Over Time</h2>
            <canvas id="ticketChart"></canvas>
        </div>

        <div class="chart-container">
            <h2>Ticket Status Distribution</h2>
            <canvas id="statusChart"></canvas>
        </div>

        <div class="table-container">
            <h2>Agency Performance</h2>
            <table>
                <thead>
                    <tr>
                        <th>Agency</th>
                        <th>Total Tickets</th>
                        <th>Avg. Resolution Time (Days)</th>
                        <th>Resolution Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agencyStats as $agency): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($agency['agency_name']); ?></td>
                            <td><?php echo number_format($agency['ticket_count']); ?></td>
                            <td><?php echo round($agency['avg_resolution_time'] / 24, 1); ?></td>
                            <td><?php echo round(($agency['ticket_count'] > 0 ? $agency['resolved_count'] / $agency['ticket_count'] * 100 : 0), 1); ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Ticket Activity Chart
        new Chart(document.getElementById('ticketChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'New Tickets',
                    data: <?php echo json_encode($newTickets); ?>,
                    borderColor: '#0d6efd',
                    tension: 0.1
                }, {
                    label: 'Resolved Tickets',
                    data: <?php echo json_encode($resolvedTickets); ?>,
                    borderColor: '#28a745',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Status Distribution Chart
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($statusStats, 'status')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($statusStats, 'count')); ?>,
                    backgroundColor: [
                        '#ffc107', // pending
                        '#0dcaf0', // in_progress
                        '#6c757d', // under_review
                        '#28a745', // resolved
                        '#dc3545'  // closed
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        function exportReport() {
            const params = new URLSearchParams(window.location.search);
            const date_from = params.get('date_from') || document.getElementById('date_from').value;
            const date_to = params.get('date_to') || document.getElementById('date_to').value;
            
            window.location.href = `export-report.php?date_from=${date_from}&date_to=${date_to}`;
        }
    </script>
</body>
</html> 