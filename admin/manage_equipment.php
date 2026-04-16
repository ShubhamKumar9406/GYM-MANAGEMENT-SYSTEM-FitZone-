<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = sanitize($_POST['name']);
        $purchase_date = $_POST['purchase_date'];
        $purchase_price = floatval($_POST['purchase_price']);
        $status = $_POST['status'];
        $notes = sanitize($_POST['notes']);
        $equipment_image = null;

        // Handle image upload
        if (isset($_FILES['equipment_image']) && $_FILES['equipment_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (in_array($_FILES['equipment_image']['type'], $allowed_types) && $_FILES['equipment_image']['size'] <= $max_size) {
                $extension = pathinfo($_FILES['equipment_image']['name'], PATHINFO_EXTENSION);
                $clean_name = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($name));
                $filename = 'equipment_' . $clean_name . '_' . time() . '.' . $extension;
                $upload_path = __DIR__ . '/../assets/images/equipment/' . $filename;

                if (move_uploaded_file($_FILES['equipment_image']['tmp_name'], $upload_path)) {
                    $equipment_image = $filename;
                }
            }
        }

        $stmt = $db->prepare("INSERT INTO equipment (name, purchase_date, purchase_price, status, notes, equipment_image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsss", $name, $purchase_date, $purchase_price, $status, $notes, $equipment_image);

        if ($stmt->execute()) {
            setFlashMessage('success', 'Equipment added successfully!');
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id']);
        $name = sanitize($_POST['name']);
        $purchase_date = $_POST['purchase_date'];
        $purchase_price = floatval($_POST['purchase_price']);
        $status = $_POST['status'];
        $notes = sanitize($_POST['notes']);

        // Get current equipment data
        $current = $db->query("SELECT equipment_image FROM equipment WHERE id = $id")->fetch_assoc();
        $equipment_image = $current['equipment_image'];

        // Handle image upload
        if (isset($_FILES['equipment_image']) && $_FILES['equipment_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            $max_size = 5 * 1024 * 1024;

            if (in_array($_FILES['equipment_image']['type'], $allowed_types) && $_FILES['equipment_image']['size'] <= $max_size) {
                // Delete old image
                if ($equipment_image && file_exists(__DIR__ . '/../assets/images/equipment/' . $equipment_image)) {
                    unlink(__DIR__ . '/../assets/images/equipment/' . $equipment_image);
                }

                $extension = pathinfo($_FILES['equipment_image']['name'], PATHINFO_EXTENSION);
                $clean_name = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($name));
                $filename = 'equipment_' . $clean_name . '_' . time() . '.' . $extension;
                $upload_path = __DIR__ . '/../assets/images/equipment/' . $filename;

                if (move_uploaded_file($_FILES['equipment_image']['tmp_name'], $upload_path)) {
                    $equipment_image = $filename;
                }
            }
        }

        $stmt = $db->prepare("UPDATE equipment SET name = ?, purchase_date = ?, purchase_price = ?, status = ?, notes = ?, equipment_image = ? WHERE id = ?");
        $stmt->bind_param("ssdsssi", $name, $purchase_date, $purchase_price, $status, $notes, $equipment_image, $id);

        if ($stmt->execute()) {
            setFlashMessage('success', 'Equipment updated successfully!');
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        // Get equipment data to delete image
        $equip = $db->query("SELECT equipment_image FROM equipment WHERE id = $id")->fetch_assoc();
        if ($equip && $equip['equipment_image'] && file_exists(__DIR__ . '/../assets/images/equipment/' . $equip['equipment_image'])) {
            unlink(__DIR__ . '/../assets/images/equipment/' . $equip['equipment_image']);
        }

        if ($db->query("DELETE FROM equipment WHERE id = $id")) {
            setFlashMessage('success', 'Equipment deleted successfully!');
        }
    }

    redirect('manage_equipment.php');
}

$equipment = $db->query("SELECT * FROM equipment ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Equipment - <?php echo SITE_NAME; ?></title>
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

        <h1>Manage Equipment</h1>

        <div class="card">
            <div class="card-header">
                <h3>All Equipment</h3>
                <button onclick="openModal('addEquipmentModal')" class="btn btn-primary">+ Add New Equipment</button>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Purchase Date</th>
                            <th>Purchase Price</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($equipment as $item): ?>
                            <tr>
                                <td><?php echo $item['id']; ?></td>
                                <td>
                                    <?php if ($item['equipment_image'] && file_exists(__DIR__ . '/../assets/images/equipment/' . $item['equipment_image'])): ?>
                                        <img src="../assets/images/equipment/<?php echo htmlspecialchars($item['equipment_image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                    <?php else: ?>
                                        <span style="font-size: 2rem;">🏋️</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo formatDate($item['purchase_date']); ?></td>
                                <td>₹<?php echo number_format($item['purchase_price'], 2); ?></td>
                                <td>
                                    <?php if ($item['status'] === 'working'): ?>
                                        <span class="badge badge-success">Working</span>
                                    <?php elseif ($item['status'] === 'maintenance'): ?>
                                        <span class="badge badge-warning">Maintenance</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Damaged</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['notes']); ?></td>
                                <td>
                                    <button onclick='editEquipment(<?php echo json_encode($item); ?>)'
                                        class="btn btn-sm btn-primary">Edit</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this equipment?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
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

    <!-- Add Equipment Modal -->
    <div id="addEquipmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Equipment</h3>
                <span class="close" onclick="closeModal('addEquipmentModal')">&times;</span>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Equipment Image</label>
                    <input type="file" name="equipment_image" accept="image/*">
                    <small style="color: #666; display: block; margin-top: 0.3rem;">JPG, PNG, or GIF (Max 5MB)</small>
                </div>
                <div class="form-group">
                    <label>Equipment Name *</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Purchase Date</label>
                    <input type="date" name="purchase_date">
                </div>
                <div class="form-group">
                    <label>Purchase Price (₹)</label>
                    <input type="number" name="purchase_price" step="0.01">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="working">Working</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="damaged">Damaged</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Add Equipment</button>
            </form>
        </div>
    </div>

    <!-- Edit Equipment Modal -->
    <div id="editEquipmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Equipment</h3>
                <span class="close" onclick="closeModal('editEquipmentModal')">&times;</span>
            </div>
            <form method="POST" id="editForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Equipment Image</label>
                    <input type="file" name="equipment_image" accept="image/*">
                    <small style="color: #666; display: block; margin-top: 0.3rem;">Upload new image to replace existing (JPG, PNG, or GIF, Max 5MB)</small>
                </div>
                <div class="form-group">
                    <label>Equipment Name *</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Purchase Date</label>
                    <input type="date" name="purchase_date" id="edit_purchase_date">
                </div>
                <div class="form-group">
                    <label>Purchase Price (₹)</label>
                    <input type="number" name="purchase_price" id="edit_purchase_price" step="0.01">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status">
                        <option value="working">Working</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="damaged">Damaged</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" id="edit_notes" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Update Equipment</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function editEquipment(equipment) {
            document.getElementById('edit_id').value = equipment.id;
            document.getElementById('edit_name').value = equipment.name;
            document.getElementById('edit_purchase_date').value = equipment.purchase_date;
            document.getElementById('edit_purchase_price').value = equipment.purchase_price;
            document.getElementById('edit_status').value = equipment.status;
            document.getElementById('edit_notes').value = equipment.notes || '';
            openModal('editEquipmentModal');
        }
    </script>
</body>

</html>