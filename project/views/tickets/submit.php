<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Ensure user is logged in
requireLogin();

// Get list of agencies for dropdown
$stmt = $conn->prepare("SELECT * FROM agencies ORDER BY name ASC");
$stmt->execute();
$agencies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories for initial load
$stmt = $conn->prepare("SELECT * FROM categories ORDER BY name ASC");
$stmt->execute();
$all_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group categories by agency
$categories_by_agency = [];
foreach ($all_categories as $category) {
    $agency_id = $category['agency_id'];
    if (!isset($categories_by_agency[$agency_id])) {
        $categories_by_agency[$agency_id] = [];
    }
    $categories_by_agency[$agency_id][] = $category;
}

// Initialize variables
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid token. Please try again.';
    } else {
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $agency_id = (int)$_POST['agency_id'];
        $priority = sanitizeInput($_POST['priority']);
        $category_id = (int)$_POST['category_id'];

        // Validate inputs
        if (empty($title) || empty($description) || empty($agency_id)) {
            $error = 'Please fill in all required fields.';
        } else {
            try {
                // Create ticket
                $stmt = $conn->prepare("
                    INSERT INTO tickets (title, description, user_id, agency_id, category_id, status, priority, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW())
                ");
                
                $stmt->execute([$title, $description, $_SESSION['user_id'], $agency_id, $category_id, $priority]);
                $ticketId = $conn->lastInsertId();

                // Handle file upload if present
                if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['attachment'];
                    $fileName = $file['name'];
                    $fileType = $file['type'];
                    $fileTmpPath = $file['tmp_name'];
                    
                    // Create uploads directory if it doesn't exist
                    $uploadDir = 'uploads/tickets/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    // Generate unique filename
                    $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
                    $newFileName = 'ticket_' . $ticketId . '_' . uniqid() . '.' . $fileExt;
                    $targetPath = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($fileTmpPath, $targetPath)) {
                        // Save file information to database
                        $stmt = $conn->prepare("
                            INSERT INTO attachments (ticket_id, file_name, file_path, file_type) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$ticketId, $fileName, $targetPath, $fileType]);
                    }
                }

                $success = 'Ticket submitted successfully! Redirecting to dashboard...';
                header("Refresh: 2; URL=" . $base_path . "dashboard");
            } catch (PDOException $e) {
                $error = 'Error submitting ticket. Please try again.';
                error_log("Ticket submission error: " . $e->getMessage());
            }
        }
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-plus-circle"></i> Submit New Ticket</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php else: ?>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="mb-3">
                                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required
                                    value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="agency_id" class="form-label">Agency <span class="text-danger">*</span></label>
                                <select class="form-select" id="agency_id" name="agency_id" required>
                                    <option value="">Select Agency</option>
                                    <?php foreach ($agencies as $agency): ?>
                                        <option value="<?php echo $agency['id']; ?>"
                                            <?php echo (isset($_POST['agency_id']) && $_POST['agency_id'] == $agency['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($agency['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php 
                                    if (isset($_POST['agency_id']) && isset($categories_by_agency[$_POST['agency_id']])) {
                                        foreach ($categories_by_agency[$_POST['agency_id']] as $category) {
                                            $selected = (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '';
                                            echo '<option value="' . $category['id'] . '" ' . $selected . '>' . 
                                                 htmlspecialchars($category['name']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="low" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'low') ? 'selected' : ''; ?>>Low</option>
                                    <option value="medium" <?php echo (!isset($_POST['priority']) || $_POST['priority'] == 'medium') ? 'selected' : ''; ?>>Medium</option>
                                    <option value="high" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'high') ? 'selected' : ''; ?>>High</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="attachment" class="form-label">Attachment</label>
                                <input type="file" class="form-control" id="attachment" name="attachment">
                                <div class="form-text">Max file size: 5MB. Allowed types: jpg, jpeg, png, pdf, doc, docx</div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Submit Ticket
                                </button>
                                <a href="<?php echo $base_path; ?>dashboard" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Store categories data
const categoriesByAgency = <?php echo json_encode($categories_by_agency); ?>;

// Function to load categories based on selected agency
function loadCategories(agencyId) {
    const categorySelect = document.getElementById('category_id');
    categorySelect.innerHTML = '<option value="">Select Category</option>';
    
    if (!agencyId || !categoriesByAgency[agencyId]) {
        return;
    }

    categoriesByAgency[agencyId].forEach(category => {
        const option = document.createElement('option');
        option.value = category.id;
        option.textContent = category.name;
        categorySelect.appendChild(option);
    });
}

// Add event listener to agency select
document.getElementById('agency_id').addEventListener('change', function() {
    loadCategories(this.value);
});

// Load categories for pre-selected agency (if any)
const selectedAgency = document.getElementById('agency_id').value;
if (selectedAgency) {
    loadCategories(selectedAgency);
}
</script>

<?php require_once 'includes/footer.php'; ?> 