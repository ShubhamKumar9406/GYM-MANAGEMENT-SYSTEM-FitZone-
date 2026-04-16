<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = sanitize($_POST['name']);
        $price = floatval($_POST['price']);
        $duration_days = intval($_POST['duration_days']);
        $description = sanitize($_POST['description']);

        $stmt = $db->prepare("INSERT INTO plans (name, price, duration_days, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdis", $name, $price, $duration_days, $description);

        if ($stmt->execute()) {
            setFlashMessage('success', 'Plan added successfully!');
        } else {
            setFlashMessage('danger', 'Failed to add plan.');
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id']);
        $name = sanitize($_POST['name']);
        $price = floatval($_POST['price']);
        $duration_days = intval($_POST['duration_days']);
        $description = sanitize($_POST['description']);

        $stmt = $db->prepare("UPDATE plans SET name = ?, price = ?, duration_days = ?, description = ? WHERE id = ?");
        $stmt->bind_param("sdisi", $name, $price, $duration_days, $description, $id);

        if ($stmt->execute()) {
            setFlashMessage('success', 'Plan updated successfully!');
        } else {
            setFlashMessage('danger', 'Failed to update plan.');
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);

        if ($db->query("DELETE FROM plans WHERE id = $id")) {
            setFlashMessage('success', 'Plan deleted successfully!');
        } else {
            setFlashMessage('danger', 'Failed to delete plan.');
        }
    }

    redirect('manage_plans.php');
}

$plans = $db->query("SELECT * FROM plans ORDER BY price ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Plans - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .form-group input,
        .form-group select,
        .form-group textarea {
            background: white !important;
            color: #1a1a1a !important;
            border: 2px solid #e0e0e0 !important;
        }

        .form-group input::placeholder {
            color: #999 !important;
        }
    </style>
</head>

<body class="dashboard">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <?php displayFlashMessage(); ?>

        <h1>Manage Membership Plans</h1>

        <div class="card">
            <div class="card-header">
                <h3>All Plans</h3>
                <button onclick="openModal('addPlanModal')" class="btn btn-primary">+ Add New Plan</button>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Price (₹)</th>
                            <th>Duration (Days)</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($plans as $plan): ?>
                            <tr>
                                <td><?php echo $plan['id']; ?></td>
                                <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                <td>₹<?php echo number_format($plan['price'], 2); ?></td>
                                <td><?php echo $plan['duration_days']; ?></td>
                                <td><?php echo htmlspecialchars($plan['description']); ?></td>
                                <td>
                                    <button onclick="editPlan(<?php echo htmlspecialchars(json_encode($plan)); ?>)"
                                        class="btn btn-sm btn-primary">Edit</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this plan?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $plan['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Add Plan Modal -->
    <div id="addPlanModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Plan</h3>
                <span class="close" onclick="closeModal('addPlanModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Plan Name *</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Price (₹) *</label>
                    <input type="number" name="price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Duration (Days) *</label>
                    <input type="number" name="duration_days" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Add Plan</button>
            </form>
        </div>
    </div>

    <!-- Edit Plan Modal -->
    <div id="editPlanModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Plan</h3>
                <span class="close" onclick="closeModal('editPlanModal')">&times;</span>
            </div>
            <form method="POST" id="editPlanForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Plan Name *</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Price (₹) *</label>
                    <input type="number" name="price" id="edit_price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Duration (Days) *</label>
                    <input type="number" name="duration_days" id="edit_duration_days" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Update Plan</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function editPlan(plan) {
            document.getElementById('edit_id').value = plan.id;
            document.getElementById('edit_name').value = plan.name;
            document.getElementById('edit_price').value = plan.price;
            document.getElementById('edit_duration_days').value = plan.duration_days;
            document.getElementById('edit_description').value = plan.description;
            openModal('editPlanModal');
        }
    </script>
</body>

</html>